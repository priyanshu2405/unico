<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RefundRequest;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletLedger;
use App\Services\CurrencyService;
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
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $columns = [
            'User ID', 'Name', 'Email', 'Country', 'State', 'Order ID', 
            'Currency', 'Amount (Local)', 'Exchange Rate (to USD)', 'Amount in Base Currency (USD)',
            'Status', 'Reason', 'User TRX ID', 'Admin TRX ID', 
            'Processed At', 'Bank Details'
        ];

        fputcsv($handle, $columns);

        foreach ($refunds as $refund) {
            $refCurr = $refund->currency ?: ($refund->order->currency ?? 'USD');
            $conv = CurrencyService::convertToBase((float)$refund->amount, $refCurr);
            $rate = (float)$conv['exchange_rate'];
            $amtUsd = (float)$conv['base_amount'];

            fputcsv($handle, [
                $refund->user->user_id ?? 'N/A',
                $refund->user->name ?? 'N/A',
                $refund->user->email ?? 'N/A',
                $refund->user->country ?? 'N/A',
                $refund->user->state ?? 'N/A',
                $refund->order_id,
                $refCurr,
                $refCurr . ' ' . number_format((float)$refund->amount, 2),
                number_format($rate, 4),
                '$ ' . number_format($amtUsd, 2),
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
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $columns = [
            'Date', 'Order ID', 'Currency', 'Amount', 'Reason', 'Status', 'Admin Proof ID', 'Processed At', 'Admin Note'
        ];

        fputcsv($handle, $columns);

        foreach ($refunds as $refund) {
            fputcsv($handle, [
                $refund->created_at->format('Y-m-d'),
                $refund->order_id,
                $refund->order->currency ?? 'USD',
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

        $currency = $order ? ($order->currency ?? 'USD') : 'USD';
        $usd = 0.0;
        $rate = 1.0;

        if ($order && $order->amount_in_base_currency !== null && (float)$order->amount_in_base_currency > 0) {
            $orderAmt = (float)$order->amount;
            $ratio = $orderAmt > 0 ? min(1.0, (float)$request->amount / $orderAmt) : 1.0;
            $usd = (float)$order->amount_in_base_currency * $ratio;
            $rate = $usd > 0 && $request->amount > 0 ? $usd / $request->amount : 1.0;
        } else {
            $conv = \App\Services\CurrencyService::convertToBase((float)$request->amount, $currency);
            $usd = (float)$conv['base_amount'];
            $rate = (float)$conv['exchange_rate'];
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
            'currency' => $currency,
            'amount_in_base_currency' => $usd,
            'exchange_rate_applied' => $rate,
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
            'approved_by' => auth()->id(),
        ]);

        // Wallet refund
        if ($method === 'wallet') {
            $user = User::find($refund->user_id);

            if ($user) {
                $refCurr = $refund->currency ?: ($refund->order->currency ?? 'USD');
                // Use order's snapshot USD amount when available for consistent refund
                $order = $refund->order;
                if ($order && $order->amount_in_base_currency !== null && (float)$order->amount_in_base_currency > 0) {
                    $orderAmt = (float)$order->amount;
                    $ratio = $orderAmt > 0 ? min(1.0, (float)$refund->amount / $orderAmt) : 1.0;
                    $amountInUSD = (float)$order->amount_in_base_currency * $ratio;
                } else {
                    $refCurr = $order ? ($order->currency ?? 'USD') : ($refund->currency ?: 'USD');
                    $amountInUSD = CurrencyService::convertToBase((float)$refund->amount, $refCurr)['base_amount'];
                }

                if ($amountInUSD > 0) {
                    \App\Services\WalletService::credit(
                        $user,
                        $amountInUSD,
                        'order_refund',
                        'Refund for Order #' . $refund->order_id
                    );

                    $admin = User::where('account_type', 'admin')->first();
                    if ($admin && $admin->wallet_balance >= $amountInUSD) {
                        \App\Services\WalletService::debit(
                            $admin,
                            $amountInUSD,
                            'order_refund_reversal',
                            'Reversal of revenue for refunded Order #' . $refund->order_id
                        );
                    }
                }
            }
        }

        if ($refund->order) {
            \App\Services\ReferralService::handleOrderRefund($refund->order, (float) $refund->amount);
        }
    });

    return back()->with('success', 'Refund processed successfully.');
}

    public function approve($id)
    {
        $refund = RefundRequest::with(['user', 'order'])->findOrFail($id);
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

        if ($refund->status === 'approved') {
            return back()->with('error', 'Refund request already approved.');
        }

        $method = $refund->order->payment_method ?? '';
        $transactionId = $refund->transaction_id ?? 'AUTO-' . strtoupper(Str::random(10));

        $refund->update([
            'status' => 'approved',
            'transaction_id' => $transactionId,
            'processed_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        if ($method === 'wallet') {
            $user = User::find($refund->user_id);
            if ($user) {
                // Use order's locked USD snapshot when available (same pattern as processRefund)
                $order = $refund->order;
                if ($order && $order->amount_in_base_currency !== null && (float)$order->amount_in_base_currency > 0) {
                    $orderAmt = (float)$order->amount;
                    $ratio = $orderAmt > 0 ? min(1.0, (float)$refund->amount / $orderAmt) : 1.0;
                    $amountInUSD = (float)$order->amount_in_base_currency * $ratio;
                } else {
                    $refCurr = $order ? ($order->currency ?? 'USD') : ($refund->currency ?? 'USD');
                    $amountInUSD = CurrencyService::convertToBase((float)$refund->amount, $refCurr)['base_amount'];
                }

                if ($amountInUSD > 0) {
                    try {
                        \App\Services\WalletService::credit(
                            $user,
                            $amountInUSD,
                            'order_refund',
                            'Refund for Order #' . $refund->order_id
                        );

                        $admin = User::where('account_type', 'admin')->first();
                        if ($admin && $admin->wallet_balance >= $amountInUSD) {
                            \App\Services\WalletService::debit(
                                $admin,
                                $amountInUSD,
                                'order_refund_reversal',
                                'Reversal of revenue for refunded Order #' . $refund->order_id
                            );
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Wallet refund error: " . $e->getMessage());
                    }
                }
            }
        }

        if ($refund->order) {
            \App\Services\ReferralService::handleOrderRefund($refund->order, (float) $refund->amount);
        }

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
