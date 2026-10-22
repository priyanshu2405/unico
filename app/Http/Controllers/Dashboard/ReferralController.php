<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\RewardPointsLedger;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    /**
     * Display the Referral Points and B2B Qualification Dashboard
     */
    public function referral(Request $request)
    {
        $user = Auth::user();
        $summary = ReferralService::getReferralSummary($user);

        $query = RewardPointsLedger::query()
            ->with(['order', 'order.inventoryVoucher'])
            ->where('user_id', $user->id)
            ->whereIn('point_type', ['b2b_referral', 'student_referral', 'reversal', 'negative_offset']);

        // Filters
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
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('order_id', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->get('per_page', 15);
        $referralHistory = $query->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.referral-point.referral-table', compact('referralHistory', 'summary'))->render();
        }

        return view('dashboard.referral-point.index', compact('summary', 'referralHistory', 'user'));
    }

    /**
     * Manually bind a referral code before the first completed purchase
     */
    public function bind(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|string',
        ]);

        $user = Auth::user();
        $result = ReferralService::bindReferral($user, $request->referral_code, true);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Export referral points history to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $query = RewardPointsLedger::query()
            ->with(['order', 'order.inventoryVoucher'])
            ->where('user_id', $user->id)
            ->whereIn('point_type', ['b2b_referral', 'student_referral', 'reversal', 'negative_offset']);

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
        $filename = "referral_points_history_" . date('Ymd_His') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        fputcsv($handle, ['S.No', 'Date', 'Type', 'Description', 'Order ID', 'Points', 'Points Remaining', 'Status', 'Release Date', 'Expiry Date']);

        foreach ($history as $index => $item) {
            fputcsv($handle, [
                $index + 1,
                $item->created_at ? $item->created_at->format('Y-m-d H:i') : 'N/A',
                ucfirst(str_replace('_', ' ', $item->point_type)),
                $item->description,
                $item->order->order_id ?? 'N/A',
                $item->points,
                $item->points_remaining,
                ucfirst($item->status),
                $item->available_at ? $item->available_at->format('Y-m-d H:i') : 'Immediate',
                $item->expires_at ? $item->expires_at->format('Y-m-d H:i') : 'N/A',
            ]);
        }

        fclose($handle);
        exit;
    }
}
