<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\InventoryVoucher;
use App\Models\Order;

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

        // Voucher Purchases Summery (USD / GBP)
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
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

        if ($request->ajax()) {
            return view('dashboard.purches.partials.purches-table', compact('purchases'))->render();
        }

        return view('dashboard.purches.purches-report', compact('purchases'));
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
            $query->where('currency', $request->currency);
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
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'SKU ID', 'Date', 'Brand Name', 'Currency', 'Country/Region', 
            'Voucher Variant', 'Voucher Type', 'Purchase Invoice No.', 
            'Total Quantity', 'Purchase Value', 'Taxes', 'Per Unit Price'
        ];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $record) {
                fputcsv($file, [
                    $record->sku_id,
                    $record->purchase_date ? $record->purchase_date->format('Y-m-d') : '',
                    $record->brand_name,
                    $record->currency,
                    $record->country_region,
                    $record->voucher_variant,
                    $record->voucher_type,
                    $record->purchase_invoice_no,
                    $record->quantity,
                    $record->purchase_value,
                    $record->taxes,
                    $record->purchase_value_per_unit,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
