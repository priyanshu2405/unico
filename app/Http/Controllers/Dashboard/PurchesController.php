<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\InventoryVoucher;
use App\Models\Order;
use App\Services\ProfitLossService;

class PurchesController extends Controller
{
    public function purchaseInvoice($order_id)
    {
        $order = Order::with(['inventoryVoucher', 'user'])->where('order_id', $order_id)->firstOrFail();
        
        // Ensure user can only view their own invoice
        if (!auth()->user()->isAdmin() && !auth()->user()->isManager() && $order->user_id !== auth()->id()) {
            abort(403);
        }

        return view('dashboard.purches.invoice', compact('order'));
    }

    public function purchesReport(Request $request)
    {
        $query = InventoryVoucher::query();

        // Date Wise Report (From - To)
        if ($request->filled('from_date')) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }

        // Filter using the currency paid for the stock purchase.
        if ($request->filled('currency')) {
            $query->where(function ($currencyQuery) use ($request) {
                $currencyQuery->where('local_currency', $request->currency)
                    ->orWhere(function ($legacyQuery) use ($request) {
                        $legacyQuery->whereNull('local_currency')->where('currency', $request->currency);
                    });
            });
        }

        // Brand Purchase Report
        if ($request->filled('brand_name')) {
            $query->where('brand_name', 'like', '%' . $request->brand_name . '%');
        }

        // Variant Purchase Report
        if ($request->filled('voucher_variant')) {
            $query->where('voucher_variant', 'like', '%' . $request->voucher_variant . '%');
        }

        // Exam Type wise Report Online/Center Base/
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        // Supplier-wise Purchase Report (UK / USA)
        if ($request->filled('country_region')) {
            $query->where('country_region', $request->country_region);
        }

        $perPage = $request->get('per_page', 10);
        $purchases = $query->latest()->paginate($perPage)->withQueryString();
        $currencies = \App\Helpers\CurrencyHelper::getAllCurrencies();

        if ($request->ajax()) {
            return view('dashboard.purches.partials.purches-table', compact('purchases', 'currencies'))->render();
        }

        return view('dashboard.purches.purches-report', compact('purchases', 'currencies'));
    }

    public function export(Request $request)
    {
        $query = InventoryVoucher::query();

        if ($request->filled('from_date')) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }
        if ($request->filled('currency')) {
            $query->where(function ($currencyQuery) use ($request) {
                $currencyQuery->where('local_currency', $request->currency)
                    ->orWhere(function ($legacyQuery) use ($request) {
                        $legacyQuery->whereNull('local_currency')->where('currency', $request->currency);
                    });
            });
        }
        if ($request->filled('brand_name')) {
            $query->where('brand_name', 'like', '%' . $request->brand_name . '%');
        }
        if ($request->filled('voucher_variant')) {
            $query->where('voucher_variant', 'like', '%' . $request->voucher_variant . '%');
        }
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }
        if ($request->filled('country_region')) {
            $query->where('country_region', $request->country_region);
        }

        $records = $query->latest()->get();

        $filename = "purchase_report_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sr. No.',
            'P.ID.',
            'Date',
            'Time',
            'Brand Name',
            'Purchase Currency',
            'Country/Region',
            'Voucher Variant',
            'Voucher Type',
            'Purchase Invoice No.',
            'Purchase Date',
            'Total Quantity',
            'Purchase Value (Local)',
            'Per Unit Price (Local)',
            'Taxes',
            'Exchange Rate (to USD)',
            'Purchase Value (USD)',
            'Per Unit Price (USD)',
            'Issue Date',
            'Expiry Date'
        ];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $index => $record) {
                $curr = $record->local_currency ?: ($record->currency ?: 'USD');
                $rate = ProfitLossService::getStockUploadRate($record);
                $purchaseValUsd = ProfitLossService::getInventoryPurchaseTotalBase($record);
                $unitPriceUsd = ProfitLossService::getInventoryUnitCostBase($record);

                fputcsv($file, [
                    $index + 1,
                    $record->sku_id,
                    $record->purchase_date ? $record->purchase_date->format('Y-m-d') : 'N/A',
                    $record->created_at ? $record->created_at->format('H:i:s') : 'N/A',
                    $record->brand_name,
                    $curr,
                    is_array($record->country_region) ? implode(', ', $record->country_region) : ($record->country_region ?: 'Global'),
                    $record->voucher_variant,
                    $record->voucher_type,
                    $record->purchase_invoice_no,
                    $record->purchase_date ? $record->purchase_date->format('Y-m-d') : 'N/A',
                    $record->purchased_qty,
                    number_format((float)$record->purchase_value, 2, '.', ''),
                    number_format((float)$record->purchase_value_per_unit, 2, '.', ''),
                    number_format((float)$record->taxes, 2, '.', ''),
                    number_format($rate, 6, '.', ''),
                    number_format($purchaseValUsd, 2, '.', ''),
                    number_format($unitPriceUsd, 2, '.', ''),
                    $record->created_at ? $record->created_at->format('Y-m-d') : 'N/A',
                    $record->expiry_date ? $record->expiry_date->format('Y-m-d') : 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
