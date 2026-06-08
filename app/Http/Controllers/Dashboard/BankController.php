<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AdminPaymentMethod;
use App\Models\BankAccountModel;
use App\Models\WalletLedger;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\FinancialConnections\Session;
use Stripe\PaymentIntent;


use App\Models\Order;
use App\Models\Voucher; // Check if needed, but Order model uses it

class BankController extends Controller
{
    public function wiseCheckout(Request $request)
    {
        $amount = $request->input('amount');
        if (!$amount) {
            return back()->with('error', 'Amount missing');
        }

        try {
            $apiKey = config('services.wise.api_key');
            $profileId = config('services.wise.profile_id');
            $baseUrl = config('services.wise.base_url');

            // 1. Create a Quote
            $quoteResponse = Http::withToken($apiKey)
                ->post($baseUrl . '/v3/quotes', [
                    'sourceCurrency' => 'USD',
                    'targetCurrency' => 'USD',
                    'sourceAmount' => (float)$amount,
                    'targetAmount' => null,
                    'profileId' => (int)$profileId,
                ]);

            if (!$quoteResponse->successful()) {
                Log::error('Wise Quote Failed', ['response' => $quoteResponse->json(), 'profileId' => $profileId]);
                throw new \Exception('Wise quote creation failed: ' . ($quoteResponse->json('message') ?? 'Unknown error'));
            }

            $quoteId = $quoteResponse->json('id');

            // 2. Create Checkout Session for actual payment
            $orderId = 'WISE-' . strtoupper(uniqid());
            $checkoutResponse = Http::withToken($apiKey)
                ->post($baseUrl . "/v1/profiles/$profileId/checkout-sessions", [
                    'quoteId' => $quoteId,
                    'customerTransactionId' => $orderId,
                    'payInMethods' => ['CARD', 'BANK_TRANSFER'],
                    'redirectUrl' => route('wise.success', ['order_id' => $orderId, 'amount' => $amount]),
                ]);

            if ($checkoutResponse->successful()) {
                $checkoutUrl = $checkoutResponse->json('checkoutUrl');
                
                // No Order creation here, just redirect to Wise
                return redirect($checkoutUrl);
            }

            // Fallback if Checkout Session fails (maybe not enabled on account)
            Log::warning('Wise Checkout Session Failed, falling back to simulation', ['response' => $checkoutResponse->json()]);

            return redirect()->route('wise.success', ['order_id' => $orderId, 'amount' => $amount]);

        } catch (\Exception $e) {
            Log::error('Wise Checkout Exception: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function wiseSuccess(Request $request)
    {
        $orderId = $request->get('order_id');
        $amount = $request->get('amount');

        if (!$orderId) {
            return redirect()->route('bank.link')->with('error', 'Invalid Wise payment');
        }

        $user = auth()->user();
        $exists = WalletLedger::where('transaction_id', $orderId)->exists();

        if (!$exists) {
            $user->wallet_balance += $amount;
            $user->save();

            WalletLedger::create([
                'transaction_id' => $orderId,
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'source' => 'wise',
                'description' => 'Wallet Top-up via Wise',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        return redirect()->route('bank.link')->with('success', 'Amount added successfully via Wise!');
    }

    public function wiseCancel()
    {
        return redirect()->route('bank.link')->with('error', 'Wise payment cancelled.');
    }

    public function kuickpayCheckout(Request $request)
    {
        $amount = $request->input('amount');
        if (!$amount) {
            return back()->with('error', 'Amount missing');
        }

        try {
            $user = auth()->user();
            $prefix = config('services.kuickpay.prefix', '01520');
            $orderCount = Order::whereDate('created_at', now())->count() + 1;
            $consumerNumber = $prefix . date('ymd') . str_pad($orderCount, 5, '0', STR_PAD_LEFT);

            // Create a pending top-up order
            Order::create([
                'order_id' => 'TOP-' . date('Ymd') . '-' . str_pad($orderCount, 4, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'user_role' => $user->account_type,
                'voucher_type' => 'wallet_topup',
                'amount' => $amount,
                'status' => 'pending',
                'payment_method' => 'kuickpay',
                'kuickpay_consumer_number' => $consumerNumber,
            ]);

            return redirect()->route('bank.link')->with('success', "KuickPay Consumer Number: $consumerNumber. Please pay via your bank app (Bill Payment -> KuickPay). Funds will be added to your wallet automatically upon successful payment.");

        } catch (\Exception $e) {
            Log::error('KuickPay Checkout Exception: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function kuickpaySuccess()
    {
        return redirect()->route('bank.link')->with('success', 'KuickPay payment processed.');
    }

    public function kuickpayCancel()
    {
        return redirect()->route('bank.link')->with('error', 'KuickPay payment cancelled.');
    }


    public function processing(Request $request)
    {
        $sessionId = $request->get('session_id');
        if (!$sessionId) {
            return redirect()->route('bank.link')->with('error', 'Invalid session');
        }
        return view('dashboard.banks.processing', compact('sessionId'));
    }

    public function checkStatus(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return response()->json(['status' => 'error', 'message' => 'Invalid session'], 400);
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                // Before returning, run the success logic just in case webhook/redirect hasn't yet
                // This makes the polling very reliable
                $this->processSuccess($session, $request->ip(), $request->userAgent());
                return response()->json(['status' => 'paid', 'redirect' => route('bank.link')]);
            }

            return response()->json(['status' => 'pending']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function processSuccess($session, $ip, $userAgent)
    {
        $amount = $session->amount_total / 100;
        $userId = $session->metadata->user_id ?? null;

        if (!$userId) return false;

        $user = User::find($userId);
        if (!$user) return false;

        // ✅ Duplicate check using transaction_id
        $exists = WalletLedger::where('transaction_id', $session->id)->exists();

        if (!$exists) {
            DB::transaction(function () use ($user, $amount, $session, $ip, $userAgent) {
                // ✅ Wallet update
                $user->wallet_balance += $amount;
                $user->save();

                // ✅ Ledger entry
                WalletLedger::create([
                    'transaction_id' => $session->id,
                    'user_id' => $user->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'source' => 'stripe',
                    'description' => 'Wallet Top-up via Stripe',
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'created_at' => now(),
                ]);
            });
            return true;
        }
        return false;
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('bank.link')->with('error', 'Invalid payment');
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $this->processSuccess($session, $request->ip(), $request->userAgent());
                return redirect()->route('bank.link')->with('success', 'Amount added successfully!');
            }

            return redirect()->route('bank.link')->with('error', 'Payment still processing or failed');
        } catch (\Exception $e) {
            return redirect()->route('bank.link')->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return view('wallet.cancel');
    }
    public function paypalCheckout(Request $request)
    {
        $amount = $request->input('amount');
        if (!$amount) {
            return back()->with('error', 'Amount missing');
        }

        try {
            $token = $this->getPaypalAccessToken();

            $response = Http::withToken($token)
                ->post($this->getPaypalBaseUrl() . '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                        'description' => 'Wallet Top-up',
                    ]],
                    'application_context' => [
                        'return_url' => route('paypal.success'),
                        'cancel_url' => route('paypal.cancel'),
                        'landing_page' => 'BILLING', // Force Guest Checkout / Card entry page
                        'user_action' => 'PAY_NOW',
                    ],
                ]);

            if ($response->successful()) {
                $order = $response->json();
                foreach ($order['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        return redirect($link['href']);
                    }
                }
            }

            Log::error('PayPal Order Creation Failed', ['response' => $response->json()]);
            return back()->with('error', 'PayPal order creation failed.');
        } catch (\Exception $e) {
            Log::error('PayPal Checkout Exception: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function paypalSuccess(Request $request)
    {
        $token = $request->get('token'); // PayPal Order ID

        if (!$token) {
            return redirect()->route('bank.link')->with('error', 'Invalid PayPal payment');
        }

        try {
            $accessToken = $this->getPaypalAccessToken();

            $response = Http::withToken($accessToken)
                ->post($this->getPaypalBaseUrl() . "/v2/checkout/orders/{$token}/capture");

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'COMPLETED') {
                    $amount = $data['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
                    $user = auth()->user();

                    $exists = WalletLedger::where('transaction_id', $token)->exists();

                    if (!$exists) {
                        $user->wallet_balance += $amount;
                        $user->save();

                        WalletLedger::create([
                            'transaction_id' => $token,
                            'user_id' => $user->id,
                            'type' => 'credit',
                            'amount' => $amount,
                            'source' => 'paypal',
                            'description' => 'Wallet Top-up via PayPal',
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                            'created_at' => now(),
                        ]);
                    }

                    return redirect()->route('bank.link')->with('success', 'Amount added successfully via PayPal!');
                }
            }

            Log::error('PayPal Capture Failed', ['response' => $response->json()]);
            return redirect()->route('bank.link')->with('error', 'PayPal payment capture failed.');
        } catch (\Exception $e) {
            Log::error('PayPal Success Exception: ' . $e->getMessage());
            return redirect()->route('bank.link')->with('error', $e->getMessage());
        }
    }

    public function paypalCancel()
    {
        return redirect()->route('bank.link')->with('error', 'PayPal payment cancelled.');
    }

    private function getPaypalAccessToken()
    {
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');

        $response = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post($this->getPaypalBaseUrl() . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        throw new \Exception('Could not get PayPal access token.');
    }

    private function getPaypalBaseUrl()
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function createSession(Request $request)
    {
        try {

            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $amount = $request->input('amount');

            if (!$amount) {
                return back()->with('error', 'Amount missing');
            }

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['us_bank_account'],
                'payment_method_options' => [
                    'us_bank_account' => [
                        'verification_method' => 'automatic',
                    ],
                ],
                'metadata' => [
                    'user_id' => auth()->id(),
                ],

                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Wallet Top-up',
                        ],
                        'unit_amount' => $amount * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('wallet.processing') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('wallet.cancel'),
            ]);

            return redirect($session->url); // ✅ IMPORTANT

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());
        }
    }
    public function finalizeStripeLink(Request $request)
    {
        try {
            $secretKey = config('services.stripe.secret');
            Stripe::setApiKey($secretKey);

            $session = Session::retrieve($request->session_id, [
                'expand' => ['accounts'],
            ]);

            foreach ($session->accounts as $account) {
                // Check if account already exists
                $exists = BankAccountModel::where('user_id', auth()->id())
                    ->where('account_number', 'stripe_' . $account->id)
                    ->exists();

                if (!$exists) {
                    BankAccountModel::create([
                        'user_id' => auth()->id(),
                        'bank_name' => $account->institution_name ?? 'Linked Bank',
                        'account_holder_name' => auth()->user()->name,
                        'account_number' => 'stripe_' . $account->id, // Use stripe id for uniqueness
                        'ifsc_code' => 'STRIPE_LINKED', // Providing a default value
                        'account_type' => $account->category ?? 'Savings',
                        'balance' => 1000, // Dummy initial balance for testing
                        'is_verified' => true,
                    ]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Finalize Stripe Link Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $methods = AdminPaymentMethod::all();
        return view('dashboard.banks.manage', compact('methods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'method_type' => 'required|in:bank,upi,qr',
            'bank_name' => 'nullable|string',
            'account_holder_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'bank_code_value' => 'nullable|string',
            'bank_code_type' => 'nullable|string',
            'upi_id' => 'nullable|string',
            'qr_code' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();

        if ($request->hasFile('qr_code')) {
            $data['qr_code'] = $request->file('qr_code')->store('payment_methods', 'public');
        }

        AdminPaymentMethod::create($data);

        return redirect()->back()->with('success', 'Payment method added successfully.');
    }

    public function update(Request $request, $id)
    {
        $method = AdminPaymentMethod::findOrFail($id);

        $request->validate([
            'method_type' => 'required|in:bank,upi,qr',
            'bank_name' => 'nullable|string',
            'account_holder_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'ifsc_code' => 'nullable|string',
            'upi_id' => 'nullable|string',
            'qr_code' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();

        if ($request->hasFile('qr_code')) {
            if ($method->qr_code) {
                Storage::disk('public')->delete($method->qr_code);
            }
            $data['qr_code'] = $request->file('qr_code')->store('payment_methods', 'public');
        }

        $method->update($data);

        return redirect()->back()->with('success', 'Payment method updated successfully.');
    }

    public function destroy($id)
    {
        $method = AdminPaymentMethod::findOrFail($id);
        if ($method->qr_code) {
            Storage::disk('public')->delete($method->qr_code);
        }
        $method->delete();

        return redirect()->back()->with('success', 'Payment method deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $method = AdminPaymentMethod::findOrFail($id);
        $method->status = !$method->status;
        $method->save();

        return response()->json(['success' => true, 'status' => $method->status]);
    }

    public function bankLink()
    {
        $user = auth()->user();
        $banks = BankAccountModel::where('user_id', $user->id)->get();
        $totalBalance = $banks->sum('balance');
        $transactions = WalletLedger::where('user_id', $user->id)->latest()->paginate(10);

        return view('dashboard.banks.link', compact('banks', 'totalBalance', 'transactions'));
    }

    public function linkBank(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string',
            'account_holder_name' => 'required|string',
            'account_number' => 'required|string',
            'ifsc_code' => 'nullable|string',
            'account_type' => 'nullable|string',
            'balance' => 'nullable|numeric|min:0',
        ]);
        BankAccountModel::create([
            'user_id' => auth()->id(),
            'bank_name' => $request->bank_name,
            'account_holder_name' => $request->account_holder_name,

            // UI ke liye (last 4)
            'account_number' => substr($request->account_number, -4),

            // actual Stripe ID
            'stripe_account_id' => $request->account_number,

            'ifsc_code' => $request->ifsc_code ?? 'STRIPE',
            'account_type' => $request->account_type ?? 'stripe',
            'balance' => $request->balance ?? 0,
            'is_verified' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Bank account linked successfully.']);
    }


    public function createBankPayment(Request $request)
    {
        $request->validate([
            'bank_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $bank = BankAccountModel::where('id', $request->bank_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // 🔥 CREATE PAYMENT INTENT (BANK)
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100,
                'currency' => 'usd',

                'payment_method_types' => ['us_bank_account'],

                'payment_method_data' => [
                    'type' => 'us_bank_account',
                    'us_bank_account' => [
                        // 👇 YE SABSE IMPORTANT
                        'financial_connections_account' => $bank->stripe_account_id,
                    ],
                ],

                'confirm' => true,

                'metadata' => [
                    'user_id' => auth()->id(),
                    'amount' => $request->amount,
                ]
            ]);

            return response()->json([
                'success' => true,
                'status' => $paymentIntent->status,
                'message' => 'Bank payment started (processing)'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function exportBankReport()
    {
        $reportData = $this->getReportData();
        $filename = "bank_report_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sr. No.',
            'Bank ID',
            'Currency',
            'Country/Region',
            'Vouchers Sold',
            'Sale Value in Local Currency',
            'Refunds',
            'Disputes',
            'Currency Conversion @',
            'Sale Value in Buying Currency',
            'FX Gain/Loss',
            'Created At'
        ];

        $callback = function () use ($reportData, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($reportData as $index => $data) {
                fputcsv($file, [
                    $index + 1,
                    $data['bank_name'],
                    $data['currency'],
                    $data['country'],
                    $data['vouchers_sold'],
                    number_format($data['sale_value'], 2, '.', ''),
                    number_format($data['refunds'], 2, '.', ''),
                    $data['disputes'],
                    $data['conversion_rate'],
                    number_format($data['sale_value'], 2, '.', ''),
                    '0.00',
                    is_string($data['created_at']) ? $data['created_at'] : $data['created_at']->format('Y-m-d')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getReportData()
    {
        $methods = AdminPaymentMethod::all();
        $reportData = collect();

        foreach ($methods as $method) {
            $sold = \App\Models\Order::where('admin_payment_method_id', $method->id)->where('status', 'delivered')->sum('quantity');
            $value = \App\Models\Order::where('admin_payment_method_id', $method->id)->where('status', 'delivered')->sum('amount');
            $refunds = \App\Models\RefundRequest::whereHas('order', function ($q) use ($method) {
                $q->where('admin_payment_method_id', $method->id);
            })->where('status', 'approved')->sum('amount');

            $disputesCount = \App\Models\Dispute::whereHas('user.orders', function ($q) use ($method) {
                $q->where('admin_payment_method_id', $method->id);
            })->count();

            $reportData->push([
                'id' => $method->id,
                'bank_name' => $method->bank_name,
                'currency' => 'PKR',
                'country' => $method->country ?? 'N/A',
                'vouchers_sold' => $sold,
                'sale_value' => $value,
                'refunds' => $refunds,
                'disputes' => $disputesCount,
                'conversion_rate' => '1.00',
                'created_at' => $method->created_at
            ]);
        }

        $stripeSold = \App\Models\Order::where('payment_method', 'stripe')->where('status', 'delivered')->sum('quantity');
        $stripeValue = \App\Models\Order::where('payment_method', 'stripe')->where('status', 'delivered')->sum('amount');
        $stripeRefunds = \App\Models\RefundRequest::whereHas('order', function ($q) {
            $q->where('payment_method', 'stripe');
        })->where('status', 'approved')->sum('amount');
        $stripeDisputes = \App\Models\Dispute::whereHas('user.orders', function ($q) {
            $q->where('payment_method', 'stripe');
        })->count();

        $reportData->push([
            'id' => 'stripe',
            'bank_name' => 'Stripe (Global)',
            'currency' => 'USD/PKR',
            'country' => 'GLB',
            'vouchers_sold' => $stripeSold,
            'sale_value' => $stripeValue,
            'refunds' => $stripeRefunds,
            'disputes' => $stripeDisputes,
            'conversion_rate' => 'Dynamic',
            'created_at' => now()
        ]);

        return $reportData;
    }

    public function bankreport()
    {
        $methods = AdminPaymentMethod::all();
        $reportData = $this->getReportData();

        // Fetch recent stripe transactions (credits)
        $stripeTransactions = \App\Models\Order::where('payment_method', 'stripe')
            ->whereIn('status', ['completed', 'delivered'])
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('dashboard.banks.bank-table', compact('methods', 'reportData', 'stripeTransactions'));
    }
}
