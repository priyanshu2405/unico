<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InventoryVoucher;
use App\Models\VoucherPriceRule;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function create(Request $request, $rule)
    {
        $voucher = InventoryVoucher::findOrFail($rule);
        $user = auth()->user();

        if (!$request->filled('quantity')) {
            return response()->json(['error' => 'Quantity required'], 400);
        }

        $quantity = (int) $request->quantity;
        $unitPrice = $user->isStudent() ? $voucher->student_sale_price : $voucher->agent_sale_price;
        $amount = $unitPrice * $quantity;

        try {
            // ✅ Use sandbox full endpoint
            $baseUrl = 'https://sandbox.api.getsafepay.com/orders';
            $secretKey = env('SAFEPAY_SECRET_KEY'); // secret key from .env

            // Log request
            Log::info('SafePay Request:', [
                'url' => $baseUrl,
                'amount' => (int)($amount * 100),
                'currency' => 'PKR',
                'description' => $voucher->brand_name . ' x' . $quantity,
            ]);

            // Prepare payload
            $payload = [
                'amount' => (int)($amount * 100), // in smallest currency unit
                'currency' => 'PKR',
                'description' => $voucher->brand_name . ' x' . $quantity,
                'success_url' => route('safepay.success'),
                'failed_url' => route('vouchers.order', $voucher->id),
                'webhook_url' => route('safepay.webhook'),
                'metadata' => [
                    'rule_id' => $voucher->id,
                    'quantity' => $quantity,
                    'user_id' => $user->id,
                ],
            ];

            // API call
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post($baseUrl, $payload);

            // Log raw response for debugging
            Log::info('SafePay Raw Response:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            // Parse JSON safely
            $data = $response->json();
            if (!$data) {
                // JSON null → probably HTML, log for debug
                $data = ['error' => $response->body()];
            }

            Log::info('SafePay Parsed Data:', ['data' => $data, 'type' => gettype($data)]);

            if ($response->successful() && isset($data['data']['id'])) {

                session(['safepay_order' => [
                    'rule_id' => $voucher->id,
                    'quantity' => $quantity,
                    'amount' => $amount,
                    'safepay_order_id' => $data['data']['id'],
                ]]);

                return response()->json([
                    'success' => true,
                    'redirect_url' => $data['data']['checkout_url'] ?? null
                ]);
            } else {
                Log::error('SafePay Order Creation Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'parsed' => $data,
                ]);

                $errorMsg = is_array($data) ? ($data['message'] ?? $data['error'] ?? 'Payment order failed') : $data;

                return response()->json(['error' => $errorMsg], 400);
            }
        } catch (\Exception $e) {
            Log::error('SafePay API Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Payment gateway error: ' . $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        $safepayData = session('safepay_order');

        if (!$safepayData) {
            return redirect()->route('vouchers')
                ->with('error', 'Payment session expired');
        }

        try {
            $rule = VoucherPriceRule::with('inventoryVoucher')->findOrFail($safepayData['rule_id']);
            $voucher = $rule->inventoryVoucher;
            $user = auth()->user();

            $order = Order::create([
                'order_id' => 'PUR-' . strtoupper(\Illuminate\Support\Str::random(10)),
                'user_id' => $user->id,
                'user_role' => $user->account_type,
                'voucher_type' => $voucher->voucher_type,
                'voucher_id' => $voucher->sku_id,
                'quantity' => $safepayData['quantity'],
                'amount' => $safepayData['amount'],
                'status' => 'completed',
                'payment_method' => 'safepay',
                'referral_points' => 0,
                'bonus_amount' => 0,
            ]);

            $voucher->decrement('quantity', $safepayData['quantity']);

            session()->forget('safepay_order');

            return redirect()->route('orders.history')
                ->with('success', 'Payment successful! Order #' . $order->order_id);
        } catch (\Exception $e) {
            Log::error('SafePay Success Handler Error: ' . $e->getMessage());
            return redirect()->route('vouchers')
                ->with('error', 'Error processing payment');
        }
    }

    public function webhook(Request $request)
    {
        Log::info('SafePay Webhook', $request->all());

        if ($request->status === 'COMPLETED') {
            // Order was already created in success endpoint
        }

        return response()->json(['status' => 'ok']);
    }
    public function PaymentTable(Request $request)
    {
        if (auth()->user()->isManager() && !auth()->user()->can_view_sales_report) {
            abort(403, 'Unauthorized access to sales reports.');
        }
        if (auth()->user()->isSupport() && !auth()->user()->can_view_sales_report) {
            abort(403, 'Unauthorized access to sales reports.');
        }

        $query = Order::with(['user', 'inventoryVoucher']);

        // Filter by Date
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by Transaction ID
        if ($request->filled('transaction_id')) {
            $query->where('transaction_id', 'like', "%{$request->transaction_id}%");
        }

        // Filter by Country & State
        if ($request->anyFilled(['country', 'state'])) {
            $query->whereHas('user', function ($q) use ($request) {
                if ($request->filled('country')) {
                    $q->where('country', $request->country);
                }
                if ($request->filled('state')) {
                    $q->where('state', $request->state);
                }
            });
        }

        // Filter by Brand Name
        if ($request->filled('brand_name')) {
            $query->whereHas('inventoryVoucher', function ($q) use ($request) {
                $q->where('brand_name', 'like', "%{$request->brand_name}%");
            });
        }

        // Filter by Voucher Type
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        // Filter by Currency
        if ($request->filled('currency')) {
            $query->whereHas('inventoryVoucher', function ($q) use ($request) {
                $q->where('currency', $request->currency);
            });
        }

        $perPage = $request->get('per_page', 15);
        $payments = $query->latest()->paginate($perPage)->withQueryString();

        // Fetch unique values for filters specifically from existing orders
        $countries = Order::join('users', 'orders.user_id', '=', 'users.id')
            ->whereNotNull('users.country')
            ->distinct()
            ->pluck('users.country')
            ->sort();

        $states = Order::join('users', 'orders.user_id', '=', 'users.id')
            ->whereNotNull('users.state')
            ->distinct()
            ->pluck('users.state')
            ->sort();

        $brands = Order::join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
            ->distinct()
            ->pluck('inventory_vouchers.brand_name')
            ->sort();

        $voucherTypes = Order::distinct()
            ->pluck('voucher_type')
            ->sort();

        $currencies = Order::join('inventory_vouchers', 'orders.voucher_id', '=', 'inventory_vouchers.sku_id')
            ->distinct()
            ->pluck('inventory_vouchers.currency')
            ->sort();

        return view('dashboard.reports.payments', compact('payments', 'countries', 'states', 'brands', 'voucherTypes', 'currencies'));
    }

    public function exportPaymentReport(Request $request)
    {
        if (auth()->user()->isManager() && !auth()->user()->can_view_sales_report) {
            abort(403, 'Unauthorized access to sales reports.');
        }
        if (auth()->user()->isSupport() && !auth()->user()->can_view_sales_report) {
            abort(403, 'Unauthorized access to sales reports.');
        }

        $query = Order::with(['user', 'inventoryVoucher']);

        // Apply same filters as PaymentTable
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->filled('transaction_id')) {
            $query->where('transaction_id', 'like', "%{$request->transaction_id}%");
        }
        if ($request->anyFilled(['country', 'state'])) {
            $query->whereHas('user', function ($q) use ($request) {
                if ($request->filled('country')) {
                    $q->where('country', $request->country);
                }
                if ($request->filled('state')) {
                    $q->where('state', $request->state);
                }
            });
        }
        if ($request->filled('brand_name')) {
            $query->whereHas('inventoryVoucher', function ($q) use ($request) {
                $q->where('brand_name', 'like', "%{$request->brand_name}%");
            });
        }
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }
        if ($request->filled('currency')) {
            $query->whereHas('inventoryVoucher', function ($q) use ($request) {
                $q->where('currency', $request->currency);
            });
        }

        $records = $query->latest()->get();

        $filename = "payments_report_" . date('Ymd_His') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $columns = [
            'Sr. No.',
            'Payment ID',
            'Transaction ID',
            'Date',
            'Time',
            'Buyer Name',
            'Buyer Type',
            'Local Currency',
            'Country',
            'State',
            'Brand Name',
            'Voucher Variant',
            'Voucher Type',
            'Sale Invoice No.',
            'Quantity',
            'Sales Value',
            'Payment Receive Bank',
            'Currency Conversion',
            'FX Gain/Loss',
            'Referral Points',
            'Bonus Points'
        ];

        fputcsv($handle, $columns);

        foreach ($records as $index => $payment) {
            $localCurrency = optional($payment->inventoryVoucher)->local_currency ?? 'PKR';
            $conversionRate = optional($payment->inventoryVoucher)->currency_conversion_rate ?? 1.0;
            $purchaseValuePerUnit = optional($payment->inventoryVoucher)->purchase_value_per_unit ?? 0;
            $totalPurchaseValue = $purchaseValuePerUnit * $payment->quantity;
            $fxGainLoss = $payment->amount - $totalPurchaseValue;

            fputcsv($handle, [
                $index + 1,
                $payment->order_id,
                $payment->transaction_id ?? 'N/A',
                $payment->created_at->format('Y-m-d'),
                $payment->created_at->format('H:i:s'),
                $payment->user->name ?? 'N/A',
                ucfirst(str_replace('_', ' ', $payment->user_role)),
                $localCurrency,
                $payment->user->country ?? 'N/A',
                $payment->user->state ?? 'N/A',
                optional($payment->inventoryVoucher)->brand_name ?? 'N/A',
                optional($payment->inventoryVoucher)->voucher_variant ?? 'N/A',
                $payment->voucher_type,
                $payment->order_id,
                $payment->quantity,
                number_format($payment->amount, 2),
                $payment->bank_name ?? $payment->payment_method,
                number_format($conversionRate, 4),
                number_format($fxGainLoss, 2),
                $payment->referral_points,
                $payment->bonus_amount
            ]);
        }

        fclose($handle);
        exit;
    }
}
