<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RewardPointsLedger;
use App\Models\Order;
use App\Services\PointsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BonusController extends Controller
{
    /**
     * Display Monthly, Quarterly, and Yearly Bonus Points Dashboard
     */
    public function bonus(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin() || $user->isManager();

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfQuarter = $now->copy()->firstOfQuarter();
        $startOfYear = $now->copy()->startOfYear();

        // Next release dates as per specifications
        $nextMonthlyRelease = $now->copy()->addMonthNoOverflow()->startOfMonth()->addDays(6); // 7th of next month
        $nextQuarterMonth = ((int) ceil($now->month / 3) * 3) + 1;
        $nextQuarterRelease = Carbon::create($now->year, $nextQuarterMonth <= 12 ? $nextQuarterMonth : 1, 7)->startOfDay();
        if ($nextQuarterMonth > 12) {
            $nextQuarterRelease->addYear();
        }
        $nextYearlyRelease = Carbon::create($now->year + 1, 1, 7)->startOfDay();

        $targetUserId = null;
        if ($isAdmin) {
            $targetUserId = $request->filled('user_id') ? (int) $request->user_id : null;
        } else {
            $targetUserId = $user->id;
        }

        // Units and accruals query
        $unitsOrderQuery = Order::where('status', 'delivered')->where('is_full_points_redemption', false);
        $accruedLedgerQuery = RewardPointsLedger::whereIn('point_type', ['agent_monthly_bonus', 'agent_quarterly_bonus', 'agent_yearly_bonus', 'student_purchase_bonus']);

        if ($targetUserId) {
            $unitsOrderQuery->where('user_id', $targetUserId);
            $accruedLedgerQuery->where('user_id', $targetUserId);
        }

        $currentMonthUnits = (clone $unitsOrderQuery)->where('created_at', '>=', $startOfMonth)->sum('quantity');
        $currentQuarterUnits = (clone $unitsOrderQuery)->where('created_at', '>=', $startOfQuarter)->sum('quantity');
        $currentYearUnits = (clone $unitsOrderQuery)->where('created_at', '>=', $startOfYear)->sum('quantity');

        $accruedMonthly = (int) (clone $accruedLedgerQuery)->where('point_type', 'agent_monthly_bonus')->where('status', 'pending')->sum('points');
        $accruedQuarterly = (int) (clone $accruedLedgerQuery)->where('point_type', 'agent_quarterly_bonus')->where('status', 'pending')->sum('points');
        $accruedYearly = (int) (clone $accruedLedgerQuery)->where('point_type', 'agent_yearly_bonus')->where('status', 'pending')->sum('points');
        $totalHeld = (int) (clone $accruedLedgerQuery)->where('status', 'held')->sum('points');

        $bonusStats = [
            'month_units' => $currentMonthUnits,
            'quarter_units' => $currentQuarterUnits,
            'year_units' => $currentYearUnits,
            'accrued_monthly' => $accruedMonthly,
            'accrued_quarterly' => $accruedQuarterly,
            'accrued_yearly' => $accruedYearly,
            'total_held' => $totalHeld,
            'next_monthly_release' => $nextMonthlyRelease,
            'next_quarterly_release' => $nextQuarterRelease,
            'next_yearly_release' => $nextYearlyRelease,
        ];

        if ($isAdmin) {
            $pointsBalance = [
                'net_available_points' => (int) (clone $accruedLedgerQuery)->where('status', 'available')->sum('points_remaining'),
                'pending_points' => (int) (clone $accruedLedgerQuery)->where('status', 'pending')->sum('points'),
                'redeemed_points' => (int) abs(RewardPointsLedger::where('point_type', 'redemption')->sum('points')),
                'next_expiring_points' => 0,
            ];
            $usersList = \App\Models\User::whereIn('account_type', ['agent', 'reseller_agent', 'student'])
                ->select('id', 'name', 'email', 'account_type', 'user_id')
                ->orderBy('name')
                ->get();
        } else {
            $pointsBalance = PointsService::getUserPointsBalance($user);
            $usersList = collect();
        }

        // Bonus Ledger History
        $query = RewardPointsLedger::query()
            ->with(['user', 'order', 'order.inventoryVoucher'])
            ->whereIn('point_type', ['agent_monthly_bonus', 'agent_quarterly_bonus', 'agent_yearly_bonus', 'student_purchase_bonus']);

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('point_type')) {
            $query->where('point_type', $request->point_type);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $isAdmin) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('order_id', 'like', "%{$search}%");
                  });
                if ($isAdmin) {
                    $q->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%")
                           ->orWhere('user_id', 'like', "%{$search}%");
                    });
                }
            });
        }

        $perPage = $request->get('per_page', 15);
        $bonusHistory = $query->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.bonus-point.bonus-table', compact('bonusHistory', 'isAdmin'))->render();
        }

        return view('dashboard.bonus-point.index', compact('bonusHistory', 'bonusStats', 'pointsBalance', 'user', 'isAdmin', 'usersList'));
    }

    /**
     * Export bonus points history to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin() || $user->isManager();

        $query = RewardPointsLedger::query()
            ->with(['user', 'order', 'order.inventoryVoucher'])
            ->whereIn('point_type', ['agent_monthly_bonus', 'agent_quarterly_bonus', 'agent_yearly_bonus', 'student_purchase_bonus']);

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('point_type')) {
            $query->where('point_type', $request->point_type);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $history = $query->latest()->get();
        $filename = "bonus_points_history_" . date('Ymd_His') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $headers = ['S.No'];
        if ($isAdmin) {
            $headers[] = 'User';
            $headers[] = 'User Email';
            $headers[] = 'User Role';
        }
        $headers = array_merge($headers, ['Date', 'Bonus Category', 'Description', 'Order ID', 'Points', 'Status', 'Release Date', 'Expiry Date']);
        fputcsv($handle, $headers);

        foreach ($history as $index => $item) {
            $row = [$index + 1];
            if ($isAdmin) {
                $row[] = $item->user->name ?? 'N/A';
                $row[] = $item->user->email ?? 'N/A';
                $row[] = ucfirst(str_replace('_', ' ', $item->user->account_type ?? 'N/A'));
            }
            $row = array_merge($row, [
                $item->created_at ? $item->created_at->format('Y-m-d H:i') : 'N/A',
                ucfirst(str_replace('_', ' ', $item->point_type)),
                $item->description,
                $item->order->order_id ?? 'N/A',
                $item->points,
                ucfirst($item->status),
                $item->available_at ? $item->available_at->format('Y-m-d') : 'N/A',
                $item->expires_at ? $item->expires_at->format('Y-m-d') : 'N/A',
            ]);
            fputcsv($handle, $row);
        }

        fclose($handle);
        exit;
    }
}
