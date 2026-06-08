<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class BonusController extends Controller
{
    public function bonus(Request $request)
    {
        $user = Auth::user();
        $query = Order::query()
            ->leftJoin('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
            ->select(
                'orders.*',
                'inventory_vouchers.brand_name as v_brand_name'
            )
            ->where('orders.user_id', $user->id)
            ->where('orders.bonus_amount', '>', 0);

        // Filters
        if ($request->filled('order_id')) {
            $query->where('orders.order_id', 'like', '%' . $request->order_id . '%');
        }
        if ($request->filled('brand_name')) {
            $query->where('inventory_vouchers.brand_name', 'like', '%' . $request->brand_name . '%');
        }
        if ($request->filled('from_date')) {
            $query->whereDate('orders.created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('orders.created_at', '<=', $request->to_date);
        }
        if ($request->filled('status')) {
            $query->where('orders.status', $request->status);
        }

        $perPage = $request->get('per_page', 15);
        $bonusHistory = $query->latest('orders.created_at')->paginate($perPage)->withQueryString();

        $totalBonus = Order::where('user_id', $user->id)->sum('bonus_amount');

        // Dynamic Brands for filter
        $brands = Order::where('user_id', $user->id)
            ->where('bonus_amount', '>', 0)
            ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
            ->distinct()
            ->pluck('inventory_vouchers.brand_name')
            ->filter();

        return view('dashboard.bonus-point.index', compact('bonusHistory', 'totalBonus', 'brands'));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $query = Order::query()
            ->leftJoin('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
            ->select(
                'orders.*',
                'inventory_vouchers.brand_name as v_brand_name'
            )
            ->where('orders.user_id', $user->id)
            ->where('orders.bonus_amount', '>', 0);

        if ($request->filled('order_id')) {
            $query->where('orders.order_id', 'like', '%' . $request->order_id . '%');
        }
        if ($request->filled('brand_name')) {
            $query->where('inventory_vouchers.brand_name', 'like', '%' . $request->brand_name . '%');
        }
        if ($request->filled('from_date')) {
            $query->whereDate('orders.created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('orders.created_at', '<=', $request->to_date);
        }
        if ($request->filled('status')) {
            $query->where('orders.status', $request->status);
        }

        $history = $query->latest('orders.created_at')->get();
        $filename = "bonus_points_history_" . date('Ymd_His') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['S.No', 'Order ID', 'Brand', 'Voucher', 'Amount', 'Date', 'Bonus Points', 'Status']);

        foreach ($history as $index => $order) {
            fputcsv($handle, [
                $index + 1,
                $order->order_id,
                $order->v_brand_name ?? 'N/A',
                $order->voucher_type,
                $order->amount,
                $order->created_at->format('Y-m-d H:i'),
                $order->bonus_amount,
                $order->status
            ]);
        }

        fclose($handle);
        exit;
    }
}
