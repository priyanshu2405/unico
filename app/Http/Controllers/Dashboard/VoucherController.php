<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\VoucherPriceRule;
use App\Models\InventoryVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Notifications\VoucherDeliveredNotification;
use Illuminate\Support\Facades\Mail;
use App\Models\Order;
use App\Services\WalletService;
use App\Helpers\CurrencyHelper;
use App\Models\BankAccountModel;
use App\Models\AdminPaymentMethod;
use App\Models\User;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;

class VoucherController extends Controller
{
    
  
    public function getConversion(Request $request)
    {
        $amount = $request->get('amount', 0);
        $from = $request->get('from', 'PKR');
        $to = $request->get('to', 'USD');

        try {
            $converted = CurrencyHelper::convert($amount, $from, $to);
            return response()->json([
                'success' => true,
                'converted' => $converted,
                'rate' => $converted / ($amount ?: 1)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = InventoryVoucher::where('is_expired', false);

        // Country and State filtering: match user's country and state with voucher's settings
        if (!in_array($user->account_type, ['admin', 'manager'])) {
            $query->where(function ($q) use ($user) {
                $q->whereJsonContains('country_region', $user->country)
                    ->orWhereJsonContains('country_region', 'all')
                    ->orWhereJsonContains('country_region', 'GLB')
                    ->orWhere('country_region', 'like', '%all%')
                    ->orWhere('country_region', 'like', '%GLB%');
            });

            // If voucher has state restrictions, apply them. 
            // If state is empty or null, it's available to all states in that country.
            $query->where(function ($q) use ($user) {
                $q->whereNull('state')
                    ->orWhere('state', '[]')
                    ->orWhere('state', 'null')
                    ->orWhereJsonContains('state', $user->state)
                    ->orWhereJsonContains('state', 'all');
            });
        }

        // Filter by SKU ID
        if ($request->filled('sku_id')) {
            $query->where('sku_id', 'like', "%{$request->sku_id}%");
        }

        // Filter by Voucher Name (Brand Name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('brand_name', 'like', "%$search%");
        }

        // Add New Filters
        if ($request->filled('brand_name')) {
            $query->where('brand_name', $request->brand_name);
        }
        if ($request->filled('voucher_variant')) {
            $query->where('voucher_variant', $request->voucher_variant);
        }
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        // Filter by Date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Filter by Price Range
        if ($request->filled('min_price')) {
            if ($user->isStudent()) {
                $query->where('student_sale_price', '>=', $request->min_price);
            } else {
                $query->where('agent_sale_price', '>=', $request->min_price);
            }
        }
        if ($request->filled('max_price')) {
            if ($user->isStudent()) {
                $query->where('student_sale_price', '<=', $request->max_price);
            } else {
                $query->where('agent_sale_price', '<=', $request->max_price);
            }
        }

        $perPage = $request->get('per_page', 10);
        $vouchers = $query->latest()->paginate($perPage)->withQueryString();

        // Calculate limits and pricing for each voucher
        $userTotalLimit = $user->voucher_limit;
        $boughtTotalLast24h = Order::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->sum('quantity');

        foreach ($vouchers as $voucher) {
            // Set final price based on user role
            if ($user->isStudent()) {
                $voucher->final_price = $voucher->student_sale_price;
            } else {
                $voucher->final_price = $voucher->agent_sale_price;
            }

            // Check 24h total limit across all vouchers
            $voucher->is_limited = ($boughtTotalLast24h >= $userTotalLimit);
            $voucher->remaining_limit = max(0, $userTotalLimit - $boughtTotalLast24h);
            $voucher->quantity_bought_today = $boughtTotalLast24h;
        }

        if ($request->ajax()) {
            return view('dashboard.voucher.voucher-grid', compact('vouchers'))->render();
        }

        $stats = [
            'total_vouchers' => InventoryVoucher::count(),
            'total_stock' => InventoryVoucher::sum('quantity'),
            'total_valuation' => InventoryVoucher::sum(DB::raw('agent_sale_price * quantity')),
            'active_brands' => InventoryVoucher::distinct('brand_name')->count('brand_name'),
        ];

        $filterOptions = [
            'brands' => InventoryVoucher::where('is_expired', false)->distinct('brand_name')->orderBy('brand_name')->pluck('brand_name'),
            'variants' => InventoryVoucher::where('is_expired', false)->distinct('voucher_variant')->orderBy('voucher_variant')->pluck('voucher_variant'),
            'types' => InventoryVoucher::where('is_expired', false)->distinct('voucher_type')->orderBy('voucher_type')->pluck('voucher_type'),
        ];

        return view('dashboard.voucher.index', compact('vouchers', 'stats', 'filterOptions'));
    }

    public function showOrder($id)
    {
        $voucher = InventoryVoucher::findOrFail($id);
        $user = auth()->user();

        // Check if voucher is available in user's country
        if (!in_array($user->account_type, ['admin', 'manager'])) {
            $countries = is_array($voucher->country_region) ? $voucher->country_region : [$voucher->country_region];
            if (!in_array($user->country, $countries) && !in_array('all', $countries) && !in_array('GLB', $countries)) {
                return redirect()->route('vouchers')->with('error', 'This voucher is not available in your country.');
            }
        }

        // Calculate correct price based on user role
        if ($user->isStudent()) {
            $voucher->final_price = $voucher->student_sale_price;
        } else {
            $voucher->final_price = $voucher->agent_sale_price;
        }

        // Check user total 24h limit
        $userTotalLimit = $user->voucher_limit;
        $boughtTotalLast24h = Order::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->sum('quantity');

        if ($boughtTotalLast24h >= $userTotalLimit) {
            return redirect()->route('vouchers')->with('error', 'You have reached your voucher purchase limit in the last 24 hours.');
        }

        $maxAllowed = $userTotalLimit - $boughtTotalLast24h;

        // Check quantity availability
        if ($voucher->quantity <= 0 || $voucher->status === 'OUT OF STOCK') {
            return redirect()->route('vouchers')->with('error', 'This voucher is out of stock.');
        }

        $banks = BankAccountModel::where('user_id', $user->id)->get();
        $adminBanks = AdminPaymentMethod::where('status', true)->get();

        // Mock data for points and store credit
        $userPoints = [
            'quarterly' => 0,
            'yearly' => 0,
            'store_credit' => $user->wallet_balance ?? 0,
            'max_allowed' => $maxAllowed
        ];

        $rule = $voucher;

        return view('dashboard.voucher.order-now', compact('voucher', 'rule', 'userPoints', 'banks', 'adminBanks'));
    }

    public function placeOrder(Request $request, $id)
    {
        $voucher = InventoryVoucher::findOrFail($id);
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'payment_type' => 'required|in:card,admin_bank,wise,kuickpay,stripe,wallet',
            'admin_bank_id' => 'required_if:payment_type,admin_bank,wise|exists:admin_payment_methods,id',
            'payment_receipt' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stripeToken' => 'required_if:payment_type,stripe',
        ]);

        $user = auth()->user();

        // Calculate correct price based on user role
        if ($user->isStudent()) {
            $totalAmount = $voucher->student_sale_price * $request->quantity;
        } else {
            // Agent and Reseller see same price
            $totalAmount = $voucher->agent_sale_price * $request->quantity;
        }

        // Check user total 24h limit
        $userTotalLimit = $user->voucher_limit;
        $boughtTotalLast24h = Order::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->sum('quantity');

        if ($boughtTotalLast24h + $request->quantity > $userTotalLimit) {
            return response()->json(['message' => 'Your last 24 hours limit has been reached. Remaining: ' . max(0, $userTotalLimit - $boughtTotalLast24h)], 400);
        }

        if ($voucher->quantity < $request->quantity || $voucher->status === 'OUT OF STOCK') {
            return response()->json(['message' => 'Insufficient stock available.'], 400);
        }

        $status = 'pending';
        $payment_receipt = null;
        $bank_details = [];
        
        $voucherCurrency = $voucher->currency ?: 'PKR';
        $amountInUSD = CurrencyHelper::convert($totalAmount, $voucherCurrency, 'USD');

        if ($request->payment_type == 'wallet') {
            if ($user->wallet_balance < $amountInUSD) {
                return response()->json(['message' => 'Insufficient wallet balance.'], 400);
            }
            $status = 'completed'; // Wallet payment is instant
            $bank_details = ['notes' => 'Paid via Wallet (USD: ' . number_format($amountInUSD, 2) . ')'];
        } 
        elseif ($request->payment_type == 'admin_bank' || $request->payment_type == 'wise') {
            $adminBank = AdminPaymentMethod::findOrFail($request->admin_bank_id);
            if ($request->hasFile('payment_receipt')) {
                $payment_receipt = $request->file('payment_receipt')->store('receipts', 'public');
            }
            $bank_details = [
                'admin_payment_method_id' => $adminBank->id,
                'bank_name' => $adminBank->bank_name,
                'account_number' => $adminBank->account_number,
                'ifsc_code' => $adminBank->ifsc_code,
                'transaction_id' => $request->transaction_id,
                'account_holder_name' => $request->account_holder_name,
                'amount_transferred' => $request->transfer_amount,
                'captured_details' => $request->captured_details, // Storing JSON from OCR
            ];
            $status = 'pending';
        } elseif ($request->payment_type == 'card') {
            $status = 'pending';
            $bank_details = [
                'notes' => 'Card payment initiated',
                'captured_details' => $request->captured_details
            ];
        } elseif ($request->payment_type == 'kuickpay') {
            $status = 'pending';
            // Generating a unique consumer number: Prefix (01520) + Timestamp + OrderCount
            $prefix = config('services.kuickpay.prefix', '01520');
            $orderCount = Order::whereDate('created_at', now())->count() + 1;
            $consumerNumber = $prefix . date('ymd') . str_pad($orderCount, 5, '0', STR_PAD_LEFT);
            $bank_details = [
                'kuickpay_consumer_number' => $consumerNumber,
                'notes' => 'Kuickpay BPS payment initiated'
            ];
        } elseif ($request->payment_type == 'stripe') {
            // Process Stripe Payment using API directly
            $stripeSecret = config('services.stripe.secret');

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $stripeSecret,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->asForm()->post('https://api.stripe.com/v1/charges', [
                    'amount' => (int)($totalAmount * 100), // in cents
                    'currency' => strtolower($voucher->currency ?: 'PKR'),
                    'source' => $request->stripeToken,
                    'description' => 'Voucher Purchase: ' . $voucher->brand_name,
                    'metadata' => [
                        'user_id' => $user->id,
                        'voucher_id' => $voucher->sku_id,
                        'quantity' => $request->quantity,
                    ],
                ]);

                if ($response->failed()) {
                    $error = $response->json()['error']['message'] ?? 'Stripe payment failed';
                    return response()->json(['message' => $error], 400);
                }

                $charge = $response->json();
                $status = 'completed'; // Payment successful
                $bank_details = [
                    'transaction_id' => $charge['id'],
                    'notes' => 'Stripe payment successful'
                ];
            } catch (\Exception $e) {
                return response()->json(['message' => 'Stripe integration error: ' . $e->getMessage()], 500);
            }
        }

