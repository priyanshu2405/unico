<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Credit funds to user wallet (Secure)
     */
    public static function credit(User $user, float $amount, string $source, string $description, bool $isWithdrawable = true): WalletLedger
    {
        return DB::transaction(function () use ($user, $amount, $source, $description, $isWithdrawable) {
            // Row-level lock to prevent double-spending
            $userObj = User::where('id', $user->id)->lockForUpdate()->first();
            
            $userObj->wallet_balance += $amount;
            if ($isWithdrawable) {
                $userObj->withdrawable_balance += $amount;
            }
            $userObj->save();

            $transactionId = 'TXN_' . strtoupper(Str::random(12));

            return WalletLedger::create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'type' => 'credit',
                'amount' => $amount,
                'source' => $source,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * Debit funds from user wallet (Secure)
     */
    public static function debit(User $user, float $amount, string $source, string $description): WalletLedger
    {
        return DB::transaction(function () use ($user, $amount, $source, $description) {
            $userObj = User::where('id', $user->id)->lockForUpdate()->first();

            if ($userObj->wallet_balance < $amount) {
                throw new \Exception("Insufficient wallet balance.");
            }

            $userObj->wallet_balance -= $amount;
            
            // Adjust withdrawable balance (cannot go below 0)
            $userObj->withdrawable_balance = max(0, $userObj->withdrawable_balance - $amount);
            $userObj->save();

            $transactionId = 'TXN_' . strtoupper(Str::random(12));

            return WalletLedger::create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'type' => 'debit',
                'amount' => $amount,
                'source' => $source,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
