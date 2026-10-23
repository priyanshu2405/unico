<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\InventoryVoucher;
use App\Models\RewardPointsLedger;
use App\Models\B2bReferralQualification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    /**
     * Ensure the user has an initialized unique referral code
     */
    public static function ensureUserReferralCode(User $user): string
    {
        if (!empty($user->referral_code)) {
            return $user->referral_code;
        }

        $prefix = match ($user->account_type) {
            'agent', 'regular_agent' => 'UC-AG',
            'reseller_agent' => 'UC-RS',
            'student' => 'UC-ST',
            default => 'UC-REF',
        };

        do {
            $code = $prefix . '-' . strtoupper(Str::random(6));
        } while (User::where('referral_code', $code)->exists());

        $user->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Get full public registration referral link for user
     */
    public static function getReferralLink(User $user): string
    {
        $code = self::ensureUserReferralCode($user);
        return route('register', ['ref' => $code]);
    }

    /**
     * Bind a referred user to a referrer code with strict anti-fraud checks
     */
    public static function bindReferral(User $user, string $referralCode, bool $isManual = false): array
    {
        $referralCode = strtoupper(trim($referralCode));
        if (empty($referralCode)) {
            return ['success' => false, 'message' => 'Please provide a valid referral code.'];
        }

        if ($user->referred_by) {
            return ['success' => false, 'message' => 'You already have an active referrer linked to your account.'];
        }

        $referrer = User::where('referral_code', $referralCode)->first();
        if (!$referrer) {
            return ['success' => false, 'message' => 'Invalid referral code. Referrer not found.'];
        }

        // 1. Self-referral prohibition
        if ($referrer->id === $user->id || $user->referral_code === $referralCode) {
            return ['success' => false, 'message' => 'Self-referrals are prohibited.'];
        }

        // 2. Anti-fraud: Same email or phone
        if (strtolower($referrer->email) === strtolower($user->email)) {
            return ['success' => false, 'message' => 'Cannot refer an account using the same email address.'];
        }

        if (!empty($user->phone) && !empty($referrer->phone) && $user->phone === $referrer->phone) {
            return ['success' => false, 'message' => 'Cannot refer an account with the same phone number.'];
        }

        // 3. If manual binding after registration: must be before the first completed purchase
        if ($isManual) {
            $hasPurchases = Order::where('user_id', $user->id)
                ->whereIn('status', ['completed', 'delivered'])
                ->exists();
            if ($hasPurchases) {
                return ['success' => false, 'message' => 'Referral code must be recorded before your first completed purchase.'];
            }
        }

        DB::transaction(function () use ($user, $referrer) {
            $user->update([
                'referred_by' => $referrer->id,
                'referral_registered_at' => now(),
            ]);

            // Check if B2B referral applies: Referrer is Agent/Reseller/Consultant and Referred is Agent/Reseller
            $isReferrerB2b = in_array($referrer->account_type, ['agent', 'reseller_agent', 'manager']);
            $isReferredB2b = in_array($user->account_type, ['agent', 'reseller_agent']);

            if ($isReferrerB2b && $isReferredB2b) {
                $registrationTime = $user->created_at ?? now();
                $deadline = Carbon::parse($registrationTime)->copy()->addMonthsNoOverflow(3)->endOfDay();

                B2bReferralQualification::firstOrCreate(
                    ['referred_id' => $user->id],
                    [
                        'referrer_id' => $referrer->id,
                        'target_amount' => 100000.00, // Rs. 100,000 threshold
                        'qualifying_start_date' => $registrationTime,
                        'qualifying_deadline' => $deadline,
                        'total_eligible_purchases' => 0.00,
                        'is_qualified' => false,
                        'status' => 'in_progress',
                    ]
                );
            }
        });

        return ['success' => true, 'message' => 'Referral linked successfully to ' . $referrer->name];
    }

    /**
     * Handle points calculation and ledger recording when an order is delivered
     */
    public static function recordOrderDelivered(Order $order): void
    {
        $buyer = $order->user;
        if (!$buyer) {
            return;
        }

        $deliveryTime = now();
        $voucher = InventoryVoucher::where('sku_id', $order->voucher_id)->first();
        $quantity = max(1, (int) $order->quantity);

        // Eligible purchase value: subtotal after discounts, excluding taxes/charges, amounts paid with points
        // Use local voucher currency amount (or PKR 1:1 if Pakistan)
        $cashAmountPaid = max(0, (float) $order->amount - (float) ($order->points_redemption_amount ?? 0));
        $cashRatio = (float) $order->amount > 0 ? ($cashAmountPaid / (float) $order->amount) : 1.0;

        DB::transaction(function () use ($order, $buyer, $voucher, $quantity, $deliveryTime, $cashAmountPaid, $cashRatio) {
            // ==========================================
            // 1. REFERRAL REWARDS (Earned by Referrer)
            // ==========================================
            if ($buyer->referred_by) {
                $referrer = User::find($buyer->referred_by);

                if ($referrer) {
                    $isReferrerB2b = in_array($referrer->account_type, ['agent', 'reseller_agent', 'manager']);
                    $isReferredB2b = in_array($buyer->account_type, ['agent', 'reseller_agent']);

                    if ($isReferrerB2b && $isReferredB2b) {
                        // B2B Referral: 1% of eligible purchases
                        self::processB2bReferralEarning($order, $referrer, $buyer, $cashAmountPaid, $deliveryTime);
                    } else {
                        // Student/Standard Referral: Qualifying voucher units * student_referral_points_per_unit
                        self::processStudentReferralEarning($order, $referrer, $buyer, $voucher, $quantity, $cashRatio, $deliveryTime);
                    }
                }
            }

            // ==========================================
            // 2. STUDENT PURCHASE BONUS (Earned by Buyer)
            // ==========================================
            if ($buyer->isStudent() && $voucher && $voucher->student_bonus_points_per_unit > 0) {
                $baseBonusPoints = $voucher->student_bonus_points_per_unit * $quantity;
                $awardedBonusPoints = (int) round($baseBonusPoints * $cashRatio); // No points on points

                if ($awardedBonusPoints > 0) {
                    $availableAt = $deliveryTime->copy()->addDays(14); // 14 days release
                    $expiresAt = $availableAt->copy()->addDays(365); // 365 days expiry

                    RewardPointsLedger::create([
                        'user_id' => $buyer->id,
                        'order_id' => $order->id,
                        'points' => $awardedBonusPoints,
                        'points_remaining' => $awardedBonusPoints,
                        'point_type' => 'student_purchase_bonus',
                        'status' => 'pending',
                        'available_at' => $availableAt,
                        'expires_at' => $expiresAt,
                        'description' => "Student Purchase Bonus for Order #{$order->order_id} ({$quantity} unit(s))",
                        'metadata' => [
                            'voucher_sku' => $voucher->sku_id,
                            'bonus_per_unit' => $voucher->student_bonus_points_per_unit,
                            'quantity' => $quantity,
                            'cash_ratio' => $cashRatio,
                        ],
                    ]);
                }
            }

            // ==========================================
            // 3. AGENT BONUS ACCRUAL (Monthly Bonus)
            // ==========================================
            if (($buyer->isAgent() || $buyer->isResellerAgent()) && $voucher && $voucher->agent_bonus_points_per_unit > 0) {
                // Special condition: Orders using full 10% points adjustment do NOT earn bonus points
                if (!$order->is_full_points_redemption) {
                    $baseAgentBonus = $voucher->agent_bonus_points_per_unit * $quantity;
                    $awardedAgentBonus = (int) round($baseAgentBonus * $cashRatio);

                    if ($awardedAgentBonus > 0) {
                        // Monthly bonus becomes available on 7th day of the following calendar month
                        $nextMonthSeventh = $deliveryTime->copy()->addMonthNoOverflow()->startOfMonth()->addDays(6)->startOfDay();
                        $expiresAt = $nextMonthSeventh->copy()->addDays(30); // 30 days validity

                        RewardPointsLedger::create([
                            'user_id' => $buyer->id,
                            'order_id' => $order->id,
                            'points' => $awardedAgentBonus,
                            'points_remaining' => $awardedAgentBonus,
                            'point_type' => 'agent_monthly_bonus',
                            'status' => 'pending',
                            'available_at' => $nextMonthSeventh,
                            'expires_at' => $expiresAt,
                            'description' => "Monthly Bonus for Order #{$order->order_id} ({$quantity} unit(s))",
                            'metadata' => [
                                'voucher_sku' => $voucher->sku_id,
                                'bonus_per_unit' => $voucher->agent_bonus_points_per_unit,
                                'quantity' => $quantity,
                                'earning_month' => $deliveryTime->format('Y-m'),
                            ],
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Process B2B Referral 1% reward and 3-month Rs. 100,000 threshold tracking
     */
    protected static function processB2bReferralEarning(Order $order, User $referrer, User $buyer, float $cashAmountPaid, Carbon $deliveryTime): void
    {
        $qualification = B2bReferralQualification::where('referred_id', $buyer->id)->first();
        if (!$qualification) {
            $registrationTime = $buyer->created_at ?? $order->created_at ?? now();
            $qualification = B2bReferralQualification::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $buyer->id,
                'target_amount' => 100000.00,
                'qualifying_start_date' => $registrationTime,
                'qualifying_deadline' => Carbon::parse($registrationTime)->copy()->addMonthsNoOverflow(3)->endOfDay(),
                'total_eligible_purchases' => 0.00,
                'is_qualified' => false,
                'status' => 'in_progress',
            ]);
        }

        // 1% reward: Rs. 100,000 earns 1,000 points (1 point = Rs. 1)
        $pointsEarned = (int) round($cashAmountPaid * 0.01);
        if ($pointsEarned <= 0) {
            return;
        }

        $now = now();
        $isWithinWindow = $now->lte($qualification->qualifying_deadline);

        if (!$qualification->is_qualified && $isWithinWindow) {
            // Accumulate purchases toward target
            $qualification->total_eligible_purchases += $cashAmountPaid;

            if ($qualification->total_eligible_purchases >= $qualification->target_amount) {
                // Qualified!
                $qualification->is_qualified = true;
                $qualification->status = 'qualified';
                $qualification->qualified_at = $now;
                $qualification->save();

                // Unlock previous pending introductory points for this referrer from this buyer
                self::unlockQualifiedB2bPendingPoints($referrer, $buyer);
            } else {
                $qualification->save();
            }
        } elseif (!$qualification->is_qualified && !$isWithinWindow) {
            // Window passed without reaching target
            $qualification->status = 'expired';
            $qualification->save();
        }

        // Release condition: After qualification is met AND 14 days after payment & delivery
        $isReady = $qualification->is_qualified;
        $availableAt = $deliveryTime->copy()->addDays(14);
        $expiresAt = $availableAt->copy()->addDays(180); // 180 days validity

        $status = ($isReady && now()->gte($availableAt)) ? 'available' : 'pending';

        RewardPointsLedger::create([
            'user_id' => $referrer->id,
            'order_id' => $order->id,
            'points' => $pointsEarned,
            'points_remaining' => $pointsEarned,
            'point_type' => 'b2b_referral',
            'status' => $qualification->status === 'expired' ? 'cancelled' : $status,
            'available_at' => $availableAt,
            'expires_at' => $expiresAt,
            'description' => "B2B Referral (1%) from {$buyer->name} for Order #{$order->order_id}",
            'metadata' => [
                'referred_user_id' => $buyer->id,
                'referred_name' => $buyer->name,
                'order_amount' => $cashAmountPaid,
                'is_qualified' => $qualification->is_qualified,
            ],
        ]);
    }

    /**
     * Unlock pending introductory points when B2B qualification threshold is reached
     */
    protected static function unlockQualifiedB2bPendingPoints(User $referrer, User $buyer): void
    {
        $pendingRecords = RewardPointsLedger::where('user_id', $referrer->id)
            ->where('point_type', 'b2b_referral')
            ->where('status', 'pending')
            ->get();

        foreach ($pendingRecords as $record) {
            $metadata = $record->metadata ?? [];
            if (($metadata['referred_user_id'] ?? null) == $buyer->id) {
                $metadata['is_qualified'] = true;
                $record->metadata = $metadata;

                // If 14 days have already elapsed since order delivery, make available now
                if ($record->available_at && now()->gte($record->available_at)) {
                    $record->status = 'available';
                }
                $record->save();
            }
        }
    }

    /**
     * Process Student Referral Points
     */
    protected static function processStudentReferralEarning(Order $order, User $referrer, User $buyer, ?InventoryVoucher $voucher, int $quantity, float $cashRatio, Carbon $deliveryTime): void
    {
        $pointsPerUnit = $voucher ? (int) $voucher->student_referral_points_per_unit : 0;
        if ($pointsPerUnit <= 0) {
            // Check fallback reseller referral or agent referral points
            $pointsPerUnit = (int) ($voucher->agent_referral_points_per_unit ?? $voucher->referral_points_reseller ?? 0);
        }

        $basePoints = $pointsPerUnit * $quantity;
        $pointsEarned = (int) round($basePoints * $cashRatio); // No points on points-funded portion

        if ($pointsEarned <= 0) {
            return;
        }

        // Available after 7 days of payment and delivery
        $availableAt = $deliveryTime->copy()->addDays(7);
        $expiresAt = $availableAt->copy()->addDays(60); // 60 days validity

        RewardPointsLedger::create([
            'user_id' => $referrer->id,
            'order_id' => $order->id,
            'points' => $pointsEarned,
            'points_remaining' => $pointsEarned,
            'point_type' => 'student_referral',
            'status' => 'pending',
            'available_at' => $availableAt,
            'expires_at' => $expiresAt,
            'description' => "Student Referral Reward from {$buyer->name} for Order #{$order->order_id}",
            'metadata' => [
                'referred_user_id' => $buyer->id,
                'referred_name' => $buyer->name,
                'points_per_unit' => $pointsPerUnit,
                'quantity' => $quantity,
                'cash_ratio' => $cashRatio,
            ],
        ]);
    }

    /**
     * Handle refunds: Reverses associated reward points and recalculates B2B targets
     */
    public static function handleOrderRefund(Order $order, float $refundAmount): void
    {
        $orderTotal = (float) $order->amount;
        if ($orderTotal <= 0 || $refundAmount <= 0) {
            return;
        }

        $ratio = min(1.0, $refundAmount / $orderTotal);

        DB::transaction(function () use ($order, $refundAmount, $ratio) {
            $records = RewardPointsLedger::where('order_id', $order->id)->get();

            foreach ($records as $record) {
                $pointsToReverse = (int) round($record->points * $ratio);
                if ($pointsToReverse <= 0) {
                    continue;
                }

                $user = User::find($record->user_id);
                if (!$user) {
                    continue;
                }

                if ($record->status === 'available') {
                    // Check if points are still unspent
                    if ($record->points_remaining >= $pointsToReverse) {
                        $record->points_remaining -= $pointsToReverse;
                        if ($record->points_remaining === 0) {
                            $record->status = 'reversed';
                        }
                        $record->save();
                    } else {
                        // Already spent points! Record negative balance offset against future earnings (do not deduct money)
                        $alreadySpent = $pointsToReverse - $record->points_remaining;
                        $record->points_remaining = 0;
                        $record->status = 'reversed';
                        $record->save();

                        RewardPointsLedger::create([
                            'user_id' => $user->id,
                            'order_id' => $order->id,
                            'points' => -$alreadySpent,
                            'points_remaining' => 0,
                            'point_type' => 'negative_offset',
                            'status' => 'reversed',
                            'description' => "Negative offset for already-redeemed points on refunded Order #{$order->order_id}",
                        ]);
                    }
                } elseif ($record->status === 'pending') {
                    $record->status = 'reversed';
                    $record->save();
                }
            }

            // If buyer has B2B qualification record:
            $buyer = $order->user;
            if ($buyer) {
                $qualification = B2bReferralQualification::where('referred_id', $buyer->id)->first();
                if ($qualification) {
                    $qualification->total_eligible_purchases = max(0, $qualification->total_eligible_purchases - $refundAmount);

                    // If refund drops purchases below Rs. 100,000, qualification is withdrawn
                    if ($qualification->total_eligible_purchases < $qualification->target_amount) {
                        $qualification->is_qualified = false;
                        if (now()->lte($qualification->qualifying_deadline)) {
                            $qualification->status = 'in_progress';
                        } else {
                            $qualification->status = 'expired';
                        }
                        $qualification->qualified_at = null;
                    }
                    $qualification->save();
                }
            }
        });
    }

    /**
     * Get complete referral summary for a user
     */
    public static function getReferralSummary(User $user): array
    {
        $pointsSummary = PointsService::getUserPointsBalance($user);
        $referralCode = self::ensureUserReferralCode($user);
        $referralLink = self::getReferralLink($user);

        // B2B Referrals Qualification Tracking
        $b2bQualifications = B2bReferralQualification::with('referred')
            ->where('referrer_id', $user->id)
            ->latest()
            ->get();

        // Direct Referrals list
        $referredUsers = User::where('referred_by', $user->id)
            ->latest('referral_registered_at')
            ->get();

        return [
            'referral_code' => $referralCode,
            'referral_link' => $referralLink,
            'points' => $pointsSummary,
            'b2b_qualifications' => $b2bQualifications,
            'referred_users' => $referredUsers,
            'total_referrals' => $referredUsers->count(),
        ];
    }

    /**
     * Get aggregate referral summary for Admin
     */
    public static function getAdminReferralSummary(?int $userId = null): array
    {
        $now = now();
        $ledgerQuery = RewardPointsLedger::whereIn('point_type', ['b2b_referral', 'student_referral', 'reversal', 'negative_offset']);
        $qualQuery = B2bReferralQualification::with(['referrer', 'referred'])->latest();
        $userQuery = User::whereNotNull('referred_by');

        if ($userId) {
            $ledgerQuery->where('user_id', $userId);
            $qualQuery->where('referrer_id', $userId);
            $userQuery->where('referred_by', $userId);
        }

        $available = (int) (clone $ledgerQuery)
            ->where('status', 'available')
            ->where('points_remaining', '>', 0)
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->sum('points_remaining');

        $pending = (int) (clone $ledgerQuery)
            ->where('status', 'pending')
            ->sum('points');

        $held = (int) (clone $ledgerQuery)
            ->where('status', 'held')
            ->sum('points');

        $redeemed = (int) abs((clone $ledgerQuery)
            ->where('point_type', 'redemption')
            ->sum('points'));

        $totalEarned = (int) (clone $ledgerQuery)
            ->where('points', '>', 0)
            ->whereIn('status', ['available', 'pending', 'held', 'redeemed'])
            ->sum('points');

        $b2bQualifications = $qualQuery->get();
        $totalReferrals = $userQuery->count();

        $usersList = User::whereIn('account_type', ['agent', 'reseller_agent', 'student'])
            ->select('id', 'name', 'email', 'account_type', 'user_id')
            ->orderBy('name')
            ->get();

        return [
            'is_admin_view' => true,
            'points' => [
                'net_available_points' => $available,
                'pending_points' => $pending,
                'held_points' => $held,
                'redeemed_points' => $redeemed,
                'total_earned_points' => $totalEarned,
                'next_expiring_points' => 0,
            ],
            'b2b_qualifications' => $b2bQualifications,
            'total_referrals' => $totalReferrals,
            'users_list' => $usersList,
        ];
    }
}
