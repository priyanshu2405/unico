<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RefundRequest;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletLedger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $authUser = auth()->user();
        if (!$authUser->isAdmin() && !$authUser->can_process_refunds) {
            abort(403, 'Unauthorized action.');
        }

        $query = RefundRequest::with(['user', 'order'])->latest();

        if (($authUser->isManager() || $authUser->isSupport()) && !$authUser->isAdmin()) {
            if (!$authUser->can_view_all_countries) {
                $permitted = $authUser->permitted_countries ?? [];
                $query->whereHas('user', function ($q) use ($permitted) {
                    $q->whereIn('country', $permitted);
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('user_id', 'like', '%' . $request->user_id . '%');
            });
        }

        if ($request->filled('country')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('country', $request->country);
            });
        }

        if ($request->filled('state')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('state', $request->state);
            });
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('user', function ($sq) use ($request) {
                    $sq->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%')
                        ->orWhere('user_id', 'like', '%' . $request->search . '%');
                })->orWhere('order_id', 'like', '%' . $request->search . '%');
            });
        }

        $perPage = $request->get('per_page', 10);
        $refunds = $query->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.refunds.refunds-table', compact('refunds'))->render();
        }

        return view('dashboard.refunds.index', compact('refunds'));
    }

    public function adminExport(Request $request)
    {
        $authUser = auth()->user();
        if (!$authUser->isAdmin() && !$authUser->can_process_refunds) {
            abort(403, 'Unauthorized action.');
        }

        $query = RefundRequest::with('user')->latest();

        if (($authUser->isManager() || $authUser->isSupport()) && !$authUser->isAdmin()) {
            if (!$authUser->can_view_all_countries) {
                $permitted = $authUser->permitted_countries ?? [];
                $query->whereHas('user', function ($q) use ($permitted) {
                    $q->whereIn('country', $permitted);
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('user_id', 'like', '%' . $request->user_id . '%');
            });
        }
        if ($request->filled('country')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('country', $request->country);
            });
        }
        if ($request->filled('state')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('state', $request->state);
            });
        }

        $refunds = $query->get();
        $filename = "refunds_report_" . date('Ymd_His') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $columns = [
            'User ID', 'Name', 'Email', 'Country', 'State', 'Order ID', 
            'Amount', 'Status', 'Reason', 'User TRX ID', 'Admin TRX ID', 
            'Processed At', 'Bank Details'
        ];

        fputcsv($handle, $columns);

        foreach ($refunds as $refund) {
            fputcsv($handle, [
                $refund->user->user_id ?? 'N/A',
                $refund->user->name ?? 'N/A',
                $refund->user->email ?? 'N/A',
                $refund->user->country ?? 'N/A',
                $refund->user->state ?? 'N/A',
                $refund->order_id,
                $refund->amount,
                $refund->status,
                $refund->reason,
                $refund->user_transaction_id,
                $refund->transaction_id,
                $refund->processed_at,
                $refund->bank_details
            ]);
        }

        fclose($handle);
        exit;
    }

    public function userRefunds(Request $request)
    {
        $query = RefundRequest::where('user_id', Auth::id())->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 10);
        $refunds = $query->paginate($perPage)->withQueryString();

        // Fetch orders where voucher codes are NOT received (status != delivered)
        $eligibleOrders = Order::where('user_id', Auth::id())
          //  ->whereIn('status', ['delivered'])
            ->latest()
            ->get();

        return view('dashboard.refunds.user-index', compact('refunds', 'eligibleOrders'));
    }

    public function userExport(Request $request)
    {
        $query = RefundRequest::where('user_id', Auth::id())->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $refunds = $query->get();
        $filename = "my_refunds_" . date('Ymd_His') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $columns = [
            'Date', 'Order ID', 'Amount', 'Reason', 'Status', 'Admin Proof ID', 'Processed At', 'Admin Note'
        ];

        fputcsv($handle, $columns);

        foreach ($refunds as $refund) {
            fputcsv($handle, [
                $refund->created_at->format('Y-m-d'),
                $refund->order_id,
                $refund->amount,
                $refund->reason,
                $refund->status,
                $refund->transaction_id ?? 'N/A',
                $refund->processed_at ?? 'N/A',
                $refund->admin_note ?? 'N/A'
            ]);
        }

        fclose($handle);
        exit;
    }

    public function store(Request $request)
    {
        $order = Order::where('order_id', $request->order_id)->first();
        $isManual = !in_array($order->payment_method ?? '', ['stripe', 'paypal', 'wallet']);

        $rules = [
            'order_id' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string',
            'user_transaction_id' => 'required|string',
        ];

        if ($isManual) {
            $rules['bank_details'] = 'required|string';
            $rules['transaction_slip'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
        } else {
            $rules['bank_details'] = 'nullable|string';
            $rules['transaction_slip'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $request->validate($rules);

        $slipPath = null;
        if ($request->hasFile('transaction_slip')) {
            $slipPath = $request->file('transaction_slip')->store('refund_slips', 'public');
        }

        RefundRequest::create([
            'user_id' => Auth::id(),
            'order_id' => $request->order_id,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'bank_details' => $request->bank_details ?? 'N/A (Automated Payment)',
            'user_transaction_id' => $request->user_transaction_id,
            'transaction_slip' => $slipPath,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Refund request submitted successfully.');
    }

   
 public function processRefund(Request $request, $id)
 {
    $refund = RefundRequest::with(['order', 'user'])->findOrFail($id);
    $authUser = auth()->user();
    if (!$authUser->isAdmin()) {
        if (!$authUser->can_process_refunds) {
            abort(403, 'Unauthorized action.');
        }
        if (!$authUser->can_view_all_countries) {
            $permitted = $authUser->permitted_countries ?? [];
            if (!in_array($refund->user->country ?? '', $permitted)) {
                abort(403, 'Unauthorized action.');
            }
        }
    }
    $method = $refund->order->payment_method ?? '';
    $isManual = !in_array($method, ['stripe', 'paypal', 'wallet']);

    $rules = [
        'transaction_id' => $isManual ? 'required|string' : 'nullable|string',
        'processed_date' => 'required|date',
        'processed_time' => 'required',
    ];

    $rules['refund_receipt'] = $isManual
        ? 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        : 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';

    $request->validate($rules);

    DB::transaction(function () use ($request, $refund, $method) {

        $receiptPath = $refund->refund_receipt;

        if ($request->hasFile('refund_receipt')) {
            $receiptPath = $request->file('refund_receipt')->store('refund_receipts', 'public');
        }

        $processedAt = $request->processed_date . ' ' . $request->processed_time;

        // 👉 SAFE transaction id
        $transactionId = $refund->transaction_id
            ?? 'AUTO-' . strtoupper(Str::random(10));

        $refund->update([
            'status' => 'approved',
            'transaction_id' => $transactionId,
            'refund_receipt' => $receiptPath,
            'processed_at' => $processedAt,
        ]);

        // Wallet refund
        if ($method === 'wallet') {

            $user = User::find($refund->user_id);

            if ($user) {
                $user->wallet_balance += $refund->amount;
                $user->save();

                // 👉 DUPLICATE SAFE LOGIC
                if (!WalletLedger::where('transaction_id', $transactionId)->exists()) {
                    WalletLedger::create([
                        'transaction_id' => $transactionId,
                        'user_id' => $user->id,
                        'type' => 'credit',
                        'amount' => $refund->amount,
                        'source' => 'wallet',
                        'description' => 'Refund for Order #' . $refund->order_id,
                    ]);
                }
            }
        }
    });

    return back()->with('success', 'Refund processed successfully.');
}

    public function approve($id)
    {
        $refund = RefundRequest::with('user')->findOrFail($id);
        $authUser = auth()->user();
        if (!$authUser->isAdmin()) {
            if (!$authUser->can_process_refunds) {
                abort(403, 'Unauthorized action.');
            }
            if (!$authUser->can_view_all_countries) {
                $permitted = $authUser->permitted_countries ?? [];
                if (!in_array($refund->user->country ?? '', $permitted)) {
                    abort(403, 'Unauthorized action.');
                }
            }
        }
        $refund->update(['status' => 'approved']);
        return back()->with('success', 'Refund request approved.');
    }

    public function reject(Request $request, $id)
    {
        $refund = RefundRequest::with('user')->findOrFail($id);
        $authUser = auth()->user();
        if (!$authUser->isAdmin()) {
            if (!$authUser->can_process_refunds) {
                abort(403, 'Unauthorized action.');
            }
            if (!$authUser->can_view_all_countries) {
                $permitted = $authUser->permitted_countries ?? [];
                if (!in_array($refund->user->country ?? '', $permitted)) {
                    abort(403, 'Unauthorized action.');
                }
            }
        }
        $refund->update([
            'status' => 'rejected',
            'admin_note' => $request->note
        ]);
        return back()->with('success', 'Refund request rejected.');
    }
}
