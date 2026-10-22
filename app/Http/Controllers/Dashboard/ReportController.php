<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Vouchar;
use App\Models\User;
use App\Models\InventoryVoucher;
use App\Models\RefundRequest;
use App\Services\CurrencyService;
use App\Services\ProfitLossService;
use App\Helpers\CurrencyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total_sales' => Order::where('status', 'delivered')->get()->sum(fn($o) => CurrencyService::getOrderBaseAmount($o)) ?? 0,
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_vouchers' => InventoryVoucher::count(),
            'total_users' => User::count(),
        ];

        $recent_orders = Order::with(['user', 'inventoryVoucher'])->latest()->limit(5)->get();

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

        if ($request->filled('city')) {
            $query->whereJsonContains('city', $request->city);
        }

        // Date range handling
        $startDate = null;
        $endDate = null;
        $hasDateFilter = false;

        if ($request->filled('date_range')) {
            $hasDateFilter = true;
            $dates = explode(' to ', $request->date_range);
            if (count($dates) == 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
            } else {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();
            }
        } elseif ($request->filled('period_type') || $request->filled('filter_date')) {
            $hasDateFilter = true;
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
        $stock_data = $query->paginate($perPage)->withQueryString();

        foreach ($stock_data as $item) {
            $unit_cost = (float) $item->purchase_value_per_unit;
            $purch_curr = $item->local_currency ?: ($item->currency ?: 'USD');
            $sale_curr = $item->currency ?: 'USD';
            // Use pre-calculated locked USD columns, fallback to safe manual calc if backfill is missing
            $stockUploadRate = (float)($item->currency_conversion_rate ?? 0) ?: (float) CurrencyService::getExchangeRate($purch_curr, 'USD');
            $unit_taxes = $item->purchased_qty > 0 ? (float)($item->taxes_usd ?? ($item->taxes * $stockUploadRate)) / $item->purchased_qty : 0;

            $item->purchase_currency = $purch_curr;
            $item->sale_currency = $sale_curr;

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

            // 2. Purchases (Additions)
            $purchase_qty = (int)$item->purchased_qty;
            if ($hasDateFilter && $startDate) {
                $purchaseInPeriod = ($item->purchase_date && Carbon::parse($item->purchase_date)->between($startDate, $endDate)) ? (int)$item->purchased_qty : 0;
                $purchase_qty = $purchaseInPeriod;
            }
            $item->purchase_qty = $purchase_qty;
            $item->purchase_value_calc = $purchase_qty * $unit_cost;
            $item->purchase_taxes = $purchase_qty * $unit_taxes;

            // 3. Total Available (Opening + Purchases)
            $item->in_stock_qty = $opening_qty + $purchase_qty;
            $item->in_stock_value = $item->in_stock_qty * $unit_cost;
            $item->in_stock_taxes = $item->in_stock_qty * $unit_taxes;

            // 4. Gross Sold (Delivered) in this period
            $soldQuery = Order::where('voucher_id', $item->sku_id)->where('status', 'delivered');
            if ($startDate && $endDate) {
                $soldQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $soldOrders = $soldQuery->get();
            $gross_sold_qty = (int) $soldOrders->sum('quantity');

            // 5. Approved Loss/Refund (Only approved refunds are considered lost/returned)
            $refundResults = RefundRequest::where('status', 'approved')
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
            // Refund requests do not record physical voucher returns.
            $item->sold_qty = ProfitLossService::getActiveSoldQuantity($gross_sold_qty);

            // Sold Value based on delivered orders sales revenue
            $sold_val_usd = (float) $soldOrders->sum(function ($order) {
                return CurrencyService::getOrderBaseAmount($order);
            });
            $item->sold_val_usd = $sold_val_usd;
            $item->sold_value = CurrencyService::convert($sold_val_usd, 'USD', $sale_curr);
            $item->sold_taxes = (float) $soldOrders->sum('tax_amount');

            // Lost / Refunded items
            $lost_val_usd = (float) $refunds->sum(fn($refund) => ProfitLossService::getRefundBaseAmount($refund));
            $item->lost_qty = 0;
            $item->lost_val_usd = $lost_val_usd;
            $item->lost_value = CurrencyService::convert($lost_val_usd, 'USD', $sale_curr);
            $item->lost_taxes = (float) $refunds->sum(function ($refund) {
                $order = $refund->order;
                return $order ? (float) ($order->tax_amount ?? 0) * ProfitLossService::getRefundRatio($order, $refund) : 0.0;
            });

            // 6. Closing Stock = In Stock - Gross Delivered (Remaining stock available on hand)
            $item->closing_qty = max(0, $item->in_stock_qty - $gross_sold_qty);
            $item->closing_value = $item->closing_qty * $unit_cost;
            $item->closing_taxes = $item->closing_qty * $unit_taxes;

            $unit_cost_usd = (float)($item->purchase_value_per_unit_usd ?? ($unit_cost * $stockUploadRate));
            $item->opening_val_usd = $opening_qty * $unit_cost_usd;
            $item->purchase_val_usd = $purchase_qty * $unit_cost_usd;
            $item->in_stock_val_usd = $item->in_stock_qty * $unit_cost_usd;
            $item->closing_val_usd = $item->closing_qty * $unit_cost_usd;
        }

        if ($request->ajax()) {
            return view('dashboard.reports.partials.stock-table', compact('stock_data'))->render();
        }

        $countries = InventoryVoucher::whereNotNull('country_region')->get()->pluck('country_region')->flatten()->unique()->filter()->sort();
        $cities = InventoryVoucher::whereNotNull('city')->get()->pluck('city')->flatten()->unique()->filter()->sort();

        return view('dashboard.reports.stock', compact('stock_data', 'countries', 'cities'));
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

        // Calculate Standardized P&L summary across all filtered items
        $allFilteredVouchers = (clone $query)->get();
        $mgmtSummary = $this->calculateManagementSummary($allFilteredVouchers, $startDate, $endDate);

        $perPage = $request->get('per_page', 15);
        $pl_data = $query->paginate($perPage)->withQueryString();

        foreach ($pl_data as $item) {
            $unit_cost = (float) $item->purchase_value_per_unit;
            $unit_taxes = $item->purchased_qty > 0 ? (float) $item->taxes / $item->purchased_qty : 0;
            $curr = CurrencyService::normalizeCurrencyCode($item->currency ?: 'USD');
            $costCurrency = $item->local_currency ?: $curr;

            // FX-1 Stock upload rate — use PURCHASE currency, not sale currency
            $purchaseCurrency = $item->local_currency ?: $curr;
            $stockUploadRate = ProfitLossService::getStockUploadRate($item);
            $item->stock_upload_rate = $stockUploadRate;
            $unitCostUsd = ProfitLossService::getInventoryUnitCostBase($item);
            $item->unit_cost_usd = $unitCostUsd;

            // 1. Opening Stock (Value of unsold vouchers at start of period)
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
            $item->opening_val_usd = round($opening_qty * $unitCostUsd, 2);

            // 2. Purchases in period
            $purchase_qty = (int)$item->purchased_qty;
            if ($hasDateFilter && $startDate) {
                $purchaseInPeriod = ($item->purchase_date && Carbon::parse($item->purchase_date)->between($startDate, $endDate)) ? (int)$item->purchased_qty : 0;
                $purchase_qty = $purchaseInPeriod;
            }
            $item->purchase_qty = $purchase_qty;
            $item->purchase_val_usd = round($purchase_qty * $unitCostUsd, 2);

            // 3. Sold in period
            $soldQuery = Order::where('voucher_id', $item->sku_id)->where('status', 'delivered');
            if ($startDate && $endDate) {
                $soldQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $soldOrders = $soldQuery->get();
            $gross_sold_qty = (int) $soldOrders->sum('quantity');
            $item->sold_qty = $gross_sold_qty;

            // Revenue (Sale) = Total revenue generated from selling vouchers
            $item->revenue = round((float) $soldOrders->sum(function ($order) {
                return CurrencyService::getOrderBaseAmount($order);
            }), 2);
            $item->sold_taxes = (float) $soldOrders->sum(function ($order) {
                return CurrencyService::convertOrderAmountToBase($order, (float) $order->tax_amount);
            });

            // 4. Closing Stock = Value of unsold vouchers at end of period
            $in_stock_qty = $opening_qty + $purchase_qty;
            $item->closing_qty = max(0, $in_stock_qty - $gross_sold_qty);
            $item->closing_val_usd = round($item->closing_qty * $unitCostUsd, 2);

            // 5. Total COGS = Opening Stock + Purchases - Closing Stock
            $item->cogs = ProfitLossService::calculateCogs($item->opening_val_usd, $item->purchase_val_usd, $item->closing_val_usd);

            // 6. Gross Profit = Revenue - Total COGS
            $item->gross_profit = ProfitLossService::calculateGrossProfit($item->revenue, $item->cogs);

            // 7. Operating & Non-Operating Items
            // Less: Voucher Lost Expense (abnormal waste / expired vouchers / approved refund losses)
            $refundResultsPL = RefundRequest::where('status', 'approved')
                ->whereIn('order_id', function ($q) use ($item) {
                    $q->select('order_id')->from('orders')->where('voucher_id', $item->sku_id);
                });

            if ($startDate && $endDate) {
                $refundResultsPL->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate])
                        ->orWhereBetween('processed_at', [$startDate, $endDate]);
                });
            }

            $refundsPL = $refundResultsPL->with('order')->get();
            $refundLostUsd = (float) $refundsPL->sum(fn($refund) => ProfitLossService::getRefundBaseAmount($refund));

            $expiredLostUsd = 0.0;
            if ($item->is_expired || ($item->expiry_date && Carbon::parse($item->expiry_date)->isPast())) {
                if (!$hasDateFilter || ($item->expiry_date && Carbon::parse($item->expiry_date)->between($startDate, $endDate))) {
                    $expiredLostUsd = (int)$item->quantity * $unitCostUsd;
                }
            }
            $item->lost_expense = round($refundLostUsd + $expiredLostUsd, 2);

            // Add: Foreign Exchange (FX) Gain
            $grossFixedRevenue = (float) $soldOrders->sum(fn($order) => ProfitLossService::getOrderFixedBaseAmount($order));
            $item->fx_gain = round($item->revenue - $grossFixedRevenue, 2);

            // Net Profit = Gross Profit - Voucher Lost Expense
            $item->net_profit = ProfitLossService::calculateNetProfit($item->gross_profit, $item->lost_expense);

            // Backward compatibility aliases for view
            $item->sold_value = $item->revenue;
            $item->cost_of_sold = $item->cogs;
            $item->gross_profit_before_fx = $item->gross_profit;
            $item->fx_gain_loss = $item->fx_gain;
            $item->profit_value = $item->net_profit;
            $item->fixed_revenue = $grossFixedRevenue;
            $item->purchase_value_calc = $item->purchase_val_usd;
            $item->lost_value = $item->lost_expense;
            $item->lost_qty = 0;
            $item->profit_qty = ProfitLossService::getActiveSoldQuantity($gross_sold_qty);
        }

        if ($request->ajax()) {
            return view('dashboard.reports.partials.profit-loss-table', compact('pl_data', 'mgmtSummary'))->render();
        }

        $countries = InventoryVoucher::whereNotNull('country_region')->get()->pluck('country_region')->flatten()->unique()->filter()->sort();

        return view('dashboard.reports.profit-loss', compact('pl_data', 'countries', 'mgmtSummary'));
    }

    /**
     * Calculate Standardized Profit and Loss Account metrics.
     */
    private function calculateManagementSummary($vouchers, $startDate = null, $endDate = null)
    {
        $totalRevenue = 0.0;
        $totalOpeningStock = 0.0;
        $totalPurchases = 0.0;
        $totalClosingStock = 0.0;
        $totalLostExpense = 0.0;
        $totalFxGain = 0.0;

        foreach ($vouchers as $item) {
            $unitCostUsd = ProfitLossService::getInventoryUnitCostBase($item);

            // 1. Opening Stock (Value of unsold vouchers at start of period)
            $opening_qty = 0;
            if ($startDate) {
                $purchasedBefore = ($item->purchase_date && Carbon::parse($item->purchase_date)->lt($startDate)) ? (int)$item->purchased_qty : 0;
                $soldBefore = (int) Order::where('voucher_id', $item->sku_id)
                    ->where('status', 'delivered')
                    ->where('created_at', '<', $startDate)
                    ->sum('quantity');
                $opening_qty = max(0, $purchasedBefore - $soldBefore);
            }
            $openingValUsd = $opening_qty * $unitCostUsd;
            $totalOpeningStock += $openingValUsd;

            // 2. Purchases (Total cost spent buying new vouchers in period)
            $purchase_qty = (int)$item->purchased_qty;
            if ($startDate && $endDate) {
                $purchaseInPeriod = ($item->purchase_date && Carbon::parse($item->purchase_date)->between($startDate, $endDate)) ? (int)$item->purchased_qty : 0;
                $purchase_qty = $purchaseInPeriod;
            }
            $purchaseValUsd = $purchase_qty * $unitCostUsd;
            $totalPurchases += $purchaseValUsd;

            // 3. Sold in period
            $soldQuery = Order::where('voucher_id', $item->sku_id)->where('status', 'delivered');
            if ($startDate && $endDate) {
                $soldQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $soldOrders = $soldQuery->get();
            $grossSoldQty = (int) $soldOrders->sum('quantity');

            // Revenue (Sale) = Total revenue generated from selling vouchers
            $revenueUsd = (float) $soldOrders->sum(fn($order) => CurrencyService::getOrderBaseAmount($order));
            $totalRevenue += $revenueUsd;

            // 4. Closing Stock = Value of unsold vouchers at end of period
            $inStockQty = $opening_qty + $purchase_qty;
            $closingQty = max(0, $inStockQty - $grossSoldQty);
            $closingValUsd = $closingQty * $unitCostUsd;
            $totalClosingStock += $closingValUsd;

            // 5. Operating Expenses & Non-Operating:
            // Less: Voucher Lost Expense (Cost of abnormal waste: lost/expired vouchers / approved refund write-offs)
            $refundQuery = RefundRequest::where('status', 'approved')
                ->whereIn('order_id', function ($q) use ($item) {
                    $q->select('order_id')->from('orders')->where('voucher_id', $item->sku_id);
                });
            if ($startDate && $endDate) {
                $refundQuery->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate])
                        ->orWhereBetween('processed_at', [$startDate, $endDate]);
                });
            }
            $refunds = $refundQuery->with('order')->get();
            $refundLostUsd = (float) $refunds->sum(fn($refund) => ProfitLossService::getRefundBaseAmount($refund));

            $expiredLostUsd = 0.0;
            if ($item->is_expired || ($item->expiry_date && Carbon::parse($item->expiry_date)->isPast())) {
                if (!$startDate || ($item->expiry_date && Carbon::parse($item->expiry_date)->between($startDate, $endDate))) {
                    $expiredLostUsd = (int)$item->quantity * $unitCostUsd;
                }
            }
            $totalLostExpense += ($refundLostUsd + $expiredLostUsd);

            // Add: Foreign Exchange (FX) Gain
            $fixedRevenueUsd = (float) $soldOrders->sum(fn($order) => ProfitLossService::getOrderFixedBaseAmount($order));
            $fxGainUsd = $revenueUsd - $fixedRevenueUsd;
            $totalFxGain += $fxGainUsd;
        }

        // Standardized Accounting Formulas
        $cogs = ProfitLossService::calculateCogs($totalOpeningStock, $totalPurchases, $totalClosingStock);
        $grossProfit = ProfitLossService::calculateGrossProfit($totalRevenue, $cogs);
        $netProfit = ProfitLossService::calculateNetProfit($grossProfit, $totalLostExpense);

        $grossProfitPct = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0.0;
        $netProfitPct = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0.0;

        $controlStatus = 'Within range';
        if ($grossProfitPct < 5.0) {
            $controlStatus = 'Below range (<5%)';
        } elseif ($grossProfitPct > 20.0) {
            $controlStatus = 'Above range (>20%)';
        }

        return [
            // Standardized P&L Keys (matching spreadsheet)
            'revenue' => round($totalRevenue, 2),
            'opening_stock' => round($totalOpeningStock, 2),
            'purchases' => round($totalPurchases, 2),
            'closing_stock' => round($totalClosingStock, 2),
            'cogs' => round($cogs, 2),
            'gross_profit' => round($grossProfit, 2),
            'voucher_lost_expense' => round($totalLostExpense, 2),
            'fx_gain' => round($totalFxGain, 2),
            'net_profit' => round($netProfit, 2),

            // Percentages & Controls
            'gross_profit_pct' => $grossProfitPct,
            'net_profit_pct' => $netProfitPct,
            'control_policy' => '5% to 20%',
            'control_status' => $controlStatus,

            // Legacy backward-compatibility keys
            'fixed_sales_revenue' => round($totalRevenue, 2),
            'cost_of_vouchers_sold' => round($cogs, 2),
            'gross_profit_before_fx' => round($grossProfit, 2),
            'fx_gain_loss' => round($totalFxGain, 2),
            'profit_after_fx' => round($netProfit, 2),
            'gross_profit_pct_before_fx' => $grossProfitPct,
            'profit_pct_after_fx' => $netProfitPct,
        ];
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
        if ($request->filled('city')) {
            $query->whereJsonContains('city', $request->city);
        }

        // Date range handling
        $startDate = null;
        $endDate = null;
        $hasDateFilter = false;

        if ($request->filled('date_range')) {
            $hasDateFilter = true;
            $dates = explode(' to ', $request->date_range);
            if (count($dates) == 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
            } else {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();
            }
        } elseif ($request->filled('period_type') || $request->filled('filter_date')) {
            $hasDateFilter = true;
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
            "Cache-Control"       => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sr. No.',
            'SKU ID',
            'Voucher Type',
            'Purchase Currency',
            'Sale Currency',
            'City',
            'Exchange Rate (Sale to USD)',
            'Opening Qty',
            'Opening Value (Purchase Curr)',
            'Opening Value (USD)',
            'Opening Taxes',
            'Purchases Qty',
            'Purchases Value (Purchase Curr)',
            'Purchases Value (USD)',
            'Purchases Taxes',
            'Total Available Qty (Opening + Purchases)',
            'Total Available Value (Purchase Curr)',
            'Total Available Value (USD)',
            'Total Available Taxes',
            'Sold Qty',
            'Sold Value (Sale Curr)',
            'Sold Value (USD)',
            'Sold Taxes',
            'Loss/Refund Qty',
            'Loss/Refund Value (Sale Curr)',
            'Loss/Refund Value (USD)',
            'Loss/Refund Taxes',
            'Closing / Remaining Qty',
            'Closing / Remaining Value (Purchase Curr)',
            'Closing / Remaining Value (USD)',
            'Closing / Remaining Taxes'
        ];

        $callback = function () use ($records, $columns, $startDate, $endDate, $hasDateFilter) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $index => $item) {
                $unit_cost = (float) $item->purchase_value_per_unit;
                $unit_taxes = $item->purchased_qty > 0 ? (float) $item->taxes / $item->purchased_qty : 0;
                $purch_curr = $item->local_currency ?: ($item->currency ?: 'USD');
                $sale_curr = $item->currency ?: 'USD';
                $rate = (float) CurrencyService::getRate($sale_curr);

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

                // 2. Purchases (Additions)
                $purchase_qty = (int)$item->purchased_qty;
                if ($hasDateFilter && $startDate) {
                    $purchaseInPeriod = ($item->purchase_date && Carbon::parse($item->purchase_date)->between($startDate, $endDate)) ? (int)$item->purchased_qty : 0;
                    $purchase_qty = $purchaseInPeriod;
                }

                // 3. Total Available (Opening + Purchases)
                $in_stock_qty = $opening_qty + $purchase_qty;

                // 4. Gross Sold (Delivered) in this period
                $soldQuery = Order::where('voucher_id', $item->sku_id)->where('status', 'delivered');
                if ($startDate && $endDate) {
                    $soldQuery->whereBetween('created_at', [$startDate, $endDate]);
                }
                $soldOrders = $soldQuery->get();
                $gross_sold_qty = (int) $soldOrders->sum('quantity');

                // 5. Approved Loss/Refund (Refunded items)
                $refundedOrderIds = Order::where('voucher_id', $item->sku_id)->pluck('order_id');
                $refundQuery = RefundRequest::whereIn('order_id', $refundedOrderIds)
                    ->where('status', 'approved');
                if ($startDate && $endDate) {
                    $refundQuery->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [$startDate, $endDate])
                            ->orWhereBetween('processed_at', [$startDate, $endDate]);
                    });
                }
                $refunds = $refundQuery->get();
                // Refund requests do not record physical voucher returns.
                $sold_qty = ProfitLossService::getActiveSoldQuantity($gross_sold_qty);

                // Sold Value based on delivered orders sales revenue
                $sold_val_usd = (float) $soldOrders->sum(function ($order) {
                    return CurrencyService::getOrderBaseAmount($order);
                });
                $sold_value = CurrencyService::convert($sold_val_usd, 'USD', $sale_curr);
                $sold_taxes = (float) $soldOrders->sum('tax_amount');

                // Lost Value
                $lost_val_usd = (float) $refunds->sum(function ($refund) {
                    return ProfitLossService::getRefundBaseAmount($refund);
                });
                $lost_value = CurrencyService::convert($lost_val_usd, 'USD', $sale_curr);
                $lost_taxes = (float) $refunds->sum(function ($refund) {
                    $order = $refund->order;
                    return $order ? (float) ($order->tax_amount ?? 0) * ProfitLossService::getRefundRatio($order, $refund) : 0.0;
                });

                // 6. Closing Stock = In Stock - Gross Delivered (Remaining on hand)
                $closing_qty = max(0, $in_stock_qty - $gross_sold_qty);

                // USD Conversions using locked stock-upload columns
                $lockedRate = (float)($item->currency_conversion_rate ?? 0) ?: (float) CurrencyService::getExchangeRate($purch_curr, 'USD');
                $unit_cost_usd = (float)($item->purchase_value_per_unit_usd ?? ($unit_cost * $lockedRate));
                
                $opening_val_usd = $opening_qty * $unit_cost_usd;
                $purch_val_usd = $purchase_qty * $unit_cost_usd;
                $avail_val_usd = $in_stock_qty * $unit_cost_usd;
                $closing_val_usd = $closing_qty * $unit_cost_usd;

                fputcsv($file, [
                    $index + 1,
                    $item->sku_id,
                    $item->voucher_type,
                    $purch_curr,
                    $sale_curr,
                    $item->city ? (is_array($item->city) ? implode(', ', $item->city) : $item->city) : 'N/A',
                    number_format($rate, 6, '.', ''),
                    $opening_qty,
                    number_format($opening_qty * $unit_cost, 2, '.', ''),
                    number_format($opening_val_usd, 2, '.', ''),
                    number_format($opening_qty * $unit_taxes, 2, '.', ''),
                    $purchase_qty,
                    number_format($purchase_qty * $unit_cost, 2, '.', ''),
                    number_format($purch_val_usd, 2, '.', ''),
                    number_format($purchase_qty * $unit_taxes, 2, '.', ''),
                    $in_stock_qty,
                    number_format($in_stock_qty * $unit_cost, 2, '.', ''),
                    number_format($avail_val_usd, 2, '.', ''),
                    number_format($in_stock_qty * $unit_taxes, 2, '.', ''),
                    $sold_qty,
                    number_format($sold_value, 2, '.', ''),
                    number_format($sold_val_usd, 2, '.', ''),
                    number_format($sold_taxes, 2, '.', ''),
                    $lost_qty,
                    number_format($lost_value, 2, '.', ''),
                    number_format($lost_val_usd, 2, '.', ''),
                    number_format($lost_taxes, 2, '.', ''),
                    $closing_qty,
                    number_format($closing_qty * $unit_cost, 2, '.', ''),
                    number_format($closing_val_usd, 2, '.', ''),
                    number_format($closing_qty * $unit_taxes, 2, '.', '')
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
        $mgmtSummary = $this->calculateManagementSummary($records, $startDate, $endDate);

        $filename = "profit_loss_report_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sr. No.',
            'SKU ID',
            'Voucher Type',
            'Currency',
            'Stock Upload Rate',
            'Opening Stock (USD)',
            'Purchases (USD)',
            'Closing Stock (USD)',
            'Total COGS (USD)',
            'Revenue (Sale) (USD)',
            'Gross Profit (USD)',
            'Voucher Lost Expense (USD)',
            'FX Gain / (Loss) (USD)',
            'Net Profit (USD)'
        ];

        $callback = function () use ($records, $columns, $startDate, $endDate, $mgmtSummary) {
            $file = fopen('php://output', 'w');

            // 1. Output Standardized Profit and Loss Statement matching sample spreadsheet
            fputcsv($file, ['Voucher Reselling Business']);
            fputcsv($file, ['Profit and Loss Statement (Standardized Layout)']);
            fputcsv($file, []);
            fputcsv($file, ['P&L Line Item', 'Amount ($)', 'Standard Accounting Formulas / Notes']);
            fputcsv($file, [
                'Revenue (Sale)',
                number_format($mgmtSummary['revenue'], 2, '.', ''),
                'Total revenue generated from selling vouchers'
            ]);
            fputcsv($file, []);
            fputcsv($file, [
                'Opening Stock',
                number_format($mgmtSummary['opening_stock'], 2, '.', ''),
                'Value of unsold vouchers at start of period'
            ]);
            fputcsv($file, [
                'Purchases',
                number_format($mgmtSummary['purchases'], 2, '.', ''),
                'Total cost spent buying new vouchers'
            ]);
            fputcsv($file, [
                'Less: Closing Stock',
                number_format($mgmtSummary['closing_stock'], 2, '.', ''),
                'Value of unsold vouchers at end of period (entered as positive, subtracted in formula)'
            ]);
            fputcsv($file, [
                'Total Cost of Goods Sold (COGS)',
                number_format($mgmtSummary['cogs'], 2, '.', ''),
                '= Opening Stock + Purchases - Closing Stock'
            ]);
            fputcsv($file, [
                'Gross Profit',
                number_format($mgmtSummary['gross_profit'], 2, '.', ''),
                '= Revenue - Total COGS'
            ]);
            fputcsv($file, []);
            fputcsv($file, ['Operating Expenses & Non-Operating Items', '', '']);
            fputcsv($file, [
                'Less: Voucher Lost Expense',
                number_format($mgmtSummary['voucher_lost_expense'], 2, '.', ''),
                'Cost of abnormal waste (lost/expired vouchers)'
            ]);
            fputcsv($file, [
                'Net Profit',
                number_format($mgmtSummary['net_profit'], 2, '.', ''),
                '= Gross Profit - Voucher Lost Expense'
            ]);
            fputcsv($file, []);
            fputcsv($file, ['--- Detailed SKU Breakdown ---']);
            fputcsv($file, $columns);

            foreach ($records as $index => $item) {
                $curr = CurrencyService::normalizeCurrencyCode($item->currency ?: 'USD');
                $stockUploadRate = ProfitLossService::getStockUploadRate($item);
                $unitCostUsd = ProfitLossService::getInventoryUnitCostBase($item);

                // 1. Opening Stock
                $opening_qty = 0;
                if ($startDate) {
                    $purchasedBefore = ($item->purchase_date && Carbon::parse($item->purchase_date)->lt($startDate)) ? (int)$item->purchased_qty : 0;
                    $soldBefore = (int) Order::where('voucher_id', $item->sku_id)
                        ->where('status', 'delivered')
                        ->where('created_at', '<', $startDate)
                        ->sum('quantity');
                    $opening_qty = max(0, $purchasedBefore - $soldBefore);
                }
                $opening_val_usd = round($opening_qty * $unitCostUsd, 2);

                // 2. Purchases in period
                $purchase_qty = (int)$item->purchased_qty;
                if ($startDate && $endDate) {
                    $purchaseInPeriod = ($item->purchase_date && Carbon::parse($item->purchase_date)->between($startDate, $endDate)) ? (int)$item->purchased_qty : 0;
                    $purchase_qty = $purchaseInPeriod;
                }
                $purchase_val_usd = round($purchase_qty * $unitCostUsd, 2);

                // 3. Sold in period
                $soldQuery = Order::where('voucher_id', $item->sku_id)->where('status', 'delivered');
                if ($startDate && $endDate) {
                    $soldQuery->whereBetween('created_at', [$startDate, $endDate]);
                }
                $soldOrders = $soldQuery->get();
                $gross_sold_qty = (int) $soldOrders->sum('quantity');
                $revenue_usd = round((float) $soldOrders->sum(fn($order) => CurrencyService::getOrderBaseAmount($order)), 2);

                // 4. Closing Stock
                $in_stock_qty = $opening_qty + $purchase_qty;
                $closing_qty = max(0, $in_stock_qty - $gross_sold_qty);
                $closing_val_usd = round($closing_qty * $unitCostUsd, 2);

                // 5. Total COGS
                $cogs_usd = ProfitLossService::calculateCogs($opening_val_usd, $purchase_val_usd, $closing_val_usd);

                // 6. Gross Profit
                $gross_profit_usd = ProfitLossService::calculateGrossProfit($revenue_usd, $cogs_usd);

                // 7. Lost Expense
                $refundResultsPL = RefundRequest::where('status', 'approved')
                    ->whereIn('order_id', function ($q) use ($item) {
                        $q->select('order_id')->from('orders')->where('voucher_id', $item->sku_id);
                    });
                if ($startDate && $endDate) {
                    $refundResultsPL->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [$startDate, $endDate])
                            ->orWhereBetween('processed_at', [$startDate, $endDate]);
                    });
                }
                $refundsPL = $refundResultsPL->with('order')->get();
                $refundLostUsd = (float) $refundsPL->sum(fn($refund) => ProfitLossService::getRefundBaseAmount($refund));

                $expiredLostUsd = 0.0;
                if ($item->is_expired || ($item->expiry_date && Carbon::parse($item->expiry_date)->isPast())) {
                    if (!$startDate || ($item->expiry_date && Carbon::parse($item->expiry_date)->between($startDate, $endDate))) {
                        $expiredLostUsd = (int)$item->quantity * $unitCostUsd;
                    }
                }
                $lost_expense_usd = round($refundLostUsd + $expiredLostUsd, 2);

                // 8. Foreign Exchange (FX) Gain
                $grossFixedRevenue = (float) $soldOrders->sum(fn($order) => ProfitLossService::getOrderFixedBaseAmount($order));
                $fx_gain_usd = round($revenue_usd - $grossFixedRevenue, 2);

                // 9. Net Profit
                $net_profit_usd = ProfitLossService::calculateNetProfit($gross_profit_usd, $lost_expense_usd);

                fputcsv($file, [
                    $index + 1,
                    $item->sku_id,
                    $item->voucher_type,
                    $curr,
                    number_format($stockUploadRate, 6, '.', ''),
                    number_format($opening_val_usd, 2, '.', ''),
                    number_format($purchase_val_usd, 2, '.', ''),
                    number_format($closing_val_usd, 2, '.', ''),
                    number_format($cogs_usd, 2, '.', ''),
                    number_format($revenue_usd, 2, '.', ''),
                    number_format($gross_profit_usd, 2, '.', ''),
                    number_format($lost_expense_usd, 2, '.', ''),
                    $fx_gain_usd < 0 ? '(' . number_format(abs($fx_gain_usd), 2, '.', '') . ')' : number_format($fx_gain_usd, 2, '.', ''),
                    number_format($net_profit_usd, 2, '.', '')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bankReport(Request $request)
    {
        $query = Order::query()->with('inventoryVoucher')->whereNotNull('bank_name');

        if ($request->filled('bank_name')) {
            $query->where('bank_name', 'like', '%' . $request->bank_name . '%');
        }

        $bank_data = $query->latest()->paginate(15);

        // Fetch refund mapping
        $orderIds = $bank_data->pluck('order_id')->toArray();
        $refunds = RefundRequest::whereIn('order_id', $orderIds)
            ->where('status', 'approved')
            ->select('order_id', DB::raw('SUM(amount) as total_refund'))
            ->groupBy('order_id')
            ->pluck('total_refund', 'order_id');

        if ($request->ajax()) {
            return view('dashboard.reports.partials.bank-table', compact('bank_data', 'refunds'))->render();
        }

        return view('dashboard.reports.bank', compact('bank_data', 'refunds'));
    }

    public function exportBankReport(Request $request)
    {
        $query = Order::query()->with('inventoryVoucher')->whereNotNull('bank_name');
        if ($request->filled('bank_name')) {
            $query->where('bank_name', 'like', '%' . $request->bank_name . '%');
        }

        $records = $query->get();
        
        $orderIds = $records->pluck('order_id')->toArray();
        $refunds = RefundRequest::whereIn('order_id', $orderIds)
            ->where('status', 'approved')
            ->select('order_id', DB::raw('SUM(amount) as total_refund'))
            ->groupBy('order_id')
            ->pluck('total_refund', 'order_id');

        $filename = "bank_report_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0",
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
            'Sale Value in Buying Currency (USD)',
            'FX Gain/Loss'
        ];

        $callback = function () use ($records, $columns, $refunds) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $index => $record) {
                $currency = $record->currency ?: ($record->inventoryVoucher->currency ?? 'USD');
                $countryRegion = 'Global';
                if ($record->inventoryVoucher && $record->inventoryVoucher->country_region) {
                    $cr = $record->inventoryVoucher->country_region;
                    $cr = is_array($cr) ? $cr : [$cr];
                    $countryRegion = implode(', ', $cr);
                }

                $quantity = $record->quantity ?? 0;
                $amount = $record->amount ?? 0;
                $refundAmount = $refunds[$record->order_id] ?? 0;
                
                $rate = (float)($record->exchange_rate_applied ?? ($record->inventoryVoucher->currency_conversion_rate ?? 1.0));
                if ($rate <= 0) $rate = 1.0;

                $saleValueBuying = $record->amount_in_base_currency !== null 
                    ? (float)$record->amount_in_base_currency 
                    : CurrencyService::convertToBase((float)$amount, $currency)['base_amount'];

                fputcsv($file, [
                    $index + 1,
                    $record->bank_name,
                    $currency,
                    $countryRegion,
                    $quantity,
                    number_format($amount, 2, '.', ''),
                    number_format($amount, 2, '.', ''),
                    number_format($refundAmount, 2, '.', ''),
                    number_format(0, 2, '.', ''), // Disputes (not implemented)
                    number_format($rate, 4, '.', ''),
                    number_format($saleValueBuying, 2, '.', ''),
                    number_format(0, 2, '.', '') // FX Gain/Loss
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function revenue()
    {
        // 1. Total revenue: sum of delivered orders in USD
        $total_revenue = Order::with('inventoryVoucher')
            ->where('status', 'delivered')
            ->get()
            ->sum(function ($order) {
                if ($order->amount_in_base_currency !== null && (float)$order->amount_in_base_currency > 0) {
                    return (float)$order->amount_in_base_currency;
                }
                $cur = $order->currency ?: ($order->inventoryVoucher ? $order->inventoryVoucher->currency : 'USD');
                return CurrencyService::convertToBase((float)$order->amount, $cur)['base_amount'];
            });

        // 2. Available balance: sum of all users' wallet balances converted to base USD
        $available_balance = User::all()->sum(function ($u) {
            $bal = (float)($u->wallet_balance ?? 0);
            if ($bal <= 0) return 0.0;
            // Wallet balances are maintained in USD base units throughout the platform
            return $bal;
        });

        // 3. Pending payments: sum of pending orders in USD
        $pending_payments = Order::with('inventoryVoucher')
            ->where('status', 'pending')
            ->get()
            ->sum(function ($order) {
                if ($order->amount_in_base_currency !== null && (float)$order->amount_in_base_currency > 0) {
                    return (float)$order->amount_in_base_currency;
                }
                $cur = $order->currency ?: ($order->inventoryVoucher ? $order->inventoryVoucher->currency : 'USD');
                return CurrencyService::convertToBase((float)$order->amount, $cur)['base_amount'];
            });

        // 4. Refund amount: sum of approved refund requests in USD (using locked snapshot column)
        $refund_amount = RefundRequest::where('status', 'approved')->sum('amount_in_base_currency');

        $stats = [
            'total_revenue' => $total_revenue,
            'available_balance' => $available_balance,
            'pending_payments' => $pending_payments,
            'refund_amount' => $refund_amount,
        ];

        // 5. Wallet Ledgers: latest 20 transactions
        $ledgers = \App\Models\WalletLedger::with('user')->latest()->limit(20)->get();

        return view('dashboard.revenue.index', compact('stats', 'ledgers'));
    }
}
