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
use App\Services\CurrencyService;

class BankController extends Controller
{
    public function wiseCheckout(Request $request)
    {
        $amount = (float)$request->input('amount');
        if (!$amount || $amount <= 0) {
            return back()->with('error', 'Amount missing or invalid');
        }

        try {
            $user = auth()->user();
            $userCurrency = $user->currency ?? 'USD';
            $conversion = CurrencyService::convertToBase($amount, $userCurrency);
            $amountInUSD = (float)$conversion['base_amount'];

            $apiKey = config('services.wise.api_key');
            $profileId = config('services.wise.profile_id');
            $baseUrl = config('services.wise.base_url');

            // 1. Create a Quote
            $quoteResponse = Http::withToken($apiKey)
                ->post($baseUrl . '/v3/quotes', [
                    'sourceCurrency' => 'USD',
                    'targetCurrency' => 'USD',
                    'sourceAmount' => (float)$amountInUSD,
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
                    'redirectUrl' => route('wise.success', ['order_id' => $orderId, 'amount' => $amountInUSD]),
                ]);

            if ($checkoutResponse->successful()) {
                $checkoutUrl = $checkoutResponse->json('checkoutUrl');
                session()->put('wise_order_' . $orderId, [
                    'user_id' => auth()->id(),
                    'amount' => (float)$amountInUSD,
                    'local_amount' => $amount,
                    'local_currency' => $userCurrency,
                ]);
                return redirect($checkoutUrl);
            }


            Log::error('Wise Checkout Session Failed', ['response' => $checkoutResponse->json()]);
            return back()->with('error', 'Wise checkout session failed to initiate. Please verify sandbox credentials.');

        } catch (\Exception $e) {
            Log::error('Wise Checkout Exception: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function wiseSuccess(Request $request)
    {
        $orderId = $request->get('order_id');
        if (!$orderId) {
            return redirect()->route('bank.link')->with('error', 'Invalid Wise payment');
        }

        $sessionData = session()->get('wise_order_' . $orderId);
        $user = auth()->user();

        if (!$sessionData || $sessionData['user_id'] !== $user->id) {
            return redirect()->route('bank.link')->with('error', 'Invalid or expired Wise checkout session.');
        }

        $amount = (float)$sessionData['amount'];
        $exists = WalletLedger::where('transaction_id', $orderId)->exists();

        if (!$exists && $amount > 0) {
            $desc = isset($sessionData['local_amount'])
                ? "Wallet Top-up via Wise ({$sessionData['local_amount']} {$sessionData['local_currency']})"
                : "Wallet Top-up via Wise";

            WalletService::credit(
                $user,
                $amount,
                'wise',
                $desc
            );
            session()->forget('wise_order_' . $orderId);
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
        $userId = $session->metadata->user_id ?? null;

        if (!$userId) return false;

        $user = User::find($userId);
        if (!$user) return false;

        // Duplicate check using transaction_id
        $exists = WalletLedger::where('transaction_id', $session->id)->exists();

        if (!$exists) {
            $amountUsd = isset($session->metadata->amount_usd) ? (float)$session->metadata->amount_usd : (float)($session->amount_total / 100);
            $localAmount = $session->metadata->local_amount ?? null;
            $localCurr = $session->metadata->local_currency ?? ($user->currency ?? 'USD');

            $desc = $localAmount 
                ? "Wallet Top-up via Stripe ({$localAmount} {$localCurr})" 
                : "Wallet Top-up via Stripe";

            if ($amountUsd > 0) {
                WalletService::credit(
                    $user,
                    $amountUsd,
                    'stripe',
                    $desc
                );
                return true;
            }
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
        $amount = (float)$request->input('amount');
        if (!$amount || $amount <= 0) {
            return back()->with('error', 'Amount missing or invalid');
        }

        try {
            $user = auth()->user();
            $userCurrency = $user->currency ?? 'USD';
            $conversion = CurrencyService::convertToBase($amount, $userCurrency);
            $amountInUSD = (float)$conversion['base_amount'];

            $token = $this->getPaypalAccessToken();

            $response = Http::withToken($token)
                ->post($this->getPaypalBaseUrl() . '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($amountInUSD, 2, '.', ''),
                        ],
                        'description' => 'Wallet Top-up (' . number_format($amount, 2) . ' ' . $userCurrency . ')',
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

            $amount = (float)$request->input('amount');

            if (!$amount || $amount <= 0) {
                return back()->with('error', 'Amount missing or invalid');
            }

            $user = auth()->user();
            $userCurrency = $user->currency ?? 'USD';
            $conversion = CurrencyService::convertToBase($amount, $userCurrency);
            $amountInUSD = (float)$conversion['base_amount'];

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'metadata' => [
                    'user_id' => $user->id,
                    'amount_usd' => $amountInUSD,
                    'local_amount' => $amount,
                    'local_currency' => $userCurrency,
                ],

                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Wallet Top-up (' . number_format($amount, 2) . ' ' . $userCurrency . ')',
                        ],
                        'unit_amount' => (int) round($amountInUSD * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('wallet.processing') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('wallet.cancel'),
            ]);

            return redirect($session->url); // ✅ IMPORTANT

        } catch (\Throwable $e) {

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

        return redirect()->route('bank.link')->with('success', 'Bank account linked successfully.');
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
            $user = auth()->user();
            $userCurrency = $user->currency ?? 'USD';
            $conversion = CurrencyService::convertToBase((float)$request->amount, $userCurrency);
            $amountInUSD = (float)$conversion['base_amount'];

            Stripe::setApiKey(config('services.stripe.secret'));

            // 🔥 CREATE PAYMENT INTENT (BANK)
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($amountInUSD * 100),
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
                    'amount' => $amountInUSD,
                    'local_amount' => $request->amount,
                    'local_currency' => $userCurrency,
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
            "Cache-Control"       => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0",
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
            'Sale Value in Buying Currency (USD)',
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
                    number_format($data['buying_sale_value'], 2, '.', ''),
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
            $orders = \App\Models\Order::with('inventoryVoucher')
                ->where('admin_payment_method_id', $method->id)
                ->where('status', 'delivered')
                ->get();

            $sold = $orders->sum('quantity');
            $value = $orders->sum('amount');

            // Unique currencies of these orders
            $currencies = $orders->map(function ($o) {
                return $o->inventoryVoucher->local_currency ?? 'USD';
            })->unique()->filter()->implode('/');

            // Unique countries of these orders
            $countries = $orders->flatMap(function ($o) {
                return (array)($o->inventoryVoucher->country_region ?? []);
            })->unique()->filter()->implode(', ');

            // Calculate dynamic total value in buying currency (USD)
            $buying_value_sum = $orders->sum(function ($order) {
                if ($order->amount_in_base_currency !== null && (float)$order->amount_in_base_currency > 0) {
                    return (float)$order->amount_in_base_currency;
                }
                $cur = $order->currency ?: ($order->inventoryVoucher ? $order->inventoryVoucher->currency : 'USD');
                return CurrencyService::convertToBase((float)$order->amount, $cur)['base_amount'];
            });

            // Conversion rate label
            $rates = $orders->map(function ($o) {
                $cur = $o->currency ?: ($o->inventoryVoucher ? $o->inventoryVoucher->currency : 'USD');
                return number_format($o->exchange_rate_applied ?: CurrencyService::getRate($cur), 4);
            })->unique()->filter()->implode('/');

            $refunds = \App\Models\RefundRequest::whereHas('order', function ($q) use ($method) {
                $q->where('admin_payment_method_id', $method->id);
            })->where('status', 'approved')->get()->sum(function ($ref) {
                return CurrencyService::convertToBase((float)$ref->amount, $ref->currency ?: 'USD')['base_amount'];
            });

            $disputesCount = \App\Models\Dispute::whereHas('user.orders', function ($q) use ($method) {
                $q->where('admin_payment_method_id', $method->id);
            })->count();

            $reportData->push([
                'id' => $method->id,
                'bank_name' => $method->bank_name,
                'currency' => $currencies ?: 'USD',
                'country' => $countries ?: 'Global',
                'vouchers_sold' => $sold,
                'sale_value' => $value,
                'buying_sale_value' => $buying_value_sum,
                'refunds' => $refunds,
                'disputes' => $disputesCount,
                'conversion_rate' => $rates ?: '1.0000',
                'created_at' => $method->created_at
            ]);
        }

        // Add Stripe data
        $stripeOrders = \App\Models\Order::with('inventoryVoucher')
            ->where('payment_method', 'stripe')
            ->where('status', 'delivered')
            ->get();

        $stripeSold = $stripeOrders->sum('quantity');
        $stripeValue = $stripeOrders->sum('amount');
        
        $stripeCurrencies = $stripeOrders->map(function ($o) {
            return $o->currency ?: ($o->inventoryVoucher->currency ?? 'USD');
        })->unique()->filter()->implode('/');

        $stripeCountries = $stripeOrders->flatMap(function ($o) {
            return (array)($o->inventoryVoucher->country_region ?? []);
        })->unique()->filter()->implode(', ');

        $stripeBuyingSum = $stripeOrders->sum(function ($order) {
            if ($order->amount_in_base_currency !== null && (float)$order->amount_in_base_currency > 0) {
                return (float)$order->amount_in_base_currency;
            }
            $cur = $order->currency ?: ($order->inventoryVoucher ? $order->inventoryVoucher->currency : 'USD');
            return CurrencyService::convertToBase((float)$order->amount, $cur)['base_amount'];
        });

        $stripeRates = $stripeOrders->map(function ($o) {
            $cur = $o->currency ?: ($o->inventoryVoucher ? $o->inventoryVoucher->currency : 'USD');
            return number_format($o->exchange_rate_applied ?: CurrencyService::getRate($cur), 4);
        })->unique()->filter()->implode('/');

        $stripeRefunds = \App\Models\RefundRequest::whereHas('order', function ($q) {
            $q->where('payment_method', 'stripe');
        })->where('status', 'approved')->get()->sum(function ($ref) {
            return CurrencyService::convertToBase((float)$ref->amount, $ref->currency ?: 'USD')['base_amount'];
        });

        $stripeDisputes = \App\Models\Dispute::whereHas('user.orders', function ($q) {
            $q->where('payment_method', 'stripe');
        })->count();

        $reportData->push([
            'id' => 'stripe',
            'bank_name' => 'Stripe (Global)',
            'currency' => $stripeCurrencies ?: 'USD',
            'country' => $stripeCountries ?: 'GLB',
            'vouchers_sold' => $stripeSold,
            'sale_value' => $stripeValue,
            'buying_sale_value' => $stripeBuyingSum,
            'refunds' => $stripeRefunds,
            'disputes' => $stripeDisputes,
            'conversion_rate' => $stripeRates ?: 'Dynamic',
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
