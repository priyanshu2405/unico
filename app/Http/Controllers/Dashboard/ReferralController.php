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
        $isAdmin = $user->isAdmin() || $user->isManager();

        $allPointTypes = [
            'b2b_referral', 'student_referral',
            'agent_monthly_bonus', 'agent_quarterly_bonus', 'agent_yearly_bonus', 'student_purchase_bonus',
            'redemption', 'reversal', 'negative_offset'
        ];

        if ($isAdmin) {
            $summary = ReferralService::getAdminReferralSummary($request->filled('user_id') ? (int) $request->user_id : null);
            $query = RewardPointsLedger::query()
                ->with(['user', 'order', 'order.inventoryVoucher'])
                ->whereIn('point_type', $allPointTypes);

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
        } else {
            $summary = ReferralService::getReferralSummary($user);
            $query = RewardPointsLedger::query()
                ->with(['order', 'order.inventoryVoucher'])
                ->where('user_id', $user->id)
                ->whereIn('point_type', $allPointTypes);
        }

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
        $referralHistory = $query->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.referral-point.referral-table', compact('referralHistory', 'summary', 'isAdmin'))->render();
        }

        return view('dashboard.referral-point.index', compact('summary', 'referralHistory', 'user', 'isAdmin'));
    }

    /**
     * Admin action to approve/release, hold, unhold, or cancel a point transaction
     */
    public function updatePointStatus(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser->isAdmin() && !$authUser->isManager()) {
            abort(403, 'Unauthorized action. Only Admin or Manager can modify point status.');
        }

        $request->validate([
            'action' => 'required|in:approve,release,hold,unhold,cancel',
            'notes' => 'nullable|string|max:255',
            'redeem_instantly' => 'nullable|boolean',
        ]);

        $ledger = RewardPointsLedger::findOrFail($id);
        $action = $request->action;
        $meta = $ledger->metadata ?? [];

        if (in_array($action, ['approve', 'release'])) {
            $ledger->status = 'available';
            $ledger->available_at = now();
            if ($ledger->points_remaining <= 0 && $ledger->points > 0) {
                $ledger->points_remaining = $ledger->points;
            }
            $meta['admin_action'] = 'approved_by_admin';
            $meta['admin_id'] = $authUser->id;
            $meta['admin_name'] = $authUser->name;
            $meta['admin_notes'] = $request->notes ?? 'Approved and released by Admin';
            $meta['admin_action_at'] = now()->toIso8601String();
            $message = "Point transaction #{$ledger->id} (" . number_format($ledger->points) . " pts) has been approved and released immediately.";

            // Save ledger before potential instant redemption
            $ledger->metadata = $meta;
            $ledger->save();

            // Instant wallet redemption if requested
            if ($request->boolean('redeem_instantly') && $ledger->points_remaining > 0) {
                $targetUser = $ledger->user ?? \App\Models\User::find($ledger->user_id);
                if ($targetUser) {
                    $redeemResult = \App\Services\PointsService::redeemPointsToWallet($targetUser, $ledger->points_remaining, $authUser);
                    if ($redeemResult['success']) {
                        $message .= " Points were also instantly converted and credited to {$targetUser->name}'s wallet!";
                        $ledger->refresh();
                    }
                }
            }
        } elseif ($action === 'hold') {
            $ledger->status = 'held';
            $meta['admin_action'] = 'held_by_admin';
            $meta['admin_id'] = $authUser->id;
            $meta['admin_name'] = $authUser->name;
            $meta['admin_notes'] = $request->notes ?? 'Placed on hold by Admin for review/dispute';
            $meta['admin_action_at'] = now()->toIso8601String();
            $message = "Point transaction #{$ledger->id} has been placed on hold (dispute/review).";
            $ledger->metadata = $meta;
            $ledger->save();
        } elseif ($action === 'unhold') {
            if ($ledger->available_at && now()->gte($ledger->available_at)) {
                $ledger->status = 'available';
            } else {
                $ledger->status = 'pending';
            }
            $meta['admin_action'] = 'unheld_by_admin';
            $meta['admin_id'] = $authUser->id;
            $meta['admin_action_at'] = now()->toIso8601String();
            $message = "Point transaction #{$ledger->id} hold has been lifted (status: {$ledger->status}).";
            $ledger->metadata = $meta;
            $ledger->save();
        } elseif ($action === 'cancel') {
            $ledger->status = 'cancelled';
            $ledger->points_remaining = 0;
            $meta['admin_action'] = 'cancelled_by_admin';
            $meta['admin_id'] = $authUser->id;
            $meta['admin_name'] = $authUser->name;
            $meta['admin_notes'] = $request->notes ?? 'Cancelled by Admin';
            $meta['admin_action_at'] = now()->toIso8601String();
            $message = "Point transaction #{$ledger->id} has been cancelled.";
            $ledger->metadata = $meta;
            $ledger->save();
        }

        if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $ledger->status,
                'points_remaining' => $ledger->points_remaining,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Redeem available points directly into user wallet (1 pt = PKR 1 converted)
     */
    public function redeemToWallet(Request $request)
    {
        $authUser = Auth::user();

        // If target_user_id is passed and actor is admin/manager, redeem for target user
        if ($request->filled('target_user_id') && ($authUser->isAdmin() || $authUser->isManager())) {
            $user = \App\Models\User::findOrFail($request->target_user_id);
        } else {
            $user = $authUser;
        }

        $points = $request->filled('points') ? (int) $request->points : null;
        $result = \App\Services\PointsService::redeemPointsToWallet($user, $points, $authUser);

        if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
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
        $isAdmin = $user->isAdmin() || $user->isManager();

        $query = RewardPointsLedger::query()
            ->with(['user', 'order', 'order.inventoryVoucher'])
            ->whereIn('point_type', [
                'b2b_referral', 'student_referral',
                'agent_monthly_bonus', 'agent_quarterly_bonus', 'agent_yearly_bonus', 'student_purchase_bonus',
                'redemption', 'reversal', 'negative_offset'
            ]);

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
        $filename = "referral_points_history_" . date('Ymd_His') . ".csv";
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
        $headers = array_merge($headers, ['Date', 'Type', 'Description', 'Order ID', 'Points', 'Points Remaining', 'Status', 'Release Date', 'Expiry Date']);
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
                $item->points_remaining,
                ucfirst($item->status),
                $item->available_at ? $item->available_at->format('Y-m-d H:i') : 'Immediate',
                $item->expires_at ? $item->expires_at->format('Y-m-d H:i') : 'N/A',
            ]);
            fputcsv($handle, $row);
        }

        fclose($handle);
        exit;
    }
}
