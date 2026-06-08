<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\InventoryVoucher;
use App\Models\User;
use Carbon\Carbon;
use App\Notifications\VoucherDeliveredNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->isManager() && !auth()->user()->can_view_orders) {
            abort(403, 'Unauthorized access to orders.');
        }
        if (auth()->user()->isSupport() && !auth()->user()->can_view_orders) {
            abort(403, 'Unauthorized access to orders.');
        }

        $query = Order::with(['user', 'voucher', 'inventoryVoucher'])->latest();

        $authUser = auth()->user();
        if (($authUser->isManager() || $authUser->isSupport()) && !$authUser->isAdmin()) {
            $query->whereHas('user', function ($q) use ($authUser) {
                $q->where('country', $authUser->country);
            });
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', 'like', '%' . $request->order_id . '%');
        }

        if ($request->filled('sku_id')) {
            $query->where('voucher_id', 'like', '%' . $request->sku_id . '%');
        }

        if ($request->filled('country')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('country', $request->country);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role')) {
            $query->where('user_role', $request->role);
        }

        $perPage = $request->get('per_page', 10);
        $orders = $query->paginate($perPage)->withQueryString();

        $deliveredCodes = InventoryVoucher::pluck('delivered_vouchers')->flatten()->filter()->unique()->toArray();
        
        $countriesQuery = User::whereHas('orders')->whereNotNull('country');
        if (($authUser->isManager() || $authUser->isSupport()) && !$authUser->isAdmin()) {
            $countriesQuery->where('country', $authUser->country);
        }
        $countries = $countriesQuery->distinct()->pluck('country');

        if ($request->ajax()) {
            // Need to determine which partial to return based on context
            // oder-deli uses its own table structure
            return view('dashboard.orders.oder-deli-table', compact('orders', 'deliveredCodes', 'countries'))->render();
        }

        return view('dashboard.orders.oder-deli', compact('orders', 'deliveredCodes', 'countries'));
    }

    public function export(Request $request)
    {
        $query = Order::with(['user', 'voucher'])->latest();

        $authUser = auth()->user();
        if (($authUser->isManager() || $authUser->isSupport()) && !$authUser->isAdmin()) {
            $query->whereHas('user', function ($q) use ($authUser) {
                $q->where('country', $authUser->country);
            });
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', 'like', '%' . $request->order_id . '%');
        }

        if ($request->filled('sku_id')) {
            $query->where('voucher_id', 'like', '%' . $request->sku_id . '%');
        }

        if ($request->filled('country')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('country', $request->country);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role')) {
            $query->where('user_role', $request->role);
        }

        $orders = $query->get();
        $filename = "orders_export_" . date('Y-m-d_H-i-s') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['Order ID', 'Customer', 'Email', 'Role', 'Voucher', 'Amount', 'Ref Points', 'Bonus Points', 'Status', 'Date']);

        foreach ($orders as $order) {
            fputcsv($handle, [
                $order->order_id,
                $order->user->name ?? 'N/A',
                $order->user->email ?? 'N/A',
                $order->user_role,
                $order->voucher_type,
                $order->amount,
                $order->referral_points,
                $order->bonus_amount,
                $order->status,
                $order->created_at->format('Y-m-d H:i')
            ]);
        }

        fclose($handle);
        exit;
    }

    public function deliver(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->update([
            'status' => 'delivered',
            'delivery_details' => $request->codes
        ]);

        // Update Inventory delivered_vouchers and quantity
        $voucher = InventoryVoucher::where('sku_id', $order->voucher_id)->first();
        if ($voucher) {
            $newDelivered = array_map('trim', explode("\n", str_replace("\r", "", $request->codes)));
            $existingDelivered = $voucher->delivered_vouchers ?: [];
            $allDelivered = array_unique(array_merge($existingDelivered, $newDelivered));

            $voucher->delivered_vouchers = $allDelivered;
            $voucher->save();
        }

        // Notify User
        try {
            $user = $order->user;
            $codes = $request->codes;

            // Database Notification for User
            $user->notify(new VoucherDeliveredNotification(
                $order,
                "You have received voucher codes for Order #{$order->order_id}. Total amount: RS {$order->amount}."
            ));

            // Email to User
            Mail::send('emails.order-delivered', ['order' => $order, 'user' => $user, 'codes' => $codes], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Your Voucher Codes Have Been Delivered! - UniCou');
            });
        } catch (\Exception $e) {
            // Log error but continue
        }

        // Notify Admins, Managers, and Support Team
        try {
            $staff = User::whereIn('account_type', ['admin', 'manager', 'support_team'])->get();
            Notification::send($staff, new VoucherDeliveredNotification(
                $order,
                "Voucher codes delivered for Order #{$order->order_id} by " . auth()->user()->name
            ));
        } catch (\Exception $e) {
            // Log error but continue
        }

        return back()->with('success', 'Order marked as delivered and codes sent to user.');
    }

    public function cancel(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Order already cancelled.');
        }

        $reason = $request->reason ?? 'Order cancelled by administrator.';
        $oldStatus = $order->status;

        $order->update(['status' => 'cancelled']);

        // Restore stock if not already delivered
        $voucher = InventoryVoucher::where('sku_id', $order->voucher_id)->first();
        if ($voucher && $oldStatus !== 'delivered') {
            $voucher->increment('quantity', $order->quantity);
        }

        // Send Email to User
        try {
            $user = $order->user;
            Mail::send('emails.order-cancelled', ['order' => $order, 'user' => $user, 'reason' => $reason], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Update Regarding Your Order - UniCou');
            });
        } catch (\Exception $e) {
            // Log error but continue
        }

        return back()->with('success', 'Order cancelled and notification sent to user.');
    }

    public function approve($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return back()->with('error', 'Only pending orders can be approved.');
        }

        $order->update(['status' => 'completed']);

        // Send Email to User
        try {
            $user = $order->user;
            Mail::send('emails.order-approved', ['order' => $order, 'user' => $user], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Your Order Has Been Approved - UniCou');
            });
        } catch (\Exception $e) {
            // Log error but continue
        }

        return back()->with('success', 'Order approved successfully.');
    }

    public function orderHistory(Request $request)
    {
        $query = Order::with(['inventoryVoucher'])
            ->leftJoin('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
            ->select(
                'orders.*',
                'inventory_vouchers.brand_name as v_brand_name',
                'inventory_vouchers.currency as v_currency',
                'inventory_vouchers.country_region as v_country_region',
                'inventory_vouchers.voucher_variant as v_voucher_variant',
                'inventory_vouchers.voucher_type as v_voucher_type',
                'inventory_vouchers.expiry_date as v_expiry_date',
                'inventory_vouchers.taxes as v_taxes'
            )
            ->where('orders.user_id', auth()->id());

        // Filters based on User Request
        if ($request->filled('order_id')) {
            $query->where('orders.order_id', 'like', '%' . $request->order_id . '%');
        }
        if ($request->filled('from_date')) {
            $query->whereDate('orders.created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('orders.created_at', '<=', $request->to_date);
        }
        if ($request->filled('currency')) {
            $query->where('inventory_vouchers.currency', $request->currency);
        }
        if ($request->filled('brand_name')) {
            $query->where('inventory_vouchers.brand_name', 'like', '%' . $request->brand_name . '%');
        }
        if ($request->filled('voucher_variant')) {
            $query->where('inventory_vouchers.voucher_variant', 'like', '%' . $request->voucher_variant . '%');
        }
        if ($request->filled('voucher_type')) {
            $query->where('inventory_vouchers.voucher_type', $request->voucher_type);
        }
        if ($request->filled('country_region')) {
            $query->where('inventory_vouchers.country_region', $request->country_region);
        }

        $perPage = $request->get('per_page', 10);
        $orders = $query->latest('orders.created_at')->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.orders.history-table', compact('orders'))->render();
        }

        // Get dynamic brands and variants from the user's orders for filter options
        $brands = Order::where('user_id', auth()->id())
            ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
            ->distinct()
            ->pluck('inventory_vouchers.brand_name')
            ->filter();

        $variants = Order::where('user_id', auth()->id())
            ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
            ->distinct()
            ->pluck('inventory_vouchers.voucher_variant')
            ->filter();

        return view('dashboard.orders.history', compact('orders', 'brands', 'variants'));
    }

    public function userExport(Request $request)
    {
        $query = Order::with(['inventoryVoucher'])
            ->leftJoin('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
            ->select(
                'orders.*',
                'inventory_vouchers.brand_name as v_brand_name',
                'inventory_vouchers.currency as v_currency',
                'inventory_vouchers.country_region as v_country_region',
                'inventory_vouchers.voucher_variant as v_voucher_variant',
                'inventory_vouchers.voucher_type as v_voucher_type',
                'inventory_vouchers.expiry_date as v_expiry_date'
            )
            ->where('orders.user_id', auth()->id());

        // Apply same filters as history
        if ($request->filled('order_id')) {
            $query->where('orders.order_id', 'like', '%' . $request->order_id . '%');
        }
        if ($request->filled('from_date')) {
            $query->whereDate('orders.created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('orders.created_at', '<=', $request->to_date);
        }
        if ($request->filled('currency')) {
            $query->where('inventory_vouchers.currency', $request->currency);
        }
        if ($request->filled('brand_name')) {
            $query->where('inventory_vouchers.brand_name', 'like', '%' . $request->brand_name . '%');
        }
        if ($request->filled('voucher_variant')) {
            $query->where('inventory_vouchers.voucher_variant', 'like', '%' . $request->voucher_variant . '%');
        }
        if ($request->filled('voucher_type')) {
            $query->where('inventory_vouchers.voucher_type', $request->voucher_type);
        }
        if ($request->filled('country_region')) {
            $query->where('inventory_vouchers.country_region', $request->country_region);
        }

        $orders = $query->latest('orders.created_at')->get();
        $filename = "purchase_report_" . date('Ymd_His') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $columns = [
            'Sr. No.',
            'P.ID.',
            'Date',
            'Time',
            'Brand Name',
            'Currency',
            'Country/Region',
            'Voucher Variant',
            'Voucher Type',
            'Purchase Invoice No.',
            'Purchase Date',
            'Total Quantity',
            'Purchase Value',
            'Taxes',
            'Per Unit Price',
            'Issue Date',
            'Expiry Date',
            'Credit Limit',
            'Status'
        ];

        fputcsv($handle, $columns);

        $user = auth()->user();
        foreach ($orders as $index => $order) {
            // Handle Country/Region formatting
            $countryRegion = 'N/A';
            if (isset($order->inventoryVoucher->country_region)) {
                $cr = $order->inventoryVoucher->country_region;
                $cr = is_array($cr) ? $cr : [$cr];
                $countryRegion = in_array('all', $cr) || in_array('GLB', $cr) ? 'GLB' : (count($cr) > 1 ? 'MULTY' : ($cr[0] ?? 'N/A'));
            } else {
                $countryRegion = $order->v_country_region ?? 'N/A';
            }

            fputcsv($handle, [
                $index + 1,
                $order->order_id,
                $order->created_at->format('Y-m-d'),
                $order->created_at->format('H:i:s'),
                $order->inventoryVoucher->brand_name ?? $order->v_brand_name ?? 'N/A',
                $order->inventoryVoucher->currency ?? $order->v_currency ?? 'N/A',
                $countryRegion,
                $order->inventoryVoucher->voucher_variant ?? $order->v_voucher_variant ?? 'N/A',
                $order->inventoryVoucher->voucher_type ?? $order->v_voucher_type ?? $order->voucher_type ?? 'N/A',
                $order->order_id,
                $order->created_at->format('Y-m-d'),
                $order->quantity,
                $order->amount,
                $order->inventoryVoucher->taxes ?? '0.00',
                number_format($order->amount / max(1, $order->quantity), 2, '.', ''),
                $order->created_at->format('Y-m-d'),
                ($order->inventoryVoucher->expiry_date ?? $order->v_expiry_date) ? \Carbon\Carbon::parse($order->inventoryVoucher->expiry_date ?? $order->v_expiry_date)->format('Y-m-d') : 'N/A',
                $user->voucher_limit ?? 'N/A',
                $order->status
            ]);
        }

        fclose($handle);
        exit;
    }
}
