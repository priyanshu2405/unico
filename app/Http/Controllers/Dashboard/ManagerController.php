<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Vouchar;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Mail\UserApproved;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'pending_approvals' => User::where('profile_verification_status', 'pending')->count(),
            'active_vouchers' => \App\Models\InventoryVoucher::where('status', 'active')->sum('quantity'),
            'low_stock_alerts' => \App\Models\InventoryVoucher::where('quantity', '<=', 5)->count(),
        ];

        return view('dashboard.manager_dashboard', compact('stats'));
    }

    public function auditTransactions(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $logs = AuditLog::with('user')->latest()->paginate($perPage)->withQueryString();
        return view('dashboard.audit.index', compact('logs'));
    }

    public function manageUsers(Request $request)
    {
        $query = User::where('account_type', '!=', 'admin');
        $perPage = $request->get('per_page', 15);
        $users = $query->latest()->paginate($perPage)->withQueryString();
        return view('dashboard.users.index', compact('users'));
    }

    public function approveDetails(User $user)
    {
        return view('dashboard.approvals.show', compact('user'));
    }

    public function approveUser(User $user)
    {
        $user->update([
            'profile_verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => auth()->id()
        ]);

        try {
            Mail::to($user->email)->send(new UserApproved($user));
        } catch (\Exception $e) {
            // Handle mail error
        }

        return redirect()->route('manager.users')->with('success', 'User approved successfully.');
    }

    public function voucherStock()
    {
        return view('dashboard.voucher.stock');
    }

    public function disputes()
    {
        return view('dashboard.disputes_index');
    }

    public function addCredit(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:300',
        ]);

        \App\Services\WalletService::credit(
            $user,
            (float)$request->amount,
            'manager_credit',
            'Manager credit added by ' . auth()->user()->name
        );
        
        return back()->with('success', 'USD ' . number_format($request->amount, 2) . ' credit added to ' . $user->first_name . '\'s account.');
    }

    public function stopSystem()
    {
        $settings = SystemSetting::all()->pluck('settings_value', 'settings_key');
        $logs = AuditLog::where('action', 'like', 'System %')->latest()->limit(10)->get();
        return view('dashboard.system.control', compact('settings', 'logs'));
    }

    public function reports()
    {
        $stats = [
            'total_sales' => Order::where('status', 'delivered')->get()->sum(fn($o) => \App\Services\CurrencyService::getOrderBaseAmount($o)) ?? 0,
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_vouchers' => \App\Models\InventoryVoucher::sum('quantity'),
            'total_users' => User::count(),
        ];

        $recent_orders = Order::with(['user', 'voucher'])->latest()->limit(5)->get();

        return view('dashboard.reports.index', compact('stats', 'recent_orders'));
    }
}
