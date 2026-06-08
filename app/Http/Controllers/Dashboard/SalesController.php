<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class SalesController extends Controller
{
    public function SalesReport(Request $request)
    {
        $query = Order::with(['user', 'voucher', 'inventoryVoucher'])->where('status', 'delivered');

        // Apply Country Visibility Restrictions for Managers and Support
        $authUser = auth()->user();
        if (($authUser->isManager() || $authUser->isSupport()) && !$authUser->isAdmin()) {
            $query->whereHas('user', function ($q) use ($authUser) {
                $q->where('country', $authUser->country);
            });
        }

        // Apply Reseller Agent Restriction (Only see sales of users they added)
        if ($authUser->isResellerAgent()) {
            $query->whereHas('user', function ($q) use ($authUser) {
                $q->where('sub_agent_id', $authUser->id);
            });
        }

        // Date Wise Report (From - To)
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Voucher Type
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        // Buyer Type
        if ($request->filled('user_role')) {
            $query->where('user_role', $request->user_role);
        }

        // Country & State Filters (Filter by user's country/state)
        if ($request->anyFilled(['user_country', 'user_state'])) {
            $query->whereHas('user', function ($q) use ($request) {
                if ($request->filled('user_country')) {
                    $q->where('country', $request->user_country);
                }
                if ($request->filled('user_state')) {
                    $q->where('state', $request->user_state);
                }
            });
        }

        // Filter by Inventory Voucher fields
        if ($request->anyFilled(['currency', 'country_region', 'brand_name', 'voucher_variant'])) {
            $query->whereHas('inventoryVoucher', function ($q) use ($request) {
                if ($request->filled('currency')) {
                    $q->where('currency', $request->currency);
                }
                if ($request->filled('country_region')) {
                    $q->where('country_region', $request->country_region);
                }
                if ($request->filled('brand_name')) {
                    $q->where('brand_name', 'like', "%{$request->brand_name}%");
                }
                if ($request->filled('voucher_variant')) {
                    $q->where('voucher_variant', 'like', "%{$request->voucher_variant}%");
                }
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%$search%")
                    ->orWhereHas('user', function ($qu) use ($search) {
                        $qu->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%");
                    });
            });
        }

        $perPage = $request->get('per_page', 10);
        $sales = $query->latest()->paginate($perPage)->withQueryString();

        // Fetch unique values for filters from delivered orders
        $filterQuery = Order::where('status', 'delivered')
            ->with(['user', 'inventoryVoucher']);

        // Apply same Country Visibility Restrictions for filter data
        if (($authUser->isManager() || $authUser->isSupport()) && !$authUser->isAdmin()) {
            $filterQuery->whereHas('user', function ($q) use ($authUser) {
                $q->where('country', $authUser->country);
            });
        }

        // Apply Reseller Agent Restriction for filter data
        if ($authUser->isResellerAgent()) {
            $filterQuery->whereHas('user', function ($q) use ($authUser) {
                $q->where('sub_agent_id', $authUser->id);
            });
        }

        $filterData = $filterQuery->get();

        $brands = $filterData->pluck('inventoryVoucher.brand_name')->unique()->filter()->values();
        $countries = $filterData->pluck('user.country')->unique()->filter()->values();
        $states = $filterData->pluck('user.state')->unique()->filter()->values();
        $currencies = $filterData->pluck('inventoryVoucher.currency')->unique()->filter()->values();
        $voucherTypes = $filterData->pluck('voucher_type')->unique()->filter()->values();

        if ($request->ajax()) {
            return view('dashboard.sales.sales-table', compact('sales'))->render();
        }

        return view('dashboard.sales.sales-index', compact('sales', 'brands', 'countries', 'states', 'currencies', 'voucherTypes'));
    }

    public function export(Request $request)
    {
        $query = Order::with(['user', 'voucher', 'inventoryVoucher'])->where('status', 'delivered');

        // Apply Country Visibility Restrictions for Managers and Support
        $authUser = auth()->user();
        if (($authUser->isManager() || $authUser->isSupport()) && !$authUser->isAdmin()) {
            $query->whereHas('user', function ($q) use ($authUser) {
                $q->where('country', $authUser->country);
            });
        }

        // Apply Reseller Agent Restriction (Only see sales of users they added)
        if ($authUser->isResellerAgent()) {
            $query->whereHas('user', function ($q) use ($authUser) {
                $q->where('sub_agent_id', $authUser->id);
            });
        }

        // Date Wise Report (From - To)
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Voucher Type
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        // Buyer Type
        if ($request->filled('user_role')) {
            $query->where('user_role', $request->user_role);
        }

        // Country & State Filters (Filter by user's country/state)
        if ($request->anyFilled(['user_country', 'user_state'])) {
            $query->whereHas('user', function ($q) use ($request) {
                if ($request->filled('user_country')) {
                    $q->where('country', $request->user_country);
                }
                if ($request->filled('user_state')) {
                    $q->where('state', $request->user_state);
                }
            });
        }

        // Filter by Inventory Voucher fields
        if ($request->anyFilled(['currency', 'brand_name', 'voucher_variant'])) {
            $query->whereHas('inventoryVoucher', function ($q) use ($request) {
                if ($request->filled('currency')) {
                    $q->where('currency', $request->currency);
                }
                if ($request->filled('brand_name')) {
                    $q->where('brand_name', $request->brand_name);
                }
                if ($request->filled('voucher_variant')) {
                    $q->where('voucher_variant', 'like', "%{$request->voucher_variant}%");
                }
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%$search%")
                    ->orWhereHas('user', function ($qu) use ($search) {
                        $qu->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%");
                    });
            });
        }

        $records = $query->latest()->get();

        $filename = "sales_report_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sr. No.',
            'Sale ID',
            'Date',
            'Time',
            'Buyer Type',
            'Local currency',
            'Country/Region',
            'Voucher Variant',
            'Voucher Type',
            'Sales Invoice No.',
            'Quantity',
            'Sales Value in Local Currency',
            'Sale Value per Unit',
            'Taxes',
            'Bank',
            'Currency Conversion @',
            'FX Gain/Loss',
            'Referral Points Earned',
            'Referral Points Redeemed',
            'Customer Bonus Points'
        ];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $index => $record) {
                $localValue = 0;
                $currencyCode = $record->inventoryVoucher->local_currency ?? '';
                if ($record->inventoryVoucher) {
                    $localValue = $record->inventoryVoucher->purchase_value_per_unit * $record->quantity;
                }

                $unitValue = $record->quantity > 0 ? $localValue / $record->quantity : 0;

                $fxGainLoss = 0;
                if ($record->inventoryVoucher) {
                    $totalPurchaseValue = $record->inventoryVoucher->purchase_value_per_unit * $record->quantity;
                    $fxGainLoss = $record->amount - $totalPurchaseValue;
                }

                fputcsv($file, [
                    $index + 1,
                    $record->order_id,
                    $record->created_at->format('d M Y'),
                    $record->created_at->format('H:i A'),
                    ucfirst(str_replace('_', ' ', $record->user_role)),
                    $currencyCode,
                    $record->user->country ?? 'N/A',
                    $record->inventoryVoucher->voucher_variant ?? 'N/A',
                    $record->voucher_type,
                    $record->order_id,
                    $record->quantity,
                    $currencyCode . ' ' . number_format($localValue, 2),
                    $currencyCode . ' ' . number_format($unitValue, 2),
                    $record->inventoryVoucher->taxes ?? '0.00',
                    $record->bank_name ?? 'N/A',
                    number_format($record->inventoryVoucher->currency_conversion_rate ?? 0, 4),
                    $currencyCode . ' ' . number_format($fxGainLoss, 2),
                    $record->referral_points ?? 0,
                    $record->points_redeemed ?? 0,
                    number_format($record->bonus_amount ?? 0, 2)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
