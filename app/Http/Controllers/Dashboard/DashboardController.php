<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vouchar;
use App\Models\InventoryVoucher;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            $perPage = $request->get('per_page', 10);
            $users = User::latest()->paginate($perPage)->withQueryString();
            
            $topBrand = Order::where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.brand_name', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.brand_name')
                ->orderBy('total', 'desc')
                ->first();

            $topVariant = Order::where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.voucher_variant', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.voucher_variant')
                ->orderBy('total', 'desc')
                ->first();

            $topCountry = Order::where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.country_region', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.country_region')
                ->orderBy('total', 'desc')
                ->first();

            $topBuyer = Order::where('orders.status', 'delivered')
                ->join('users', 'orders.user_id', '=', 'users.id')
                ->select('users.name', \DB::raw('count(*) as total'))
                ->groupBy('users.id', 'users.name')
                ->orderBy('total', 'desc')
                ->first();

            $stats = [
                'total_users' => User::count(),
                'agents' => User::where('account_type', 'reseller_agent')->count(),
                'students' => User::where('account_type', 'student')->count(),
                'pending_approvals' => User::where('profile_verification_status', 'pending')->count(),
                'total_vouchers' => InventoryVoucher::sum('quantity'),
                'total_sales' => Order::where('status', 'delivered')->count(),
                'total_revenue' => Order::where('status', 'delivered')->sum('amount'),
                'total_referral_points' => Order::sum('referral_points'),
                'total_bonus_points' => Order::sum('bonus_amount'),
                'top_brand' => $topBrand ? $topBrand->brand_name : 'N/A',
                'top_variant' => $topVariant ? $topVariant->voucher_variant : 'N/A',
                'top_country' => $topCountry ? $topCountry->country_region : 'N/A',
                'top_buyer' => $topBuyer ? $topBuyer->name : 'N/A',
            ];
            return view('dashboard.admin_dashboard', compact('users', 'stats'));
        }

        if ($user->isManager()) {
            $topBrand = Order::where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.brand_name', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.brand_name')
                ->orderBy('total', 'desc')
                ->first();

            $topVariant = Order::where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.voucher_variant', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.voucher_variant')
                ->orderBy('total', 'desc')
                ->first();

            $topCountry = Order::where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.country_region', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.country_region')
                ->orderBy('total', 'desc')
                ->first();

            $topBuyer = Order::where('orders.status', 'delivered')
                ->join('users', 'orders.user_id', '=', 'users.id')
                ->select('users.name', \DB::raw('count(*) as total'))
                ->groupBy('users.id', 'users.name')
                ->orderBy('total', 'desc')
                ->first();

            $stats = [
                'total_users' => User::count(),
                'agents' => User::where('account_type', 'reseller_agent')->count(),
                'students' => User::where('account_type', 'student')->count(),
                'pending_approvals' => User::where('profile_verification_status', 'pending')->count(),
                'total_vouchers' => InventoryVoucher::sum('quantity'),
                'total_sales' => Order::where('status', 'delivered')->count(),
                'total_revenue' => Order::where('status', 'delivered')->sum('amount'),
                'total_referral_points' => Order::sum('referral_points'),
                'total_bonus_points' => Order::sum('bonus_amount'),
                'top_brand' => $topBrand ? $topBrand->brand_name : 'N/A',
                'top_variant' => $topVariant ? $topVariant->voucher_variant : 'N/A',
                'top_country' => $topCountry ? $topCountry->country_region : 'N/A',
                'top_buyer' => $topBuyer ? $topBuyer->name : 'N/A',
            ];
            return view('dashboard.manager_dashboard', compact('stats'));
        }

        if ($user->isResellerAgent()) {
            $topSellingBrands = Order::where('orders.user_id', $user->id)
                ->where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.brand_name', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.brand_name')
                ->orderBy('total', 'desc')
                ->limit(3)
                ->get();
                
            $topSellingVariant = Order::where('orders.user_id', $user->id)
                ->where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.voucher_variant', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.voucher_variant')
                ->orderBy('total', 'desc')
                ->first();

            $totalPurchaseAmount = Order::where('user_id', $user->id)
                ->where('status', 'delivered')
                ->sum('amount');
            
            $totalVouchersPurchased = Order::where('user_id', $user->id)
                ->where('status', 'delivered')
                ->sum('quantity');

           // New stats including sub-agents
            $totalReferralPoints = $user->total_referral_points;
            $totalBonusPoints = $user->total_bonus_points;

            $referralPointsHistory = Order::where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('sub_agent_id', $user->id);
                })
                ->where('status', 'delivered')
                ->where('referral_points', '>', 0)
                ->latest()
                ->limit(5)
                ->get();

            $bonusPointsHistory = Order::where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('sub_agent_id', $user->id);
                })
                ->where('status', 'delivered')
                ->where('bonus_amount', '>', 0)
                ->latest()
                ->limit(5)
                ->get();

            return view('dashboard.reseller_dashboard', compact(
                'topSellingBrands', 
                'topSellingVariant', 
                'totalPurchaseAmount', 
                'totalVouchersPurchased',
                'totalReferralPoints',
                'totalBonusPoints',
                'referralPointsHistory',
                'bonusPointsHistory'
            ));
        }

        if ($user->isRegularAgent()) {
            $topSellingBrands = Order::where('orders.user_id', $user->id)
                ->where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.brand_name', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.brand_name')
                ->orderBy('total', 'desc')
                ->limit(3)
                ->get();
                
            $topSellingVariant = Order::where('orders.user_id', $user->id)
                ->where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.voucher_variant', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.voucher_variant')
                ->orderBy('total', 'desc')
                ->first();

            $totalPurchaseAmount = Order::where('user_id', $user->id)
                ->where('status', 'delivered')
                ->sum('amount');
            
            $totalVouchersPurchased = Order::where('user_id', $user->id)
                ->where('status', 'delivered')
                ->sum('quantity');

            return view('dashboard.agent_dashboard', compact('topSellingBrands', 'topSellingVariant', 'totalPurchaseAmount', 'totalVouchersPurchased'));
        }

        if ($user->isStudent()) {
            $topSellingBrands = Order::where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.brand_name', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.brand_name')
                ->orderBy('total', 'desc')
                ->limit(3)
                ->get();
                
            $topSellingVariant = Order::where('orders.status', 'delivered')
                ->join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
                ->select('inventory_vouchers.voucher_variant', \DB::raw('count(*) as total'))
                ->groupBy('inventory_vouchers.voucher_variant')
                ->orderBy('total', 'desc')
                ->first();

            // Graph Data for Student (Revenue/Purchases)
            $dailyPurchases = Order::where('user_id', $user->id)
                ->where('status', 'delivered')
                ->where('created_at', '>=', now()->subDays(7))
                ->select(\DB::raw('DATE(created_at) as date'), \DB::raw('SUM(amount) as total_revenue'), \DB::raw('COUNT(*) as total_orders'))
                ->groupBy('date')
                ->get();

            $monthlyPurchases = Order::where('user_id', $user->id)
                ->where('status', 'delivered')
                ->where('created_at', '>=', now()->subMonths(12))
                ->select(\DB::raw('MONTHNAME(created_at) as month'), \DB::raw('SUM(amount) as total_revenue'), \DB::raw('COUNT(*) as total_orders'))
                ->groupBy('month')
                ->get();

            $totalPurchaseAmount = Order::where('user_id', $user->id)
                ->where('status', 'delivered')
                ->sum('amount');
            
            $totalVouchersPurchased = Order::where('user_id', $user->id)
                ->where('status', 'delivered')
                ->sum('quantity');

            return view('dashboard.student_dashboard', compact('topSellingBrands', 'topSellingVariant', 'totalPurchaseAmount', 'totalVouchersPurchased'));
        }

        if ($user->isSupport()) {
            $stats = [
                'total_users' => User::count(),
                'pending_approvals' => User::where('profile_verification_status', 'pending')->count(),
                'students' => User::where('account_type', 'student')->count(),
                'active_vouchers' => InventoryVoucher::sum('quantity'),
            ];
              // Rating stats from disputes assigned to this support person
            $disputeStats = \App\Models\Dispute::where('assigned_to', $user->id)
                ->whereNotNull('rating')
                ->select(\DB::raw('count(*) as count'), \DB::raw('avg(rating) as avg_rating'))
                ->first();

            $stats['rating_count'] = $disputeStats->count ?? 0;
            $stats['rating_percentage'] = $disputeStats->avg_rating ? round(($disputeStats->avg_rating / 5) * 100) : 0;

            return view('dashboard.support_dashboard', compact('stats'));
        }

        return redirect('/');
    }

     public function notifications(Request $request)
     {
        $user = auth()->user();
        // Mark all notifications as read (except System Alerts) when visiting the notifications page
        $user->unreadNotifications()
            ->where('type', '!=', \App\Notifications\SystemAlertNotification::class)
            ->update(['read_at' => now()]);
        
        // Refresh the unreadNotifications collection so the count in header becomes 0
        $user->load('unreadNotifications');
        
        $perPage = $request->get('per_page', 10);
        $notifications = $user->notifications()
            ->where('type', '!=', \App\Notifications\SystemAlertNotification::class)
            ->paginate($perPage)
            ->withQueryString();
        return view('dashboard.notifications.index', compact('notifications'));
     }

     public function getUnreadNotificationsCount()
     {
        return response()->json([
            'count' => auth()->user()
                ->unreadNotifications()
                ->where('type', '!=', \App\Notifications\SystemAlertNotification::class)
                ->count()
        ]);
     }

     public function markAllNotificationsAsRead()
     {
        auth()->user()
            ->unreadNotifications()
            ->where('type', '!=', \App\Notifications\SystemAlertNotification::class)
            ->update(['read_at' => now()]);
        return back()->with('success', 'All notifications marked as read.');
     }

    public function notificationsBulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No notifications selected.');
        }

        $notifications = auth()->user()->notifications()->whereIn('id', $ids);

        if ($action === 'read') {
            $notifications->update(['read_at' => now()]);
            $message = 'Selected notifications marked as read.';
        } elseif ($action === 'delete') {
            $notifications->delete();
            $message = 'Selected notifications deleted.';
        } else {
            return back()->with('error', 'Invalid action.');
        }

        return back()->with('success', $message);
    }

    public function stockAlerts()
    {
        $vouchers = Vouchar::where('stock', '<', 10)->get();
        $alerts = $vouchers->map(function ($v) {
            return [
                'type' => $v->name,
                'remaining' => $v->stock,
                'threshold' => 10
            ];
        });
        return view('dashboard.stock.alerts', compact('alerts'));
    }

    public function manageAccount()
    {
        $user = auth()->user();
        return view('dashboard.account.manage', compact('user'));
    }

    public function updateAccount(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only('first_name', 'last_name', 'email'));

        return back()->with('success', 'Account updated successfully.');
    }

    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Current password does not match.');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Password changed successfully.');
    }

    public function getTrendData(Request $request)
    {
        $type = $request->get('type', 'Daily');
        $user = auth()->user();
        $query = Order::where('status', 'delivered');

        // If not admin/manager, only show their own data
        if (!$user->isAdmin() && !$user->isManager() && !$user->isSupport()) {
            $query->where('user_id', $user->id);
        }

        switch ($type) {
            case 'Daily':
                $data = $query->where('created_at', '>=', now()->subDays(30))
                    ->select(\DB::raw('DATE(created_at) as label'), \DB::raw('SUM(amount) as revenue'), \DB::raw('SUM(quantity) as vouchers'))
                    ->groupBy('label')
                    ->orderBy('label')
                    ->get();
                break;
            case 'Weekly':
                $data = $query->where('created_at', '>=', now()->subWeeks(12))
                    ->select(\DB::raw('YEARWEEK(created_at) as label'), \DB::raw('SUM(amount) as revenue'), \DB::raw('SUM(quantity) as vouchers'))
                    ->groupBy('label')
                    ->orderBy('label')
                    ->get();
                break;
            case 'Monthly':
                $data = $query->where('created_at', '>=', now()->subMonths(12))
                    ->select(\DB::raw('DATE_FORMAT(created_at, "%b %Y") as label'), \DB::raw('SUM(amount) as revenue'), \DB::raw('SUM(quantity) as vouchers'))
                    ->groupBy(\DB::raw('YEAR(created_at), MONTH(created_at), label'))
                    ->orderBy(\DB::raw('YEAR(created_at), MONTH(created_at)'))
                    ->get();
                break;
            case 'Yearly':
                $data = $query->where('created_at', '>=', now()->subYears(5))
                    ->select(\DB::raw('YEAR(created_at) as label'), \DB::raw('SUM(amount) as revenue'), \DB::raw('SUM(quantity) as vouchers'))
                    ->groupBy('label')
                    ->orderBy('label')
                    ->get();
                break;
            default:
                return response()->json(['error' => 'Invalid type'], 400);
        }

        return response()->json($data);
    }
}