        if ($request->payment_type == 'stripe' && $totalAmount < 150) {
            return response()->json(['message' => 'For Stripe payment, the minimum amount is 150 PKR.'], 400);
        }

        $referralPointsPerUnit = 0;
        $bonusPointsPerUnit = 0;
        if ($user->isStudent()) {
            $referralPointsPerUnit = $voucher->student_referral_points_per_unit;
            $bonusPointsPerUnit = $voucher->student_bonus_points_per_unit;
        } elseif ($user->isResellerAgent()) {
            $referralPointsPerUnit = $voucher->referral_points_reseller;
            $bonusPointsPerUnit = 0;
        } elseif ($user->isRegularAgent()) {
            $referralPointsPerUnit = $voucher->agent_referral_points_per_unit;
            $bonusPointsPerUnit = $voucher->agent_bonus_points_per_unit;
        }

        $totalReferralPoints = $referralPointsPerUnit * $request->quantity;
        $totalBonusPoints = $bonusPointsPerUnit * $request->quantity;

        DB::beginTransaction();
        try {
            // Process Wallet Payment Logic
            if ($request->payment_type == 'wallet') {
                // Debit User in USD
                WalletService::debit(
                    $user,
                    $amountInUSD,
                    'order_purchase',
                    "Payment for Voucher Order: {$voucher->brand_name} (Qty: {$request->quantity}) - Amount in USD: " . number_format($amountInUSD, 2)
                );

                // Credit Admin (First admin found) in USD
                $admin = User::where('account_type', 'admin')->first();
                if ($admin) {
                    WalletService::credit(
                        $admin,
                        $amountInUSD,
                        'order_revenue',
                        "Revenue from User {$user->name} for Order of {$voucher->brand_name} - Amount in USD: " . number_format($amountInUSD, 2),
                        true // Admin can withdraw revenue
                    );
                }
            }

            // Create Order
            $orderData = [
                'order_id' => 'PUR-' . date('Ymd') . '-' . str_pad(Order::whereDate('created_at', now())->count() + 1, 4, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'sub_agent_id' => $user->sub_agent_id, // Store parent ID for referral points
                'user_role' => $user->account_type,
                'voucher_type' => $voucher->voucher_type,
                'voucher_id' => $voucher->sku_id,
                'quantity' => $request->quantity,
                'amount' => $totalAmount,
                'status' => $status,
                'referral_points' => $totalReferralPoints,
                'bonus_amount' => $totalBonusPoints,
                'payment_method' => $request->payment_type,
                'payment_receipt' => $payment_receipt,
            ];
            $orderData = array_merge($orderData, $bank_details);

            $order = Order::create($orderData);

            // Decrement quantity immediately on purchase
            $voucher->decrement('quantity', $request->quantity);
// ✅ WALLET AUTO DELIVERY
if ($request->payment_type == 'wallet') {

    $inventory = $order->inventoryVoucher;

    if ($inventory && $inventory->upload_vouchers) {

        $available = array_diff(
            $inventory->upload_vouchers,
            $inventory->delivered_vouchers ?? []
        );

        if (count($available) >= $order->quantity) {

            $codes = array_slice($available, 0, $order->quantity);

            $codesString = implode("\n", $codes);

            // 👉 SAME FUNCTION CALL
            $this->processDelivery($order, $codesString);
        }
    }
}
      $userMsg = "Your order has been successfully placed. Voucher: " . $order->voucher_type . ", Amount: " . $order->amount . ", Order ID: " . $order->order_id;
            $user->notify(new OrderPlacedNotification($order, $userMsg, 'order_placed'));

            DB::commit();

            session()->flash('success', $status == 'completed' ? 'Order placed successfully.' : 'Order submitted. Waiting for admin approval.');

            return response()->json([
                'success' => true,
                'message' => $status == 'completed' ? 'Order placed successfully.' : 'Order submitted. Waiting for admin approval.',
                'order_id' => $order->order_id,
                'consumer_number' => $order->kuickpay_consumer_number
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to place order: ' . $e->getMessage()], 500);
        }
    }
public function processDelivery($order, $codes)
{
    // Update Order
    $order->update([
        'status' => 'delivered',
        'delivery_details' => $codes
    ]);

    // Update Inventory
    $voucher = InventoryVoucher::where('sku_id', $order->voucher_id)->first();

    if ($voucher) {
        $newDelivered = array_map('trim', explode("\n", str_replace("\r", "", $codes)));
        $existingDelivered = $voucher->delivered_vouchers ?: [];
        $allDelivered = array_unique(array_merge($existingDelivered, $newDelivered));

        $voucher->delivered_vouchers = $allDelivered;
        $voucher->save();
    }

    // Notify User
    try {
        $user = $order->user;

        $user->notify(new VoucherDeliveredNotification(
            $order,
            "You have received voucher codes for Order #{$order->order_id}. Total amount: RS {$order->amount}."
        ));

        Mail::send('emails.order-delivered', [
            'order' => $order,
            'user' => $user,
            'codes' => $codes
        ], function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Your Voucher Codes Have Been Delivered! - UniCou');
        });

    } catch (\Exception $e) {}

    // Notify Staff
    try {
        $staff = User::whereIn('account_type', ['admin', 'manager', 'support_team'])->get();

        Notification::send($staff, new VoucherDeliveredNotification(
            $order,
            "Voucher codes delivered for Order #{$order->order_id}"
        ));

    } catch (\Exception $e) {}
}
    public function create()
    {
        return view('dashboard.voucher.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'yearly_points' => 'nullable|integer|min:0',
            'logo' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        Vouchar::create([
            'voucher_id' => 'VCH-' . strtoupper(Str::random(6)),
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'original_price' => $request->original_price,
            'stock' => $request->stock,
            'yearly_points' => $request->yearly_points ?? 0,
            'logo' => $request->logo,
            'description' => $request->description,
            'status' => 'active',
        ]);

        return back()->with('success', 'Voucher added successfully.');
    }

    public function edit($id)
    {
        $voucher = Vouchar::findOrFail($id);
        return view('dashboard.voucher.edit', compact('voucher'));
    }

    public function update(Request $request, $id)
    {
        Vouchar::where('id', $id)->update([
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'original_price' => $request->original_price,
            'stock' => $request->stock,
            'yearly_points' => $request->yearly_points ?? 0,
            'status' => $request->status,
        ]);

        return back()->with('success', 'Voucher updated successfully.');
    }

    public function destroy($id)
    {
        Vouchar::where('id', $id)->delete();
        return back()->with('success', 'Voucher deleted successfully.');
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $query = InventoryVoucher::where('is_expired', false);

        // Country and State filtering: match user's country and state with voucher's settings
        if (!in_array($user->account_type, ['admin', 'manager'])) {
            $query->where(function ($q) use ($user) {
                $q->whereJsonContains('country_region', $user->country)
                    ->orWhereJsonContains('country_region', 'all');
            });

            // If voucher has state restrictions, apply them. 
            // If state is empty or null, it's available to all states in that country.
            $query->where(function ($q) use ($user) {
                $q->whereNull('state')
                    ->orWhere('state', '[]')
                    ->orWhere('state', 'null')
                    ->orWhereJsonContains('state', $user->state)
                    ->orWhereJsonContains('state', 'all');
            });
        }

        // Apply same filters as index
        if ($request->filled('brand_name')) {
            $query->where('brand_name', $request->brand_name);
        }
        if ($request->filled('voucher_variant')) {
            $query->where('voucher_variant', $request->voucher_variant);
        }
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        $vouchers = $query->latest()->get();
        $filename = "vouchers_" . date('Ymd') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['Sr. No.', 'SKU ID', 'Brand Name', 'Voucher Variant', 'Quantity', 'Purchase Value', 'Stock Status']);

        foreach ($vouchers as $index => $v) {
            $finalPrice = $user->isStudent() ? $v->student_sale_price : $v->agent_sale_price;
            $stockStatus = $v->quantity > 0 ? 'In Stock' : 'Out of Stock';
            fputcsv($handle, [
                $index + 1,
                $v->sku_id,
                $v->brand_name,
                $v->voucher_variant,
                $v->quantity,
                $v->currency . ' ' . number_format($finalPrice, 2),
                $stockStatus
            ]);
        }

        fclose($handle);
        exit;
    }

    public function import(Request $request)
    {
        $request->validate(['csv_file' => 'required|mimes:csv,txt']);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        fgetcsv($handle); // Skip header

        while (($data = fgetcsv($handle)) !== FALSE) {
            Vouchar::updateOrInsert(
                ['voucher_id' => $data[0]],
                [
                    'name' => $data[1],
                    'category' => $data[2],
                    'price' => $data[3],
                    'stock' => $data[4],
                    'status' => $data[5] ?? 'active',
                    'updated_at' => now(),
                ]
            );
        }

        fclose($handle);
        return back()->with('success', 'Vouchers imported successfully.');
    }
}
