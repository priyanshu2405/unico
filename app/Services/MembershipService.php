<?php

namespace App\Services;

use App\Models\User;
use App\Models\Membership;
use App\Models\InventoryVoucher;
use App\Models\PromotionalCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MembershipService
{
    /**
     * Activate or renew a membership for a user
     */
    public static function activateMembership(User $user, string $planType = 'monthly', ?string $paymentSource = 'wallet'): array
    {
        $planType = strtolower($planType);
        $fee = $planType === 'yearly' ? 15000.00 : 1500.00; // Rs. 1,500 monthly, Rs. 15,000 yearly
        $currency = 'PKR';

        // Calculate end date based on UK timezone (Europe/London) as per specification
        $nowUk = Carbon::now('Europe/London');

        if ($planType === 'monthly') {
            // Ending at 11:59 p.m. UK time on the last day of the current calendar month
            $expiresAt = $nowUk->copy()->endOfMonth()->setTime(23, 59, 59)->utc();
        } else {
            // 12 consecutive months
            $expiresAt = $nowUk->copy()->addYear()->endOfDay()->utc();
        }

        // If user already has an active membership
        $existing = Membership::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        return DB::transaction(function () use ($user, $planType, $fee, $currency, $expiresAt, $existing, $paymentSource) {
            // If paying via wallet:
            if ($paymentSource === 'wallet') {
                // Convert PKR fee to USD for wallet deduction
                $feeInUSD = CurrencyService::convertToBase($fee, 'PKR')['base_amount'];
                if ($user->wallet_balance < $feeInUSD) {
                    return [
                        'success' => false,
                        'message' => "Insufficient wallet balance to purchase {$planType} membership. Required: PKR " . number_format($fee, 2) . " (~$" . number_format($feeInUSD, 2) . " USD)",
                    ];
                }

                WalletService::debit(
                    $user,
                    $feeInUSD,
                    'membership_fee',
                    "Payment for UniCou {$planType} Membership (Rs. " . number_format($fee, 0) . ")"
                );
            }

            // Generate account-specific unique promo code
            $promoCode = 'MEM-' . strtoupper(substr($planType, 0, 1)) . '-' . strtoupper(Str::random(6));

            if ($existing) {
                // Extend or update existing membership
                $existing->status = 'expired';
                $existing->save();
            }

            $membership = Membership::create([
                'user_id' => $user->id,
                'plan_type' => $planType,
                'fee_paid' => $fee,
                'currency' => $currency,
                'discount_percent' => 1.00, // 1% discount
                'promo_code' => $promoCode,
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'status' => 'active',
                'auto_renew' => false,
                'total_discount_saved' => 0.00,
            ]);

            // Update user cache columns
            $user->update([
                'has_active_membership' => true,
                'membership_type' => $planType,
                'membership_expires_at' => $expiresAt,
                'membership_promo_code' => $promoCode,
            ]);

            return [
                'success' => true,
                'message' => "Congratulations! Your " . ucfirst($planType) . " Membership is now active until " . Carbon::parse($expiresAt)->format('d M Y H:i') . " UTC. You get an immediate 1% discount on all voucher purchases!",
                'membership' => $membership,
            ];
        });
    }

    /**
     * Compute full order checkout breakdown including Membership discount, Promo Code, and Points Redemption
     */
    public static function calculateOrderPricing(
        User $user,
        InventoryVoucher $voucher,
        int $quantity,
        ?string $promoCode = null,
        int $pointsToRedeem = 0
    ): array {
        $quantity = max(1, $quantity);

        // Determine base unit price for user
        $unitPrice = (float) $voucher->purchase_value_per_unit;
        if ($user->isStudent() && $voucher->student_sale_price > 0) {
            $unitPrice = (float) $voucher->student_sale_price;
        } elseif (($user->isAgent() || $user->isResellerAgent()) && $voucher->agent_sale_price > 0) {
            $unitPrice = (float) $voucher->agent_sale_price;
        }

        $baseSubtotal = $unitPrice * $quantity;

        // 1. Membership Discount: Extra 1% on qualifying voucher subtotal
        $hasMembership = $user->hasActiveMembership();
        $membershipDiscount = 0.0;
        if ($hasMembership) {
            $membershipDiscount = round($baseSubtotal * 0.01, 2);
        }

        $subtotalAfterMembership = max(0, $baseSubtotal - $membershipDiscount);

        // 2. Promotional Code Discount
        $promoDiscount = 0.0;
        $promoObj = null;
        $promoMessage = '';

        if (!empty($promoCode)) {
            $cleanCode = strtoupper(trim($promoCode));

            // Check if promo code matches user's membership promo code
            if ($user->membership_promo_code === $cleanCode && $hasMembership) {
                // Already accounted for by membership discount
                $promoMessage = 'Membership 1% discount already active.';
            } else {
                $promoObj = PromotionalCode::where('code', $cleanCode)->first();
                if ($promoObj) {
                    $validation = $promoObj->isValidFor($user, $subtotalAfterMembership);
                    if ($validation['valid']) {
                        $promoDiscount = round($promoObj->calculateDiscount($subtotalAfterMembership), 2);
                        $promoMessage = "Code applied: {$promoObj->title}";
                    } else {
                        $promoMessage = $validation['message'];
                    }
                } else {
                    $promoMessage = 'Invalid promotional code.';
                }
            }
        }

        $subtotalAfterAllDiscounts = max(0, $subtotalAfterMembership - $promoDiscount);

        // 3. Reward Points Redemption (Up to 10% of subtotal after discounts, min 100 points)
        $pointsEligibility = PointsService::calculateMaxRedeemablePoints($subtotalAfterAllDiscounts, $user);
        $pointsRedemptionAmount = 0.0;
        $actualPointsRedeemed = 0;
        $isFullPointsRedemption = false;

        if ($pointsToRedeem > 0 && $pointsEligibility['can_redeem']) {
            $actualPointsRedeemed = min($pointsToRedeem, $pointsEligibility['max_points']);
            if ($actualPointsRedeemed >= 100) {
                // 1 point = Rs. 1 discount
                $pointsRedemptionAmount = (float) $actualPointsRedeemed;

                // Check if user utilized full 10% points adjustment allowance
                if ($actualPointsRedeemed >= $pointsEligibility['ten_percent_cap']) {
                    $isFullPointsRedemption = true;
                }
            } else {
                $actualPointsRedeemed = 0;
                $pointsRedemptionAmount = 0.0;
            }
        }

        // 4. Taxes & Charges (Calculated on post-discount amount)
        $unitTaxes = $voucher->purchased_qty > 0 ? (float) $voucher->taxes / $voucher->purchased_qty : 0;
        $taxAmount = round($unitTaxes * $quantity, 2);

        // 5. Final Payable Amount
        $payableVoucherAmount = max(0, $subtotalAfterAllDiscounts - $pointsRedemptionAmount);
        $totalPayable = round($payableVoucherAmount + $taxAmount, 2);

        // Currency conversions
        $voucherCurrency = CurrencyService::normalizeCurrencyCode($voucher->currency ?? 'PKR');
        $userCurrency = $user->currency ?: 'USD';

        $totalPayableInUSD = CurrencyService::convertToBase($totalPayable, $voucherCurrency)['base_amount'];
        $totalPayableInUserCurr = CurrencyService::convert($totalPayable, $voucherCurrency, $userCurrency);

        return [
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'base_subtotal' => $baseSubtotal,
            'has_membership' => $hasMembership,
            'membership_discount' => $membershipDiscount,
            'subtotal_after_membership' => $subtotalAfterMembership,
            'promo_code' => $promoObj ? $promoObj->code : null,
            'promo_discount' => $promoDiscount,
            'promo_message' => $promoMessage,
            'subtotal_after_discounts' => $subtotalAfterAllDiscounts,
            'points_eligibility' => $pointsEligibility,
            'points_redeemed' => $actualPointsRedeemed,
            'points_redemption_amount' => $pointsRedemptionAmount,
            'is_full_points_redemption' => $isFullPointsRedemption,
            'tax_amount' => $taxAmount,
            'total_payable' => $totalPayable,
            'currency' => $voucherCurrency,
            'total_payable_usd' => $totalPayableInUSD,
            'total_payable_user_curr' => $totalPayableInUserCurr,
            'user_currency' => $userCurrency,
        ];
    }
}
