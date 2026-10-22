<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\ProfitLossService;

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

        // Apply Reseller Agent Restriction (See sales of users they added AND their own purchases)
        if ($authUser->isResellerAgent()) {
            $query->where(function ($q) use ($authUser) {
                $q->where('orders.user_id', $authUser->id)
                  ->orWhere('orders.sub_agent_id', $authUser->id)
                  ->orWhereHas('user', function ($qu) use ($authUser) {
                      $qu->where('sub_agent_id', $authUser->id);
                  });
            });
        } elseif ($request->filled('reseller_id')) {
            // Filter by specific Reseller Agent for Admin / Manager
            $resellerId = $request->reseller_id;
            $query->where(function ($q) use ($resellerId) {
                $q->where('sub_agent_id', $resellerId)
                  ->orWhere('user_id', $resellerId)
                  ->orWhereHas('user', function ($qu) use ($resellerId) {
                      $qu->where('sub_agent_id', $resellerId);
                  });
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
        if ($request->anyFilled(['currency', 'country_region', 'city', 'brand_name', 'voucher_variant'])) {
            $query->whereHas('inventoryVoucher', function ($q) use ($request) {
                if ($request->filled('currency')) {
                    $q->where('currency', $request->currency);
                }
                if ($request->filled('country_region')) {
                    $q->where('country_region', $request->country_region);
                }
                if ($request->filled('city')) {
                    $q->whereJsonContains('city', $request->city);
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
        $cities = $filterData->pluck('inventoryVoucher.city')->flatten()->unique()->filter()->values();
        $resellers = User::where('account_type', 'reseller_agent')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'user_id']);
        $selectedReseller = $request->filled('reseller_id') ? User::find($request->reseller_id) : null;

        if ($request->ajax()) {
            return view('dashboard.sales.sales-table', compact('sales'))->render();
        }

        return view('dashboard.sales.sales-index', compact('sales', 'brands', 'countries', 'states', 'currencies', 'voucherTypes', 'cities', 'resellers', 'selectedReseller'));
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

        // Apply Reseller Agent Restriction (See sales of users they added AND their own purchases)
        if ($authUser->isResellerAgent()) {
            $query->where(function ($q) use ($authUser) {
                $q->where('orders.user_id', $authUser->id)
                  ->orWhere('orders.sub_agent_id', $authUser->id)
                  ->orWhereHas('user', function ($qu) use ($authUser) {
                      $qu->where('sub_agent_id', $authUser->id);
                  });
            });
        } elseif ($request->filled('reseller_id')) {
            $resellerId = $request->reseller_id;
            $query->where(function ($q) use ($resellerId) {
                $q->where('sub_agent_id', $resellerId)
                  ->orWhere('user_id', $resellerId)
                  ->orWhereHas('user', function ($qu) use ($resellerId) {
                      $qu->where('sub_agent_id', $resellerId);
                  });
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
        if ($request->anyFilled(['currency', 'brand_name', 'voucher_variant', 'city'])) {
            $query->whereHas('inventoryVoucher', function ($q) use ($request) {
                if ($request->filled('currency')) {
                    $q->where('currency', $request->currency);
                }
                if ($request->filled('city')) {
                    $q->whereJsonContains('city', $request->city);
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
            "Cache-Control"       => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sr. No.',
            'Sale ID',
            'Date',
            'Time',
            'Buyer ID',
            'Buyer Name',
            'Buyer Type',
            'Buyer Currency',
            'Transaction Currency',
            'Country/Region',
            'City',
            'Voucher Variant',
            'Voucher Type',
            'Sales Invoice No.',
            'Quantity',
            'Sales Value in Local Currency',
            'Sale Value per Unit (Local)',
            'Admin Exchange Rate (to USD)',
            'Quoted Sales Value (USD)',
            'Settled Sales Value (USD)',
            'Purchase Cost per Unit (USD)',
            'Total Purchase Cost (USD)',
            'Gross Profit (USD)',
            'Gateway FX Variance (USD)',
            'Net Realized Profit (USD)',
            'Taxes',
            'Bank',
            'Referral Points Earned',
            'Referral Points Redeemed',
            'Customer Bonus Points'
        ];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $index => $record) {
                $currencyCode = $record->currency ?: ($record->inventoryVoucher->currency ?? 'USD');
                $saleValueLocal = (float) $record->amount;
                $quantity = max(1, (int)$record->quantity);
                $unitValueLocal = $saleValueLocal / $quantity;
                
                // Admin Exchange Rate & Quoted Value in USD
                $adminRate = (float)($record->exchange_rate_applied ?: CurrencyService::getRate($currencyCode));
                $quotedSalesUsd = (float)($saleValueLocal * $adminRate);

                // Settled Value in Base Currency (USD) from payment gateway/settlement
                if ($record->amount_in_base_currency !== null && (float)$record->amount_in_base_currency > 0) {
                    $settledSalesUsd = (float)$record->amount_in_base_currency;
                } else {
                    $settledSalesUsd = $quotedSalesUsd;
                }

                // Purchase Cost (USD) — using pre-calculated locked USD columns
                $unitPurchaseUsd = 0.0;
                $totalPurchaseCostUsd = 0.0;
                if ($record->inventoryVoucher) {
                    $unitPurchaseUsd = (float) ProfitLossService::getInventoryUnitCostBase($record->inventoryVoucher);
                    $totalPurchaseCostUsd = $unitPurchaseUsd * $quantity;
                }

                // Financial Breakdown (Decoupled Operating Markup vs Gateway FX Variance)
                $grossProfitUsd = $quotedSalesUsd - $totalPurchaseCostUsd;
                $gatewayFxVarianceUsd = $settledSalesUsd - $quotedSalesUsd;
                $netRealizedProfitUsd = $grossProfitUsd + $gatewayFxVarianceUsd;

                $bank = $record->bank_name ?: ($record->payment_method ? ucfirst($record->payment_method) : 'N/A');

                fputcsv($file, [
                    $index + 1,
                    $record->order_id,
                    $record->created_at->format('d M Y'),
                    $record->created_at->format('H:i A'),
                    $record->user->user_id ?? 'N/A',
                    trim(($record->user->first_name ?? '') . ' ' . ($record->user->last_name ?? '')) ?: 'N/A',
                    ucfirst(str_replace('_', ' ', $record->user_role)),
                    $record->user ? $record->user->currency : 'N/A',
                    $currencyCode,
                    $record->user->country ?? 'N/A',
                    optional($record->inventoryVoucher)->city ? (is_array($record->inventoryVoucher->city) ? implode(', ', $record->inventoryVoucher->city) : $record->inventoryVoucher->city) : 'N/A',
                    $record->inventoryVoucher->voucher_variant ?? $record->voucher_type ?? 'N/A',
                    $record->voucher_type,
                    $record->order_id,
                    $record->quantity,
                    number_format($saleValueLocal, 2, '.', ''),
                    number_format($unitValueLocal, 2, '.', ''),
                    number_format($adminRate, 6, '.', ''),
                    number_format($quotedSalesUsd, 2, '.', ''),
                    number_format($settledSalesUsd, 2, '.', ''),
                    number_format($unitPurchaseUsd, 2, '.', ''),
                    number_format($totalPurchaseCostUsd, 2, '.', ''),
                    number_format($grossProfitUsd, 2, '.', ''),
                    number_format($gatewayFxVarianceUsd, 2, '.', ''),
                    number_format($netRealizedProfitUsd, 2, '.', ''),
                    number_format($record->tax_amount ?? 0, 2, '.', ''),
                    $bank,
                    $record->referral_points ?? 0,
                    $record->points_redeemed ?? 0,
                    number_format($record->bonus_amount ?? 0, 2, '.', '')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
