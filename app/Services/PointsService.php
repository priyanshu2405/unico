<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\RewardPointsLedger;
use App\Models\B2bReferralQualification;
use App\Notifications\PointsExpiryReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointsService
{
    /**
     * Get real-time categorized points balance for a user
     */
    public static function getUserPointsBalance(User $user): array
    {
        $now = now();

        // Expire any overdue available points dynamically first
        self::expireOverduePointsForUser($user, $now);

        // Release any matured pending points
        self::releaseMaturedPointsForUser($user, $now);

        // Available points (sum of remaining points in available ledgers)
        $available = (int) RewardPointsLedger::where('user_id', $user->id)
            ->where('status', 'available')
            ->where('points_remaining', '>', 0)
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->sum('points_remaining');

        // Pending points
        $pending = (int) RewardPointsLedger::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('points');

        // Redeemed points
        $redeemed = (int) abs(RewardPointsLedger::where('user_id', $user->id)
            ->where('point_type', 'redemption')
            ->sum('points'));

        // Reversed / Cancelled points
        $reversed = (int) abs(RewardPointsLedger::where('user_id', $user->id)
            ->whereIn('status', ['reversed', 'cancelled'])
            ->sum('points'));

        // Expired points
        $expired = (int) RewardPointsLedger::where('user_id', $user->id)
            ->where('status', 'expired')
            ->sum('points');

        // Negative offset from refunds where points were already spent
        $negativeOffset = (int) abs(RewardPointsLedger::where('user_id', $user->id)
            ->where('point_type', 'negative_offset')
            ->sum('points'));

        // Net spendable available points (cannot go below 0)
        $netAvailable = max(0, $available - $negativeOffset);

        // Next expiring points & date
        $nextExpiring = RewardPointsLedger::where('user_id', $user->id)
            ->where('status', 'available')
            ->where('points_remaining', '>', 0)
            ->where('expires_at', '>', $now)
            ->orderBy('expires_at', 'asc')
            ->first();

        return [
            'available_points' => $available,
            'pending_points' => $pending,
            'redeemed_points' => $redeemed,
            'reversed_points' => $reversed,
            'expired_points' => $expired,
            'negative_offset' => $negativeOffset,
            'net_available_points' => $netAvailable,
            'next_expiring_points' => $nextExpiring ? $nextExpiring->points_remaining : 0,
            'next_expiring_date' => $nextExpiring ? $nextExpiring->expires_at : null,
        ];
    }

    /**
     * Calculate maximum redeemable points for an order
     * Minimum 100 points, combined max up to 10% of eligible voucher subtotal
     */
    public static function calculateMaxRedeemablePoints(float $voucherSubtotalAfterDiscounts, User $user): array
    {
        $balance = self::getUserPointsBalance($user);
        $netAvailable = $balance['net_available_points'];

        // Maximum 10% adjustment: 1 point = PKR 1
        $tenPercentCap = (int) floor($voucherSubtotalAfterDiscounts * 0.10);

        // Minimum redemption rule is 100 points
        if ($netAvailable < 100 || $tenPercentCap < 100) {
            return [
                'can_redeem' => false,
                'min_points' => 100,
                'max_points' => 0,
                'ten_percent_cap' => $tenPercentCap,
                'available_points' => $netAvailable,
                'reason' => $netAvailable < 100
                    ? 'A minimum of 100 available reward points is required to redeem.'
                    : 'Order subtotal is too low for the 100 points minimum (max 10% adjustment applies).',
            ];
        }

        $maxRedeemable = min($netAvailable, $tenPercentCap);

        return [
            'can_redeem' => true,
            'min_points' => 100,
            'max_points' => $maxRedeemable,
            'ten_percent_cap' => $tenPercentCap,
            'available_points' => $netAvailable,
            'reason' => 'Eligible for points redemption up to 10% of subtotal.',
        ];
    }

    /**
     * Deduct points from user's ledger using FIFO (earliest-expiring available points first)
     */
    public static function redeemPoints(User $user, int $pointsToRedeem, Order $order): void
    {
        if ($pointsToRedeem <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $pointsToRedeem, $order) {
            $now = now();
            $availableLedgers = RewardPointsLedger::where('user_id', $user->id)
                ->where('status', 'available')
                ->where('points_remaining', '>', 0)
                ->where(function ($q) use ($now) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                })
                ->orderBy('expires_at', 'asc') // FIFO earliest expiry first
                ->lockForUpdate()
                ->get();

            $pointsLeftToDeduct = $pointsToRedeem;

            foreach ($availableLedgers as $ledger) {
                if ($pointsLeftToDeduct <= 0) {
                    break;
                }

                $deduct = min($ledger->points_remaining, $pointsLeftToDeduct);
                $ledger->points_remaining -= $deduct;
                if ($ledger->points_remaining === 0) {
                    $ledger->status = 'redeemed';
                }
                $ledger->save();

                $pointsLeftToDeduct -= $deduct;
            }

            // Create negative redemption ledger entry
            RewardPointsLedger::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'points' => -$pointsToRedeem,
                'points_remaining' => 0,
                'point_type' => 'redemption',
                'status' => 'redeemed',
                'description' => "Redeemed {$pointsToRedeem} points on Order #{$order->order_id}",
            ]);
        });
    }

    /**
     * Expire overdue points for a specific user
     */
    public static function expireOverduePointsForUser(User $user, Carbon $now): void
    {
        RewardPointsLedger::where('user_id', $user->id)
            ->where('status', 'available')
            ->where('points_remaining', '>', 0)
            ->where('expires_at', '<=', $now)
            ->update([
                'status' => 'expired',
                'points_remaining' => 0,
            ]);
    }

    /**
     * Release matured points for a specific user
     */
    public static function releaseMaturedPointsForUser(User $user, Carbon $now): void
    {
        $pendingRecords = RewardPointsLedger::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('available_at', '<=', $now)
            ->get();

        foreach ($pendingRecords as $record) {
            // For B2B referral, confirm buyer has qualified
            if ($record->point_type === 'b2b_referral') {
                $meta = $record->metadata ?? [];
                $buyerId = $meta['referred_user_id'] ?? null;
                if ($buyerId) {
                    $qual = B2bReferralQualification::where('referred_id', $buyerId)->first();
                    if (!$qual || !$qual->is_qualified) {
                        continue; // Still pending qualification
                    }
                }
            }

            $record->status = 'available';
            $record->save();
        }
    }

    /**
     * Process scheduled points release, expiry, and reminder notifications system-wide
     */
    public static function processScheduledPoints(): void
    {
        $now = now();

        // 1. Mark overdue points as expired
        $expiredCount = RewardPointsLedger::where('status', 'available')
            ->where('points_remaining', '>', 0)
            ->where('expires_at', '<=', $now)
            ->update([
                'status' => 'expired',
                'points_remaining' => 0,
            ]);

        // 2. Release matured pending points
        $pending = RewardPointsLedger::where('status', 'pending')
            ->where('available_at', '<=', $now)
            ->get();

        foreach ($pending as $record) {
            if ($record->point_type === 'b2b_referral') {
                $meta = $record->metadata ?? [];
                $buyerId = $meta['referred_user_id'] ?? null;
                if ($buyerId) {
                    $qual = B2bReferralQualification::where('referred_id', $buyerId)->first();
                    if (!$qual || !$qual->is_qualified) {
                        continue;
                    }
                }
            }

            $record->status = 'available';
            $record->save();
        }

        // 3. Cancel unfulfilled B2B introductory referral points past their 3-month deadline
        $expiredB2b = B2bReferralQualification::where('status', 'in_progress')
            ->where('qualifying_deadline', '<=', $now)
            ->where('is_qualified', false)
            ->get();

        foreach ($expiredB2b as $qual) {
            $qual->status = 'expired';
            $qual->save();

            // Cancel any pending B2B points for this referrer from this buyer
            $records = RewardPointsLedger::where('user_id', $qual->referrer_id)
                ->where('point_type', 'b2b_referral')
                ->where('status', 'pending')
                ->get();

            foreach ($records as $rec) {
                $meta = $rec->metadata ?? [];
                if (($meta['referred_user_id'] ?? null) == $qual->referred_id) {
                    $rec->status = 'cancelled';
                    $rec->points_remaining = 0;
                    $rec->save();
                }
            }
        }

        // 4. Expiry notifications (30 days and 7 days beforehand)
        self::sendExpiryNotifications($now);
    }

    /**
     * Send points expiry notifications 30 days and 7 days beforehand
     */
    protected static function sendExpiryNotifications(Carbon $now): void
    {
        // 30 days threshold
        $threshold30 = $now->copy()->addDays(30);
        $due30 = RewardPointsLedger::where('status', 'available')
            ->where('points_remaining', '>', 0)
            ->where('expiry_notified_30d', false)
            ->whereBetween('expires_at', [$now, $threshold30])
            ->get();

        foreach ($due30 as $ledger) {
            $user = User::find($ledger->user_id);
            if ($user) {
                try {
                    $daysLeft = (int) max(1, $now->diffInDays($ledger->expires_at));
                    $user->notify(new PointsExpiryReminderNotification($ledger->points_remaining, $daysLeft, $ledger->expires_at));
                } catch (\Exception $e) {
                    Log::error("Points expiry notice error: " . $e->getMessage());
                }
            }
            $ledger->expiry_notified_30d = true;
            $ledger->save();
        }

        // 7 days threshold
        $threshold7 = $now->copy()->addDays(7);
        $due7 = RewardPointsLedger::where('status', 'available')
            ->where('points_remaining', '>', 0)
            ->where('expiry_notified_7d', false)
            ->whereBetween('expires_at', [$now, $threshold7])
            ->get();

        foreach ($due7 as $ledger) {
            $user = User::find($ledger->user_id);
            if ($user) {
                try {
                    $daysLeft = (int) max(1, $now->diffInDays($ledger->expires_at));
                    $user->notify(new PointsExpiryReminderNotification($ledger->points_remaining, $daysLeft, $ledger->expires_at));
                } catch (\Exception $e) {
                    Log::error("Points 7-day expiry notice error: " . $e->getMessage());
                }
            }
            $ledger->expiry_notified_7d = true;
            $ledger->save();
        }
    }
}
