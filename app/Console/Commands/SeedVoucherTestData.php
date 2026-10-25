<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\InventoryVoucher;
use App\Models\Order;
use App\Models\RewardPointsLedger;
use App\Services\CurrencyService;
use App\Services\WalletService;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SeedVoucherTestData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'voucher:seed-test-data {--clean : Clean previously seeded test data first} {--user-id= : Optional existing user ID to also credit and award points to}';

    /**
     * The console command description.
     */
    protected $description = 'Seed 10 global vouchers stock, credit 1 Lakh INR to agents and students, simulate 6 voucher purchases via wallet, and award random bonus and referral points.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=======================================================================");
        $this->info("  STARTING VOUCHER STOCK, WALLET CREDIT, PURCHASES & POINTS SEEDING   ");
        $this->info("=======================================================================");

        if ($this->option('clean')) {
            $this->warn("Cleaning previously seeded test data...");
            $testSkus = [
                'GLOB-PTE-2026', 'GLOB-IELTS-2026', 'GLOB-TOEFL-2026', 'GLOB-GRE-2026', 'GLOB-GMAT-2026',
                'GLOB-DUOLINGO-2026', 'GLOB-SAT-2026', 'GLOB-OET-2026', 'GLOB-ACT-2026', 'GLOB-PTE-CORE-2026'
            ];
            $testOrders = Order::whereIn('voucher_id', $testSkus)->get();
            foreach ($testOrders as $to) {
                RewardPointsLedger::where('order_id', $to->id)->delete();
                $to->delete();
            }
            InventoryVoucher::whereIn('sku_id', $testSkus)->delete();
            RewardPointsLedger::where('description', 'like', '%Test Seed%')->delete();
            $this->info("Previous test data cleared.");
        }

        // -------------------------------------------------------------
        // STEP 1: CALCULATE 1 LAKH INR (₹100,000) IN BASE CURRENCY (USD)
        // -------------------------------------------------------------
        $inrAmount = 100000;
        $conversion = CurrencyService::convertToBase($inrAmount, 'INR');
        $baseUsdAmount = (float) ($conversion['base_amount'] ?? round($inrAmount * 0.012, 2));
        $exchangeRate = (float) ($conversion['rate'] ?? 0.012);

        $this->info(sprintf(
            "1. Currency Conversion: ₹%s INR = $%s USD (Exchange Rate: %s)",
            number_format($inrAmount),
            number_format($baseUsdAmount, 2),
            $exchangeRate
        ));

        // -------------------------------------------------------------
        // STEP 2: FIND OR CREATE ADMIN, AGENTS, AND STUDENTS
        // -------------------------------------------------------------
        $admin = User::where('account_type', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'user_id' => User::generateNextUserId('admin', 'US'),
                'name' => 'System Administrator',
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => 'admin@unicou.test',
                'password' => Hash::make('password123'),
                'account_type' => 'admin',
                'country_iso' => 'US',
                'country' => 'United States',
                'email_verified_at' => now(),
                'wallet_balance' => 5000.00,
                'withdrawable_balance' => 5000.00,
            ]);
            $this->info("Created default admin: admin@unicou.test");
        }

        // Test Agent 1 (Regular Agent)
        $agent1 = User::firstOrCreate(
            ['email' => 'agent1@unicou.test'],
            [
                'user_id' => User::generateNextUserId('agent', 'IN'),
                'name' => 'Apex Global Education Agent',
                'first_name' => 'Apex Global',
                'last_name' => 'Agent',
                'phone' => '+919876543210',
                'account_type' => 'agent',
                'country_iso' => 'IN',
                'country' => 'India',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'wallet_balance' => 0,
                'withdrawable_balance' => 0,
            ]
        );
        ReferralService::ensureUserReferralCode($agent1);

        // Test Agent 2 (Reseller Agent - referred by Agent 1)
        $agent2 = User::firstOrCreate(
            ['email' => 'agent2@unicou.test'],
            [
                'user_id' => User::generateNextUserId('reseller_agent', 'PK'),
                'name' => 'Global Study Partners Reseller',
                'first_name' => 'Global Study',
                'last_name' => 'Partners',
                'phone' => '+923001234567',
                'account_type' => 'reseller_agent',
                'country_iso' => 'PK',
                'country' => 'Pakistan',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'wallet_balance' => 0,
                'withdrawable_balance' => 0,
                'referred_by' => $agent1->id,
            ]
        );
        ReferralService::ensureUserReferralCode($agent2);

        // Test Student 1 (referred by Agent 1)
        $student1 = User::firstOrCreate(
            ['email' => 'student1@unicou.test'],
            [
                'user_id' => User::generateNextUserId('student', 'IN'),
                'name' => 'Rahul Sharma',
                'first_name' => 'Rahul',
                'last_name' => 'Sharma',
                'phone' => '+919876543211',
                'account_type' => 'student',
                'country_iso' => 'IN',
                'country' => 'India',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'wallet_balance' => 0,
                'withdrawable_balance' => 0,
                'referred_by' => $agent1->id,
            ]
        );
        ReferralService::ensureUserReferralCode($student1);

        // Test Student 2 (referred by Student 1)
        $student2 = User::firstOrCreate(
            ['email' => 'student2@unicou.test'],
            [
                'user_id' => User::generateNextUserId('student', 'PK'),
                'name' => 'Ayesha Khan',
                'first_name' => 'Ayesha',
                'last_name' => 'Khan',
                'phone' => '+923001234568',
                'account_type' => 'student',
                'country_iso' => 'PK',
                'country' => 'Pakistan',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'wallet_balance' => 0,
                'withdrawable_balance' => 0,
                'referred_by' => $student1->id,
            ]
        );
        ReferralService::ensureUserReferralCode($student2);

        $testUsers = [$agent1, $agent2, $student1, $student2];

        // Also include optional user ID if supplied via flag
        if ($this->option('user-id')) {
            $customUser = User::find($this->option('user-id'));
            if ($customUser) {
                $testUsers[] = $customUser;
                $this->info("Included extra user #{$customUser->id} ({$customUser->email}) in seeding.");
            }
        }

        // Credit 1 Lakh INR to each user's wallet
        $this->info("\n2. Crediting 1 Lakh INR ($" . number_format($baseUsdAmount, 2) . " USD) to wallets:");
        foreach ($testUsers as $u) {
            WalletService::credit(
                $u,
                $baseUsdAmount,
                'wallet_topup',
                "Wallet credit: 1 Lakh INR (₹100,000 converted to $" . number_format($baseUsdAmount, 2) . " USD)"
            );
            $u->refresh();
            $this->line("   - User #{$u->id} [{$u->account_type}] {$u->name} ({$u->email}) -> Current Balance: $" . number_format($u->wallet_balance, 2) . " USD");
        }

        // -------------------------------------------------------------
        // STEP 3: STOCK UPLOAD 10 VOUCHERS GLOBALLY
        // -------------------------------------------------------------
        $this->info("\n3. Creating & Stock-uploading 10 Global Vouchers (10 units uploaded each):");

        $globalVouchersConfig = [
            [
                'sku_id' => 'GLOB-PTE-2026',
                'brand_name' => 'Pearson PTE Academic Global',
                'voucher_type' => 'PTE',
                'voucher_variant' => 'Academic',
                'purchase_value_per_unit' => 110.00,
                'agent_sale_price' => 135.00,
                'student_sale_price' => 155.00,
                'student_bonus_points' => 500,
                'student_referral_points' => 300,
                'agent_bonus_points' => 800,
                'agent_referral_points' => 400,
                'reseller_points' => 350,
                'code_prefix' => 'GLOB-PTE-26-',
            ],
            [
                'sku_id' => 'GLOB-IELTS-2026',
                'brand_name' => 'IDP IELTS Academic Global',
                'voucher_type' => 'IELTS',
                'voucher_variant' => 'Academic',
                'purchase_value_per_unit' => 120.00,
                'agent_sale_price' => 145.00,
                'student_sale_price' => 165.00,
                'student_bonus_points' => 450,
                'student_referral_points' => 250,
                'agent_bonus_points' => 750,
                'agent_referral_points' => 350,
                'reseller_points' => 300,
                'code_prefix' => 'GLOB-IELTS-26-',
            ],
            [
                'sku_id' => 'GLOB-TOEFL-2026',
                'brand_name' => 'ETS TOEFL iBT Global',
                'voucher_type' => 'TOEFL',
                'voucher_variant' => 'iBT',
                'purchase_value_per_unit' => 130.00,
                'agent_sale_price' => 155.00,
                'student_sale_price' => 175.00,
                'student_bonus_points' => 600,
                'student_referral_points' => 350,
                'agent_bonus_points' => 900,
                'agent_referral_points' => 450,
                'reseller_points' => 400,
                'code_prefix' => 'GLOB-TOEFL-26-',
            ],
            [
                'sku_id' => 'GLOB-GRE-2026',
                'brand_name' => 'ETS GRE General Global',
                'voucher_type' => 'GRE',
                'voucher_variant' => 'General',
                'purchase_value_per_unit' => 140.00,
                'agent_sale_price' => 165.00,
                'student_sale_price' => 185.00,
                'student_bonus_points' => 700,
                'student_referral_points' => 400,
                'agent_bonus_points' => 1000,
                'agent_referral_points' => 500,
                'reseller_points' => 450,
                'code_prefix' => 'GLOB-GRE-26-',
            ],
            [
                'sku_id' => 'GLOB-GMAT-2026',
                'brand_name' => 'GMAC GMAT Focus Global',
                'voucher_type' => 'GMAT',
                'voucher_variant' => 'Focus Edition',
                'purchase_value_per_unit' => 150.00,
                'agent_sale_price' => 180.00,
                'student_sale_price' => 200.00,
                'student_bonus_points' => 800,
                'student_referral_points' => 450,
                'agent_bonus_points' => 1200,
                'agent_referral_points' => 600,
                'reseller_points' => 500,
                'code_prefix' => 'GLOB-GMAT-26-',
            ],
            [
                'sku_id' => 'GLOB-DUOLINGO-2026',
                'brand_name' => 'Duolingo English Test Global',
                'voucher_type' => 'Duolingo',
                'voucher_variant' => 'Certified',
                'purchase_value_per_unit' => 45.00,
                'agent_sale_price' => 55.00,
                'student_sale_price' => 65.00,
                'student_bonus_points' => 200,
                'student_referral_points' => 150,
                'agent_bonus_points' => 300,
                'agent_referral_points' => 200,
                'reseller_points' => 180,
                'code_prefix' => 'GLOB-DET-26-',
            ],
            [
                'sku_id' => 'GLOB-SAT-2026',
                'brand_name' => 'CollegeBoard SAT Global',
                'voucher_type' => 'SAT',
                'voucher_variant' => 'Digital',
                'purchase_value_per_unit' => 80.00,
                'agent_sale_price' => 95.00,
                'student_sale_price' => 110.00,
                'student_bonus_points' => 400,
                'student_referral_points' => 250,
                'agent_bonus_points' => 600,
                'agent_referral_points' => 300,
                'reseller_points' => 280,
                'code_prefix' => 'GLOB-SAT-26-',
            ],
            [
                'sku_id' => 'GLOB-OET-2026',
                'brand_name' => 'OET Healthcare Global',
                'voucher_type' => 'OET',
                'voucher_variant' => 'Nursing/Medicine',
                'purchase_value_per_unit' => 160.00,
                'agent_sale_price' => 190.00,
                'student_sale_price' => 215.00,
                'student_bonus_points' => 850,
                'student_referral_points' => 500,
                'agent_bonus_points' => 1300,
                'agent_referral_points' => 650,
                'reseller_points' => 550,
                'code_prefix' => 'GLOB-OET-26-',
            ],
            [
                'sku_id' => 'GLOB-ACT-2026',
                'brand_name' => 'ACT Assessment Global',
                'voucher_type' => 'ACT',
                'voucher_variant' => 'Standard',
                'purchase_value_per_unit' => 85.00,
                'agent_sale_price' => 105.00,
                'student_sale_price' => 120.00,
                'student_bonus_points' => 450,
                'student_referral_points' => 300,
                'agent_bonus_points' => 650,
                'agent_referral_points' => 350,
                'reseller_points' => 300,
                'code_prefix' => 'GLOB-ACT-26-',
            ],
            [
                'sku_id' => 'GLOB-PTE-CORE-2026',
                'brand_name' => 'Pearson PTE Core Global (Canada Visa)',
                'voucher_type' => 'PTE',
                'voucher_variant' => 'Core',
                'purchase_value_per_unit' => 115.00,
                'agent_sale_price' => 140.00,
                'student_sale_price' => 160.00,
                'student_bonus_points' => 550,
                'student_referral_points' => 350,
                'agent_bonus_points' => 850,
                'agent_referral_points' => 450,
                'reseller_points' => 380,
                'code_prefix' => 'GLOB-PTE-CR-',
            ],
        ];

        $seededVouchers = [];
        foreach ($globalVouchersConfig as $idx => $vConf) {
            // Generate 10 codes
            $uploadCodes = [];
            for ($i = 1; $i <= 10; $i++) {
                $uploadCodes[] = $vConf['code_prefix'] . str_pad($i, 3, '0', STR_PAD_LEFT);
            }

            $voucher = InventoryVoucher::updateOrCreate(
                ['sku_id' => $vConf['sku_id']],
                [
                    'brand_name' => $vConf['brand_name'],
                    'country_region' => ['all'],
                    'state' => ['all'],
                    'city' => ['all'],
                    'currency' => 'USD',
                    'voucher_variant' => $vConf['voucher_variant'],
                    'voucher_type' => $vConf['voucher_type'],
                    'purchase_invoice_no' => 'INV-GLOB-' . (202600 + $idx),
                    'purchase_date' => Carbon::now()->subDays(10),
                    'expiry_date' => Carbon::now()->addYear(),
                    'quantity' => 10,
                    'opening_stock_qty' => 10,
                    'purchased_qty' => 10,
                    'status' => 'IN STOCK',
                    'purchase_value' => $vConf['purchase_value_per_unit'] * 10,
                    'purchase_value_per_unit' => $vConf['purchase_value_per_unit'],
                    'taxes' => 0.00,
                    'agent_sale_price' => $vConf['agent_sale_price'],
                    'student_sale_price' => $vConf['student_sale_price'],
                    'student_bonus_points_per_unit' => $vConf['student_bonus_points'],
                    'student_referral_points_per_unit' => $vConf['student_referral_points'],
                    'agent_bonus_points_per_unit' => $vConf['agent_bonus_points'],
                    'agent_referral_points_per_unit' => $vConf['agent_referral_points'],
                    'referral_points_reseller' => $vConf['reseller_points'],
                    'upload_vouchers' => $uploadCodes,
                    'delivered_vouchers' => [],
                    'instructions' => 'Redeem directly on official testing portal globally.',
                    'is_expired' => false,
                ]
            );

            $seededVouchers[$vConf['sku_id']] = $voucher;
            $this->line("   - SKU: {$voucher->sku_id} | Brand: {$voucher->brand_name} (10 codes uploaded)");
        }

        // -------------------------------------------------------------
        // STEP 4: AGENTS AND STUDENTS BUY TOTAL 6 VOUCHERS VIA WALLET
        // -------------------------------------------------------------
        $this->info("\n4. Executing Wallet Purchases for Total 6 Vouchers from GLOB-PTE-2026:");

        $mainVoucher = $seededVouchers['GLOB-PTE-2026'];
        $codes = $mainVoucher->upload_vouchers;

        // Breakdown:
        // Purchase 1: Student 1 (Rahul) buys 2 vouchers
        // Purchase 2: Student 2 (Ayesha) buys 1 voucher
        // Purchase 3: Agent 1 (Apex Global) buys 2 vouchers
        // Purchase 4: Agent 2 (Global Study Reseller) buys 1 voucher
        // Total = 2 + 1 + 2 + 1 = 6 vouchers!
        $purchases = [
            [
                'user' => $student1,
                'qty' => 2,
                'price_per_unit' => (float) $mainVoucher->student_sale_price,
                'codes' => array_slice($codes, 0, 2),
                'label' => 'Student 1 (Rahul Sharma)',
            ],
            [
                'user' => $student2,
                'qty' => 1,
                'price_per_unit' => (float) $mainVoucher->student_sale_price,
                'codes' => array_slice($codes, 2, 1),
                'label' => 'Student 2 (Ayesha Khan)',
            ],
            [
                'user' => $agent1,
                'qty' => 2,
                'price_per_unit' => (float) $mainVoucher->agent_sale_price,
                'codes' => array_slice($codes, 3, 2),
                'label' => 'Agent 1 (Apex Global Agent)',
            ],
            [
                'user' => $agent2,
                'qty' => 1,
                'price_per_unit' => (float) $mainVoucher->agent_sale_price,
                'codes' => array_slice($codes, 5, 1),
                'label' => 'Agent 2 (Global Study Partners)',
            ],
        ];

        $deliveredCodes = [];
        $createdOrders = [];

        foreach ($purchases as $pIdx => $p) {
            $user = $p['user'];
            $qty = $p['qty'];
            $totalAmount = $qty * $p['price_per_unit'];
            $orderCodes = $p['codes'];
            $deliveredCodes = array_merge($deliveredCodes, $orderCodes);

            // 1. Debit User Wallet
            WalletService::debit(
                $user,
                $totalAmount,
                'order_purchase',
                "Payment for {$qty}x {$mainVoucher->brand_name} via Wallet"
            );

            // 2. Credit Admin Wallet
            if ($admin) {
                WalletService::credit(
                    $admin,
                    $totalAmount,
                    'order_revenue',
                    "Revenue from {$user->name} for {$qty}x {$mainVoucher->brand_name}",
                    true
                );
            }

            // 3. Create Delivered Order
            $orderId = 'PUR-' . date('Ymd') . '-T' . ($pIdx + 1) . rand(100, 999);
            $order = Order::create([
                'order_id' => $orderId,
                'user_id' => $user->id,
                'sub_agent_id' => $user->referred_by,
                'user_role' => $user->account_type,
                'voucher_type' => $mainVoucher->voucher_type,
                'voucher_id' => $mainVoucher->sku_id,
                'quantity' => $qty,
                'amount' => $totalAmount,
                'currency' => 'USD',
                'exchange_rate_applied' => 1.0,
                'amount_in_base_currency' => $totalAmount,
                'tax_amount' => 0.00,
                'promo_discount_amount' => 0.00,
                'membership_discount_amount' => 0.00,
                'points_redemption_amount' => 0.00,
                'is_full_points_redemption' => false,
                'status' => 'delivered',
                'payment_method' => 'wallet',
                'delivery_details' => implode("\n", $orderCodes),
                'referral_points' => ($user->isStudent() ? $mainVoucher->student_referral_points_per_unit : $mainVoucher->agent_referral_points_per_unit) * $qty,
                'bonus_amount' => ($user->isStudent() ? $mainVoucher->student_bonus_points_per_unit : $mainVoucher->agent_bonus_points_per_unit) * $qty,
                'created_at' => Carbon::now()->subHours(rand(2, 48)),
            ]);

            $createdOrders[] = $order;

            $this->line(sprintf(
                "   - [%s] bought %d voucher(s) | Order #%s | Total: $%s | Codes: %s",
                $p['label'],
                $qty,
                $order->order_id,
                number_format($totalAmount, 2),
                implode(', ', $orderCodes)
            ));
        }

        // Update Main Voucher stock
        $mainVoucher->delivered_vouchers = $deliveredCodes;
        $mainVoucher->quantity = 10 - count($deliveredCodes); // 4 remaining
        $mainVoucher->save();

        $this->info(sprintf(
            "   -> Total Vouchers Bought: %d | Remaining Stock in SKU %s: %d units",
            count($deliveredCodes),
            $mainVoucher->sku_id,
            $mainVoucher->quantity
        ));

        // -------------------------------------------------------------
        // STEP 5: AWARD DIVERSE BONUS POINTS & REFERRAL POINTS (RANDOM / REALISTIC)
        // -------------------------------------------------------------
        $this->info("\n5. Generating Diverse Bonus Points & Referral Points for Testing (Available, Pending, Held, Redeemed):");

        $pointsLedgerEntries = [
            // --- AVAILABLE POINTS (READY FOR INSTANT WALLET REDEMPTION TESTING) ---
            [
                'user_id' => $agent1->id,
                'order_id' => $createdOrders[2]->id,
                'points' => 2400,
                'points_remaining' => 2400,
                'point_type' => 'agent_monthly_bonus',
                'status' => 'available',
                'available_at' => Carbon::now()->subDays(5),
                'expires_at' => Carbon::now()->addDays(25),
                'description' => 'Monthly Bonus: 2400 pts (Available - Ready for Wallet Redeem)',
                'metadata' => ['admin_notes' => 'Matured and verified for test redemption'],
            ],
            [
                'user_id' => $agent1->id,
                'order_id' => $createdOrders[0]->id,
                'points' => 1200,
                'points_remaining' => 1200,
                'point_type' => 'b2b_referral',
                'status' => 'available',
                'available_at' => Carbon::now()->subDays(10),
                'expires_at' => Carbon::now()->addDays(355),
                'description' => 'B2B Referral: 1200 pts (Available - Ready for Wallet Redeem)',
                'metadata' => ['referred_user' => $student1->name],
            ],
            [
                'user_id' => $student1->id,
                'order_id' => $createdOrders[0]->id,
                'points' => 1000,
                'points_remaining' => 1000,
                'point_type' => 'student_purchase_bonus',
                'status' => 'available',
                'available_at' => Carbon::now()->subDays(2),
                'expires_at' => Carbon::now()->addDays(363),
                'description' => 'Student Purchase Bonus: 1000 pts (Available - Ready for Wallet Redeem)',
                'metadata' => ['admin_notes' => 'Early approved by system for test'],
            ],
            [
                'user_id' => $student1->id,
                'order_id' => $createdOrders[1]->id,
                'points' => 600,
                'points_remaining' => 600,
                'point_type' => 'student_referral',
                'status' => 'available',
                'available_at' => Carbon::now()->subDays(4),
                'expires_at' => Carbon::now()->addDays(360),
                'description' => 'Student Referral Bonus: 600 pts (Available - Ready for Wallet Redeem)',
                'metadata' => ['referred_user' => $student2->name],
            ],
            [
                'user_id' => $agent2->id,
                'order_id' => $createdOrders[3]->id,
                'points' => 1800,
                'points_remaining' => 1800,
                'point_type' => 'agent_quarterly_bonus',
                'status' => 'available',
                'available_at' => Carbon::now()->subDays(1),
                'expires_at' => Carbon::now()->addDays(30),
                'description' => 'Quarterly Bonus: 1800 pts (Available - Ready for Wallet Redeem)',
                'metadata' => ['quarter' => 'Q3 2026'],
            ],

            // --- PENDING RELEASE POINTS (READY FOR ADMIN APPROVAL TESTING) ---
            [
                'user_id' => $agent1->id,
                'order_id' => $createdOrders[2]->id,
                'points' => 1600,
                'points_remaining' => 1600,
                'point_type' => 'agent_monthly_bonus',
                'status' => 'pending',
                'available_at' => Carbon::now()->addDays(12),
                'expires_at' => Carbon::now()->addDays(42),
                'description' => 'Monthly Bonus: 1600 pts (Pending - Test Admin Approval & Instant Redeem Checkbox)',
                'metadata' => ['earning_month' => date('Y-m')],
            ],
            [
                'user_id' => $student1->id,
                'order_id' => $createdOrders[0]->id,
                'points' => 500,
                'points_remaining' => 500,
                'point_type' => 'student_purchase_bonus',
                'status' => 'pending',
                'available_at' => Carbon::now()->addDays(10),
                'expires_at' => Carbon::now()->addDays(375),
                'description' => 'Student Purchase Bonus: 500 pts (Pending - Test Admin Approval)',
                'metadata' => ['order_id' => $createdOrders[0]->order_id],
            ],
            [
                'user_id' => $agent2->id,
                'order_id' => $createdOrders[3]->id,
                'points' => 900,
                'points_remaining' => 900,
                'point_type' => 'agent_monthly_bonus',
                'status' => 'pending',
                'available_at' => Carbon::now()->addDays(8),
                'expires_at' => Carbon::now()->addDays(38),
                'description' => 'Monthly Bonus: 900 pts (Pending - Test Admin Approval)',
                'metadata' => ['earning_month' => date('Y-m')],
            ],
            [
                'user_id' => $student2->id,
                'order_id' => $createdOrders[1]->id,
                'points' => 400,
                'points_remaining' => 400,
                'point_type' => 'student_referral',
                'status' => 'pending',
                'available_at' => Carbon::now()->addDays(7),
                'expires_at' => Carbon::now()->addDays(372),
                'description' => 'Student Referral Bonus: 400 pts (Pending - Test Admin Approval)',
                'metadata' => ['order_id' => $createdOrders[1]->order_id],
            ],

            // --- FLAGGED ON HOLD POINTS (READY FOR ADMIN RELEASE TESTING) ---
            [
                'user_id' => $agent1->id,
                'order_id' => $createdOrders[2]->id,
                'points' => 1500,
                'points_remaining' => 1500,
                'point_type' => 'agent_monthly_bonus',
                'status' => 'held',
                'available_at' => Carbon::now()->subDays(1),
                'expires_at' => Carbon::now()->addDays(30),
                'description' => 'Monthly Bonus: 1500 pts (On Hold - Test Admin Release Button)',
                'metadata' => ['admin_notes' => 'Flagged under review for sales audit'],
            ],
            [
                'user_id' => $student1->id,
                'order_id' => $createdOrders[0]->id,
                'points' => 300,
                'points_remaining' => 300,
                'point_type' => 'student_referral',
                'status' => 'held',
                'available_at' => Carbon::now()->subDays(3),
                'expires_at' => Carbon::now()->addDays(362),
                'description' => 'Student Referral Bonus: 300 pts (On Hold - Test Admin Release Button)',
                'metadata' => ['admin_notes' => 'Hold pending KYC document verification'],
            ],
            [
                'user_id' => $agent2->id,
                'order_id' => $createdOrders[3]->id,
                'points' => 600,
                'points_remaining' => 600,
                'point_type' => 'agent_yearly_bonus',
                'status' => 'held',
                'available_at' => Carbon::now()->subDays(2),
                'expires_at' => Carbon::now()->addDays(365),
                'description' => 'Yearly Bonus: 600 pts (On Hold - Test Admin Release Button)',
                'metadata' => ['admin_notes' => 'Hold pending annual audit'],
            ],

            // --- REDEEMED POINTS (FOR HISTORY VERIFICATION) ---
            [
                'user_id' => $student1->id,
                'order_id' => null,
                'points' => -500,
                'points_remaining' => 0,
                'point_type' => 'redemption',
                'status' => 'redeemed',
                'available_at' => Carbon::now()->subDays(6),
                'expires_at' => Carbon::now()->subDays(6),
                'description' => 'Redeemed 500 pts to wallet (Test Seed History)',
                'metadata' => ['wallet_credit_usd' => 1.80],
            ],
            [
                'user_id' => $agent1->id,
                'order_id' => null,
                'points' => -1000,
                'points_remaining' => 0,
                'point_type' => 'redemption',
                'status' => 'redeemed',
                'available_at' => Carbon::now()->subDays(3),
                'expires_at' => Carbon::now()->subDays(3),
                'description' => 'Redeemed 1000 pts to wallet (Test Seed History)',
                'metadata' => ['wallet_credit_usd' => 3.60],
            ],
        ];

        foreach ($pointsLedgerEntries as $entry) {
            $createdLedger = RewardPointsLedger::create($entry);
            $this->line(sprintf(
                "   - [%s] User #%d | %s | %d pts | Status: %s",
                $createdLedger->point_type,
                $createdLedger->user_id,
                $createdLedger->description,
                $createdLedger->points,
                strtoupper($createdLedger->status)
            ));
        }

        // Summary of User Accounts
        $this->info("\n=======================================================================");
        $this->info("                       TEST CREDENTIALS & SUMMARY                     ");
        $this->info("=======================================================================");
        $this->info("Password for all test users: password123\n");

        $this->table(
            ['ID', 'Role', 'Name', 'Email', 'Wallet (USD)', 'Available Pts', 'Pending Pts', 'Held Pts'],
            [
                [
                    $agent1->id,
                    'Agent',
                    $agent1->name,
                    $agent1->email,
                    '$' . number_format($agent1->wallet_balance, 2),
                    number_format(RewardPointsLedger::where('user_id', $agent1->id)->where('status', 'available')->sum('points_remaining')),
                    number_format(RewardPointsLedger::where('user_id', $agent1->id)->where('status', 'pending')->sum('points')),
                    number_format(RewardPointsLedger::where('user_id', $agent1->id)->where('status', 'held')->sum('points')),
                ],
                [
                    $agent2->id,
                    'Reseller Agent',
                    $agent2->name,
                    $agent2->email,
                    '$' . number_format($agent2->wallet_balance, 2),
                    number_format(RewardPointsLedger::where('user_id', $agent2->id)->where('status', 'available')->sum('points_remaining')),
                    number_format(RewardPointsLedger::where('user_id', $agent2->id)->where('status', 'pending')->sum('points')),
                    number_format(RewardPointsLedger::where('user_id', $agent2->id)->where('status', 'held')->sum('points')),
                ],
                [
                    $student1->id,
                    'Student',
                    $student1->name,
                    $student1->email,
                    '$' . number_format($student1->wallet_balance, 2),
                    number_format(RewardPointsLedger::where('user_id', $student1->id)->where('status', 'available')->sum('points_remaining')),
                    number_format(RewardPointsLedger::where('user_id', $student1->id)->where('status', 'pending')->sum('points')),
                    number_format(RewardPointsLedger::where('user_id', $student1->id)->where('status', 'held')->sum('points')),
                ],
                [
                    $student2->id,
                    'Student',
                    $student2->name,
                    $student2->email,
                    '$' . number_format($student2->wallet_balance, 2),
                    number_format(RewardPointsLedger::where('user_id', $student2->id)->where('status', 'available')->sum('points_remaining')),
                    number_format(RewardPointsLedger::where('user_id', $student2->id)->where('status', 'pending')->sum('points')),
                    number_format(RewardPointsLedger::where('user_id', $student2->id)->where('status', 'held')->sum('points')),
                ],
            ]
        );

        $this->info("Stock Upload Summary:");
        $this->line("   - 10 Global Vouchers Created with 10 codes each (Total 100 codes in catalog).");
        $this->line("   - SKU 'GLOB-PTE-2026': 10 uploaded, 6 bought via wallet, 4 remaining in stock.");
        $this->line("   - 6 Delivered Orders logged across Agents and Students.");
        $this->info("Now visit /dashboard/bonus-point or /dashboard/referral to test and verify!");

        return Command::SUCCESS;
    }
}
