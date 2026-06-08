<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Vouchar;
use App\Models\User;
use App\Models\InventoryVoucher;
use App\Models\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total_sales' => Order::where('status', 'delivered')->sum('amount') ?? 0,
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_vouchers' => Vouchar::count(),
            'total_users' => User::count(),
        ];

        $recent_orders = Order::with(['user', 'voucher'])->latest()->limit(5)->get();

        return view('dashboard.reports.index', compact('stats', 'recent_orders'));
    }

    public function stockReport(Request $request)
    {
        $query = InventoryVoucher::query();

        // Filters
        if ($request->filled('sku_id')) {
            $query->where('sku_id', 'like', '%' . $request->sku_id . '%');
        }

        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        if ($request->filled('country_region')) {
            $query->whereJsonContains('country_region', $request->country_region);
        }

        // Date range handling
        $startDate = null;
        $endDate = null;
        $hasDateFilter = $request->filled('period_type') || $request->filled('filter_date');

        if ($hasDateFilter) {
            $baseDate = $request->filled('filter_date') ? Carbon::parse($request->filter_date) : now();
            if ($request->period_type == 'weekly') {
                $startDate = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
                $endDate = $baseDate->copy()->endOfWeek(Carbon::SUNDAY);
            } else {
                // Default to monthly if filtered but no type or explicit monthly
                $startDate = $baseDate->copy()->startOfMonth();
                $endDate = $baseDate->copy()->endOfMonth();
            }
        }

        $perPage = $request->get('per_page', 15);
        $stock_data = $query->paginate($perPage)->withQueryString();

        foreach ($stock_data as $item) {
            $unit_cost = (float) $item->purchase_value_per_unit;
            $unit_taxes = $item->purchased_qty > 0 ? (float) $item->taxes / $item->purchased_qty : 0;

            // 1. Opening Stock - ONLY IF Filter is applied
            $opening_qty = 0;
            if ($hasDateFilter && $startDate) {
                $purchasedBefore = ($item->purchase_date && Carbon::parse($item->purchase_date)->lt($startDate)) ? (int)$item->purchased_qty : 0;
                $soldBefore = (int) Order::where('voucher_id', $item->sku_id)
                    ->where('status', 'delivered')
                    ->where('created_at', '<', $startDate)
                    ->sum('quantity');
                $opening_qty = max(0, $purchasedBefore - $soldBefore);
            }
            $item->opening_qty = $opening_qty;
            $item->opening_value = $opening_qty * $unit_cost;
            $item->opening_taxes = $opening_qty * $unit_taxes;

            // 2. Purchases (Additions) - Always show total initial purchase
            $purchase_qty = (int)$item->purchased_qty;
            $item->purchase_qty = $purchase_qty;
            $item->purchase_value_calc = $purchase_qty * $unit_cost;
            $item->purchase_taxes = $purchase_qty * $unit_taxes;

            // 3. Sold (Delivered) in this period
            $soldQuery = Order::where('voucher_id', $item->sku_id)->where('status', 'delivered');
            if ($startDate && $endDate) {
                $soldQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $item->sold_qty = (int) $soldQuery->sum('quantity');
            $item->sold_value = $soldQuery->sum('amount');
            $item->sold_taxes = $item->sold_qty * $unit_taxes;

            // 4. Stock Available (Current real-time balance)
            $item->in_stock_qty = $item->quantity;
            $item->in_stock_value = $item->in_stock_qty * $unit_cost;
            $item->in_stock_taxes = $item->in_stock_qty * $unit_taxes;

            // 5. Loss/Refund (Refunded items)
            $refundResults = RefundRequest::whereIn('status', ['approved', 'pending'])
                ->whereIn('order_id', function ($q) use ($item) {
                    $q->select('order_id')->from('orders')->where('voucher_id', $item->sku_id);
                });

            if ($startDate && $endDate) {
                $refundResults->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate])
                        ->orWhereBetween('processed_at', [$startDate, $endDate]);
                });
            }

            $refunds = $refundResults->get();
            $item->lost_qty = (int) Order::whereIn('order_id', $refunds->pluck('order_id'))->sum('quantity');
            $rate = (float)($item->currency_conversion_rate ?: 1);
            $item->lost_value = $refunds->sum('amount') / ($rate > 0 ? $rate : 1);
            $item->lost_taxes = $item->lost_qty * $unit_taxes;

            // 6. Closing Stock - ONLY IF Filter is applied
            $closing_qty = 0;
            if ($hasDateFilter && $endDate && !$endDate->isFuture()) {
                $closing_qty = $item->opening_qty + $item->purchase_qty - $item->sold_qty - $item->lost_qty;
            }
            $item->closing_qty = $closing_qty;
            $item->closing_value = $closing_qty * $unit_cost;
            $item->closing_taxes = $closing_qty * $unit_taxes;
        }

        if ($request->ajax()) {
            return view('dashboard.reports.partials.stock-table', compact('stock_data'))->render();
        }

        $countries = InventoryVoucher::whereNotNull('country_region')->get()->pluck('country_region')->flatten()->unique()->filter()->sort();

        return view('dashboard.reports.stock', compact('stock_data', 'countries'));
    }

    public function profitAndLossReport(Request $request)
    {
        $query = InventoryVoucher::query();

        if ($request->filled('sku_id')) {
            $query->where('sku_id', 'like', '%' . $request->sku_id . '%');
        }

        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        if ($request->filled('country_region')) {
            $query->whereJsonContains('country_region', $request->country_region);
        }

        $startDate = null;
        $endDate = null;
        $hasDateFilter = $request->filled('period_type') || $request->filled('filter_date');

        if ($hasDateFilter) {
            $baseDate = $request->filled('filter_date') ? Carbon::parse($request->filter_date) : now();
            if ($request->period_type == 'weekly') {
                $startDate = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
                $endDate = $baseDate->copy()->endOfWeek(Carbon::SUNDAY);
            } else {
                $startDate = $baseDate->copy()->startOfMonth();
                $endDate = $baseDate->copy()->endOfMonth();
            }
        }

        $perPage = $request->get('per_page', 15);
        $pl_data = $query->paginate($perPage)->withQueryString();

        foreach ($pl_data as $item) {
            $unit_cost = (float) $item->purchase_value_per_unit;
            $unit_taxes = $item->purchased_qty > 0 ? (float) $item->taxes / $item->purchased_qty : 0;

            // 1. Sold in period
            $soldQuery = Order::where('voucher_id', $item->sku_id)->where('status', 'delivered');
            if ($startDate && $endDate) {
                $soldQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $item->sold_qty = (int) $soldQuery->sum('quantity');

            $rate = (float)($item->currency_conversion_rate ?: 1);
            $item->sold_value_raw = $soldQuery->sum('amount'); // Transaction currency
            $item->sold_value = $item->sold_value_raw / ($rate > 0 ? $rate : 1); // Local currency
            $item->sold_taxes = $item->sold_qty * $unit_taxes;

            // 2. Purchases - Total for this SKU record
            $item->purchase_qty = (int)$item->purchased_qty;
            $item->purchase_value_calc = $item->purchase_qty * $unit_cost;
            $item->purchase_taxes_calc = $item->purchase_qty * $unit_taxes;

            // 3. Lost/Refund
            $refundResultsPL = RefundRequest::whereIn('status', ['approved', 'pending'])
                ->whereIn('order_id', function ($q) use ($item) {
                    $q->select('order_id')->from('orders')->where('voucher_id', $item->sku_id);
                });

            if ($startDate && $endDate) {
                $refundResultsPL->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate])
                        ->orWhereBetween('processed_at', [$startDate, $endDate]);
                });
            }

            $refundsPL = $refundResultsPL->get();
            $item->lost_qty = (int) Order::whereIn('order_id', $refundsPL->pluck('order_id'))->sum('quantity');
            $item->lost_value_raw = $refundsPL->sum('amount'); // Transaction currency
            $item->lost_value = $item->lost_value_raw / ($rate > 0 ? $rate : 1); // Local currency
            $item->lost_taxes = $item->lost_qty * $unit_taxes;

            // 4. Profit/Loss
            // Profit Qty = Sold Qty - Lost Qty (since lost/refunded items aren't contributing to profit)
            $item->profit_qty = max(0, $item->sold_qty - $item->lost_qty);

            // Cost of sold items
            $cost_of_sold = $item->sold_qty * $unit_cost;

            // Profit Value = (Sold Value in Local) - (Cost of Sold in Local) - (Refund Value in Local)
            $item->profit_value = $item->sold_value - $cost_of_sold - $item->lost_value;
            $item->profit_taxes = 0;
        }

        if ($request->ajax()) {
            return view('dashboard.reports.partials.profit-loss-table', compact('pl_data'))->render();
        }

        $countries = InventoryVoucher::whereNotNull('country_region')->get()->pluck('country_region')->flatten()->unique()->filter()->sort();

        return view('dashboard.reports.profit-loss', compact('pl_data', 'countries'));
    }

    public function grossMarginReport()
    {
        return view('dashboard.reports.gross-margin');
    }
    public function distributorMarginReport()
    {
        return view('dashboard.reports.distributor-margin');
    }
    public function platformFeeReport()
    {
        return view('dashboard.reports.platform-fee');
    }
    public function operationalExpenseReport()
    {
        return view('dashboard.reports.operational-expense');
    }

    public function exportStockReport(Request $request)
    {
        $query = InventoryVoucher::query();
        if ($request->filled('sku_id')) {
            $query->where('sku_id', 'like', '%' . $request->sku_id . '%');
        }
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }
        if ($request->filled('country_region')) {
            $query->whereJsonContains('country_region', $request->country_region);
        }

        // Date range handling
        $startDate = null;
        $endDate = null;
        $hasDateFilter = $request->filled('period_type') || $request->filled('filter_date');

        if ($hasDateFilter) {
            $baseDate = $request->filled('filter_date') ? Carbon::parse($request->filter_date) : now();
            if ($request->period_type == 'weekly') {
                $startDate = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
                $endDate = $baseDate->copy()->endOfWeek(Carbon::SUNDAY);
            } else {
                $startDate = $baseDate->copy()->startOfMonth();
                $endDate = $baseDate->copy()->endOfMonth();
            }
        }

        $records = $query->get();

        $filename = "stock_report_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sr. No.',
            'SKU ID',
            'Voucher Type',
            'Opening Qty',
            'Opening Value',
            'Opening Taxes',
            'Purchases Qty',
            'Purchases Value',
            'Purchases Taxes',
            'Available Qty',
            'Available Value',
            'Available Taxes',
            'Sold Qty',
            'Sold Value',
            'Sold Taxes',
            'Loss/Refund Qty',
            'Loss/Refund Value',
            'Loss/Refund Taxes',
            'Closing Qty',
            'Closing Value',
            'Closing Taxes'
        ];

        $callback = function () use ($records, $columns, $startDate, $endDate, $hasDateFilter) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $index => $item) {
                $unit_cost = (float) $item->purchase_value_per_unit;
                $unit_taxes = $item->purchased_qty > 0 ? (float) $item->taxes / $item->purchased_qty : 0;

                // 1. Opening - ONLY IF Filter is applied
                $opening_qty = 0;
                if ($hasDateFilter && $startDate) {
                    $purchasedBefore = ($item->purchase_date && Carbon::parse($item->purchase_date)->lt($startDate)) ? (int)$item->purchased_qty : 0;
                    $soldBefore = (int) Order::where('voucher_id', $item->sku_id)
                        ->where('status', 'delivered')
                        ->where('created_at', '<', $startDate)
                        ->sum('quantity');
                    $opening_qty = max(0, $purchasedBefore - $soldBefore);
                }

                // 2. Purchases (Additions) - Always show total initial purchase
                $purchase_qty = (int)$item->purchased_qty;

                // 3. Sold (Delivered) in this period
                $soldQuery = Order::where('voucher_id', $item->sku_id)->where('status', 'delivered');
                if ($startDate && $endDate) {
                    $soldQuery->whereBetween('created_at', [$startDate, $endDate]);
                }
                $sold_qty = (int) $soldQuery->sum('quantity');
                $sold_value = $soldQuery->sum('amount');
                $sold_taxes = $sold_qty * $unit_taxes;

                // 4. Stock Available (Current real-time balance)
                $in_stock_qty = $item->quantity;

                // 5. Loss/Refund (Refunded items)
                $refundedOrderIds = Order::where('voucher_id', $item->sku_id)->pluck('order_id');
                $refundQuery = RefundRequest::whereIn('order_id', $refundedOrderIds)
                    ->whereIn('status', ['approved', 'pending']);
                if ($startDate && $endDate) {
                    $refundQuery->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [$startDate, $endDate])
                            ->orWhereBetween('processed_at', [$startDate, $endDate]);
                    });
                }
                $lost_qty = (int) Order::whereIn('order_id', $refundQuery->pluck('order_id'))->sum('quantity');
                $rate = (float)($item->currency_conversion_rate ?: 1);
                $lost_value = $refundQuery->sum('amount') / ($rate > 0 ? $rate : 1);
                $lost_taxes = $lost_qty * $unit_taxes;

                // 6. Closing Stock - ONLY IF Filter is applied
                $closing_qty = 0;
                if ($hasDateFilter && $endDate && !$endDate->isFuture()) {
                    $closing_qty = $opening_qty + $purchase_qty - $sold_qty - $lost_qty;
                }

                fputcsv($file, [
                    $index + 1,
                    $item->sku_id,
                    $item->voucher_type,
                    $opening_qty,
                    $item->local_currency . ' ' . number_format($opening_qty * $unit_cost, 2, '.', ''),
                    $item->local_currency . ' ' . number_format($opening_qty * $unit_taxes, 2, '.', ''),
                    $purchase_qty,
                    $item->local_currency . ' ' . number_format($purchase_qty * $unit_cost, 2, '.', ''),
                    $item->local_currency . ' ' . number_format($purchase_qty * $unit_taxes, 2, '.', ''),
                    $in_stock_qty,
                    $item->local_currency . ' ' . number_format($in_stock_qty * $unit_cost, 2, '.', ''),
                    $item->local_currency . ' ' . number_format($in_stock_qty * $unit_taxes, 2, '.', ''),
                    $sold_qty,
                    $item->currency . ' ' . number_format($sold_value, 2, '.', ''),
                    $item->currency . ' ' . number_format($sold_taxes, 2, '.', ''),
                    $lost_qty,
                    $item->local_currency . ' ' . number_format($lost_value, 2, '.', ''),
                    $item->local_currency . ' ' . number_format($lost_taxes, 2, '.', ''),
                    $closing_qty,
                    $item->local_currency . ' ' . number_format($closing_qty * $unit_cost, 2, '.', ''),
                    $item->local_currency . ' ' . number_format($closing_qty * $unit_taxes, 2, '.', '')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportProfitLossReport(Request $request)
    {
        $query = InventoryVoucher::query();
        if ($request->filled('sku_id')) {
            $query->where('sku_id', 'like', '%' . $request->sku_id . '%');
        }
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }
        if ($request->filled('country_region')) {
            $query->whereJsonContains('country_region', $request->country_region);
        }

        $startDate = null;
        $endDate = null;
        $hasDateFilter = $request->filled('period_type') || $request->filled('filter_date');

        if ($hasDateFilter) {
            $baseDate = $request->filled('filter_date') ? Carbon::parse($request->filter_date) : now();
            if ($request->period_type == 'weekly') {
                $startDate = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
                $endDate = $baseDate->copy()->endOfWeek(Carbon::SUNDAY);
            } else {
                $startDate = $baseDate->copy()->startOfMonth();
                $endDate = $baseDate->copy()->endOfMonth();
            }
        }

        $records = $query->get();

        $filename = "profit_loss_report_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sr. No.',
            'SKU ID',
            'Voucher Type',
            'Sold Qty',
            'Sold Value',
            'Sold Taxes',
            'Purchase Qty',
            'Purchase Value',
            'Purchase Taxes',
            'Lost Qty',
            'Lost Value',
            'Lost Taxes',
            'Profit Qty',
            'Profit Value',
            'Profit Taxes'
        ];

        $callback = function () use ($records, $columns, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $index => $item) {
                $unit_cost = (float) $item->purchase_value_per_unit;
                $unit_taxes = $item->purchased_qty > 0 ? (float) $item->taxes / $item->purchased_qty : 0;

                // 1. Sold in period
                $soldQuery = Order::where('voucher_id', $item->sku_id)->where('status', 'delivered');
                if ($startDate && $endDate) {
                    $soldQuery->whereBetween('created_at', [$startDate, $endDate]);
                }
                $sold_qty = (int) $soldQuery->sum('quantity');
                $sold_value = $soldQuery->sum('amount');
                $sold_taxes = $sold_qty * $unit_taxes;

                // 2. Purchases
                $purchase_qty = (int)$item->purchased_qty;
                $purchase_value = $purchase_qty * $unit_cost;
                $purchase_taxes = $purchase_qty * $unit_taxes;

                // 3. Lost/Refund (Refunded items)
                $refundResultsPL = RefundRequest::whereIn('status', ['approved', 'pending'])
                    ->whereIn('order_id', function ($q) use ($item) {
                        $q->select('order_id')->from('orders')->where('voucher_id', $item->sku_id);
                    });

                if ($startDate && $endDate) {
                    $refundResultsPL->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [$startDate, $endDate])
                            ->orWhereBetween('processed_at', [$startDate, $endDate]);
                    });
                }

                $refundsPL = $refundResultsPL->get();
                $lost_qty = (int) Order::whereIn('order_id', $refundsPL->pluck('order_id'))->sum('quantity');
                $lost_value_raw = $refundsPL->sum('amount');
                $rate = (float)($item->currency_conversion_rate ?: 1);
                $lost_value = $lost_value_raw / ($rate > 0 ? $rate : 1);
                $lost_taxes = $lost_qty * $unit_taxes;

                // 4. Profit
                $profit_qty = max(0, $sold_qty - $lost_qty);
                $cost_of_sold = $sold_qty * $unit_cost;
                $rate = (float)($item->currency_conversion_rate ?: 1);
                $sold_value_local = $sold_value / ($rate > 0 ? $rate : 1);
                $profit_value = $sold_value_local - $cost_of_sold - $lost_value;

                fputcsv($file, [
                    $index + 1,
                    $item->sku_id,
                    $item->voucher_type,
                    $sold_qty,
                    $item->currency . ' ' . number_format($sold_value, 2, '.', ''),
                    $item->currency . ' ' . number_format($sold_taxes, 2, '.', ''),
                    $purchase_qty,
                    $item->local_currency . ' ' . number_format($purchase_value, 2, '.', ''),
                    $item->local_currency . ' ' . number_format($purchase_taxes, 2, '.', ''),
                    $lost_qty,
                    $item->local_currency . ' ' . number_format($lost_value, 2, '.', ''),
                    $item->local_currency . ' ' . number_format($lost_taxes, 2, '.', ''),
                    $profit_qty,
                    $item->local_currency . ' ' . number_format($profit_value, 2, '.', ''),
                    '0.00'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bankReport(Request $request)
    {
        $query = Order::query()->whereNotNull('bank_name');

        if ($request->filled('bank_name')) {
            $query->where('bank_name', 'like', '%' . $request->bank_name . '%');
        }

        $bank_data = $query->latest()->paginate(15);

        if ($request->ajax()) {
            return view('dashboard.reports.partials.bank-table', compact('bank_data'))->render();
        }

        return view('dashboard.reports.bank', compact('bank_data'));
    }

    public function exportBankReport(Request $request)
    {
        $query = Order::query()->whereNotNull('bank_name');
        if ($request->filled('bank_name')) {
            $query->where('bank_name', 'like', '%' . $request->bank_name . '%');
        }

        $records = $query->get();

        $filename = "bank_report_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sr. No.',
            'Bank ID',
            'Currency',
            'Country/Region',
            'Vouchers Sold',
            'Sale Value in Local Curr',
            'Total Credits',
            'Refunds',
            'Disputes',
            'Currency Conversion',
            'Sale Value in Buying Currency',
            'FX Gain/Loss'
        ];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $index => $record) {
                fputcsv($file, [
                    $index + 1,
                    $record->bank_name ?? 'BNK-' . ($index + 100),
                    $record->currency ?? 'USD',
                    $record->country_region ?? 'Global',
                    $record->quantity ?? 10,
                    number_format($record->amount_transferred ?? 500, 2),
                    number_format(($record->amount_transferred ?? 500) * 1.1, 2),
                    number_format(0, 2),
                    number_format(0, 2),
                    '1.00',
                    number_format($record->amount_transferred ?? 500, 2),
                    '+0.00'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function revenue()
    {
        $stats = [
            'total_revenue' => 15250.00,
            'available_balance' => 8420.50,
            'pending_payments' => 1250.00,
            'refund_amount' => 450.00,
        ];
        return view('dashboard.revenue.index', compact('stats'));
    }
}
