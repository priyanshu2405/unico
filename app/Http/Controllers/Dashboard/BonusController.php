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
        $pointsBalance = PointsService::getUserPointsBalance($user);

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfQuarter = $now->copy()->firstOfQuarter();
        $startOfYear = $now->copy()->startOfYear();

        // Qualifying units in current periods
        $currentMonthUnits = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $startOfMonth)
            ->where('is_full_points_redemption', false)
            ->sum('quantity');

        $currentQuarterUnits = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $startOfQuarter)
            ->where('is_full_points_redemption', false)
            ->sum('quantity');

        $currentYearUnits = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $startOfYear)
            ->where('is_full_points_redemption', false)
            ->sum('quantity');

        // Next release dates as per specifications
        $nextMonthlyRelease = $now->copy()->addMonthNoOverflow()->startOfMonth()->addDays(6); // 7th of next month
        $nextQuarterMonth = ((int) ceil($now->month / 3) * 3) + 1;
        $nextQuarterRelease = Carbon::create($now->year, $nextQuarterMonth <= 12 ? $nextQuarterMonth : 1, 7)->startOfDay();
        if ($nextQuarterMonth > 12) {
            $nextQuarterRelease->addYear();
        }
        $nextYearlyRelease = Carbon::create($now->year + 1, 1, 7)->startOfDay();

        // Accrued bonus points
        $accruedMonthly = (int) RewardPointsLedger::where('user_id', $user->id)
            ->where('point_type', 'agent_monthly_bonus')
            ->where('status', 'pending')
            ->sum('points');

        $accruedQuarterly = (int) RewardPointsLedger::where('user_id', $user->id)
            ->where('point_type', 'agent_quarterly_bonus')
            ->where('status', 'pending')
            ->sum('points');

        $accruedYearly = (int) RewardPointsLedger::where('user_id', $user->id)
            ->where('point_type', 'agent_yearly_bonus')
            ->where('status', 'pending')
            ->sum('points');

        $bonusStats = [
            'month_units' => $currentMonthUnits,
            'quarter_units' => $currentQuarterUnits,
            'year_units' => $currentYearUnits,
            'accrued_monthly' => $accruedMonthly,
            'accrued_quarterly' => $accruedQuarterly,
            'accrued_yearly' => $accruedYearly,
            'next_monthly_release' => $nextMonthlyRelease,
            'next_quarterly_release' => $nextQuarterRelease,
            'next_yearly_release' => $nextYearlyRelease,
        ];

        // Bonus Ledger History
        $query = RewardPointsLedger::query()
            ->with(['order', 'order.inventoryVoucher'])
            ->where('user_id', $user->id)
            ->whereIn('point_type', ['agent_monthly_bonus', 'agent_quarterly_bonus', 'agent_yearly_bonus', 'student_purchase_bonus']);

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

        $perPage = $request->get('per_page', 15);
        $bonusHistory = $query->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.bonus-point.bonus-table', compact('bonusHistory'))->render();
        }

        return view('dashboard.bonus-point.index', compact('bonusHistory', 'bonusStats', 'pointsBalance', 'user'));
    }

    /**
     * Export bonus points history to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $query = RewardPointsLedger::query()
            ->with(['order', 'order.inventoryVoucher'])
            ->where('user_id', $user->id)
            ->whereIn('point_type', ['agent_monthly_bonus', 'agent_quarterly_bonus', 'agent_yearly_bonus', 'student_purchase_bonus']);

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

        fputcsv($handle, ['S.No', 'Date', 'Bonus Category', 'Description', 'Order ID', 'Points', 'Status', 'Release Date', 'Expiry Date']);

        foreach ($history as $index => $item) {
            fputcsv($handle, [
                $index + 1,
                $item->created_at ? $item->created_at->format('Y-m-d H:i') : 'N/A',
                ucfirst(str_replace('_', ' ', $item->point_type)),
                $item->description,
                $item->order->order_id ?? 'N/A',
                $item->points,
                ucfirst($item->status),
                $item->available_at ? $item->available_at->format('Y-m-d') : 'N/A',
                $item->expires_at ? $item->expires_at->format('Y-m-d') : 'N/A',
            ]);
        }

        fclose($handle);
        exit;
    }
}
