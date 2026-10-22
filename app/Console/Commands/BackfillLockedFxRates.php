<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackfillLockedFxRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-locked-fx-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting historical backfill for locked FX columns...');

        // 1. Backfill RefundRequests
        $this->info('Backfilling RefundRequests...');
        $refunds = \App\Models\RefundRequest::with('order')->whereNull('amount_in_base_currency')->get();
        foreach ($refunds as $refund) {
            $currency = $refund->order ? ($refund->order->currency ?? 'USD') : 'USD';
            
            // Try to extract from order if possible
            if ($refund->order && $refund->order->amount_in_base_currency !== null && (float)$refund->order->amount_in_base_currency > 0) {
                $orderAmt = (float)$refund->order->amount;
                $ratio = $orderAmt > 0 ? min(1.0, (float)$refund->amount / $orderAmt) : 1.0;
                $usd = (float)$refund->order->amount_in_base_currency * $ratio;
                $rate = $usd > 0 && $refund->amount > 0 ? $usd / $refund->amount : 1.0;
            } else {
                $conv = \App\Services\CurrencyService::convertToBase((float)$refund->amount, $currency);
                $usd = (float)$conv['base_amount'];
                $rate = (float)$conv['exchange_rate'];
            }
            
            $refund->update([
                'currency' => $currency,
                'exchange_rate_applied' => $rate,
                'amount_in_base_currency' => $usd,
            ]);
        }

        // 2. Backfill WalletLedger
        $this->info('Backfilling WalletLedger...');
        $ledgers = \App\Models\WalletLedger::whereNull('amount_usd')->get();
        foreach ($ledgers as $ledger) {
            // Historical wallet transactions were essentially stored as USD since balances are USD-based
            $ledger->update([
                'currency' => 'USD',
                'original_amount' => $ledger->amount,
                'exchange_rate_applied' => 1.0,
                'amount_usd' => $ledger->amount,
            ]);
        }

        // 3. Backfill InventoryVouchers
        $this->info('Backfilling InventoryVouchers...');
        $vouchers = \App\Models\InventoryVoucher::whereNull('purchase_value_usd')->get();
        foreach ($vouchers as $voucher) {
            $currency = $voucher->local_currency ?: ($voucher->currency ?: 'USD');
            $rate = (float)($voucher->currency_conversion_rate ?? 0);
            if ($rate <= 0) {
                $rate = (float)\App\Services\CurrencyService::getExchangeRate($currency, 'USD');
                if ($rate <= 0) $rate = 1.0;
                $voucher->currency_conversion_rate = $rate;
            }
            
            $voucher->update([
                'purchase_value_usd' => (float)$voucher->purchase_value * $rate,
                'purchase_value_per_unit_usd' => (float)$voucher->purchase_value_per_unit * $rate,
                'taxes_usd' => (float)$voucher->taxes * $rate,
            ]);
        }

        $this->info('Backfill completed successfully.');
    }
}
