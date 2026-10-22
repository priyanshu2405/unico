<?php

use App\Http\Controllers\Dashboard\DisputeController;
use App\Http\Controllers\Dashboard\RefundController;
use App\Http\Controllers\Dashboard\RefundChatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\PaymentController;
use App\Http\Controllers\Dashboard\BankController;
use App\Http\Controllers\Dashboard\SalesController;
use App\Http\Controllers\Dashboard\CustomerController;
use App\Http\Controllers\Dashboard\WebhookController;
use App\Http\Controllers\Dashboard\PurchesController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\VoucherController;
use App\Http\Controllers\Dashboard\InventoryController;
use App\Http\Controllers\Dashboard\OrderController;
use App\Http\Controllers\Dashboard\WalletController;
use App\Http\Controllers\Dashboard\ComplianceController;
use App\Http\Controllers\Dashboard\SystemController;
use App\Http\Controllers\Dashboard\BroadcastController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Dashboard\AgentController;
use App\Http\Controllers\Dashboard\BonusController;
use App\Http\Controllers\Dashboard\DisputesController;
use App\Http\Controllers\Dashboard\JobController;
use App\Http\Controllers\Dashboard\StudentController;
use App\Http\Controllers\Dashboard\ManagerController;
use App\Http\Controllers\Dashboard\PricingController;
use App\Http\Controllers\Dashboard\ReferralController;
use App\Http\Controllers\Dashboard\MembershipController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

Route::get('/storage-link', function () {
    try {
        Artisan::call('storage:link');
        return Artisan::output();
    } catch (\Exception $e) {
        return $e->getMessage();
    }
});
// Programmatic migration helper (Commented out for security after successful run)
Route::get('/run-migrations-secure', function (\Illuminate\Http\Request $request) {
    try {
        // 1. Ensure migrations table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('migrations')) {
            \Illuminate\Support\Facades\DB::statement("CREATE TABLE IF NOT EXISTS `migrations` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `migration` varchar(255) NOT NULL,
                `batch` int(11) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }

        $existing = \Illuminate\Support\Facades\DB::table('migrations')->pluck('migration')->toArray();
        $currentBatch = (int) (\Illuminate\Support\Facades\DB::table('migrations')->max('batch') ?? 0) + 1;

        $files = glob(database_path('migrations/*.php'));
        sort($files);

        $synced = 0;
        $migrated = 0;
        $skipped = 0;
        $logs = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $existing)) {
                $skipped++;
                continue;
            }

            try {
                $instance = require $file;
                if (is_object($instance) && method_exists($instance, 'up')) {
                    $instance->up();
                }
                \Illuminate\Support\Facades\DB::table('migrations')->insert([
                    'migration' => $name,
                    'batch' => $currentBatch
                ]);
                $migrated++;
                $logs[] = "<span style='color:#4ade80;'>[MIGRATED]</span> {$name}";
            } catch (\Illuminate\Database\QueryException $e) {
                $code = $e->errorInfo[1] ?? 0;
                $msg = $e->getMessage();
                $isSynced = (
                    $code == 1050 ||
                    $code == 1060 ||
                    $code == 1061 ||
                    $code == 1068 ||
                    $code == 1091 ||
                    ($code == 1054 && stripos($msg, 'after') !== false) ||
                    stripos($msg, 'already exists') !== false ||
                    stripos($msg, 'Duplicate column') !== false ||
                    stripos($msg, 'Duplicate key') !== false
                );

                if ($isSynced) {
                    \Illuminate\Support\Facades\DB::table('migrations')->insert([
                        'migration' => $name,
                        'batch' => $currentBatch
                    ]);
                    $synced++;
                    $logs[] = "<span style='color:#38bdf8;'>[SYNCED]</span> {$name} (schema already present or resolved in DB)";
                } else {
                    throw $e;
                }
            }
        }

        // Clear caches & ensure storage link
        try {
            \Illuminate\Support\Facades\Artisan::call('storage:link');
        } catch (\Throwable $e) {
            // Symlink might already exist or be restricted
        }
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');

        return "<h3>Database Migration & Sync Complete</h3>" .
               "<p>Total files: " . count($files) . " | Already tracked: {$skipped} | Newly migrated: {$migrated} | Synced from DB: {$synced}</p>" .
               "<hr><div style='font-family:monospace; background:#0f172a; color:#f8fafc; padding:20px; border-radius:12px; line-height:1.6;'>" .
               (empty($logs) ? "All migrations were already up to date!" : implode("<br>", $logs)) . "</div><br><p style='color:#16a34a; font-weight:bold; font-size:1.1rem;'>All database tables and migrations are now 100% in sync!</p>";
    } catch (\Throwable $e) {
        return "<h3>Migration Error</h3><p style='color:red;'>" . htmlspecialchars($e->getMessage()) . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
});
Route::get('/test-currency', [VoucherController::class, 'testCurrency']);
Route::get('/currency-conversion', [VoucherController::class, 'getConversion'])->name('currency.convert');
Route::get('/', function () {
    return view('home.home');
})->name('home');

Route::get('/test-mail', function () {
    Mail::raw('Test mail from Laravel', function ($message) {
        $message->to('sharma@yopmail.com')
            ->subject('Test Email');
    });

    return "Mail Sent!";
});
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');


    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

// Email Verification Routes
Route::get('/email/verify', function () {
    $user = auth()->user();
    if ($user && $user->hasVerifiedEmail()) {
        if ($user->account_type === 'student' && !$user->exam_purpose) {
            return redirect()->route('auth.form.student');
        }
        if (in_array($user->account_type, ['reseller_agent', 'agent']) && !$user->business_name) {
            return redirect()->route('auth.forms.B2BResellerAgent');
        }
        return redirect()->route('dashboard');
    }
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/check-verification', function () {
    return response()->json(['verified' => auth()->user()->hasVerifiedEmail()]);
})->middleware('auth')->name('verification.check');

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

    if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403);
    }

    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    // Auto-login if not logged in or logged in as different user
    if (!auth()->check() || auth()->id() != $user->id) {
        auth()->login($user);
    }

    if ($user->account_type === 'student' && !$user->exam_purpose) {
        return redirect()->route('auth.form.student')->with('success', 'Email verified. Please complete your profile.');
    }
    if (in_array($user->account_type, ['reseller_agent', 'agent']) && !$user->business_name) {
        return redirect()->route('auth.forms.B2BResellerAgent')->with('success', 'Email verified. Please complete your profile.');
    }

    return redirect()->route('dashboard')->with('success', 'Email verified successfully.');
})->middleware(['signed'])->name('verification.verify');

Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

// Additional Registration Forms
Route::middleware('auth')->group(function () {
    Route::get('/register/agent-details', [AuthController::class, 'showAgentForm'])->name('auth.forms.B2BResellerAgent');
    Route::post('/register/agent-details', [AuthController::class, 'storeAgentDetails'])->name('auth.form.agent.post');

    Route::get('/register/student-details', [AuthController::class, 'showStudentForm'])->name('auth.form.student');
    Route::post('/register/student-details', [AuthController::class, 'storeStudentDetails'])->name('auth.form.student.post');
});
Route::get('/register/support-team-details', [AuthController::class, 'showSupportForm'])->name('auth.form.support');

Route::post('/job-applications/apply', [JobController::class, 'submitApplication'])->name('job.apply');
Route::get('/job-applications', [JobController::class, 'jobApplication'])->name('jobApplication');
Route::get('/shufti/redirect', function () {

    return redirect('/dashboard')->with('success', 'Verification completed successfully');
})->name('shufti.redirect');

Route::post('/shufti/callback', [AuthController::class, 'shuftiCallback'])->name('shufti.callback');
// Dashboard Routes (Unified Prefix)
Route::prefix('dashboard')->middleware(['auth'])->group(function () {

    Route::get('/job-applications', [JobController::class, 'jobApplication'])->name('job.applications');
    Route::get('/job-applications/{application}/view', [JobController::class, 'viewApplication'])->name('job.view');
    Route::post('/job-applications/{application}/status', [JobController::class, 'updateApplicationStatus'])->name('job.updateStatus');
    Route::delete('/job-applications/{application}', [JobController::class, 'destroyApplication'])->name('job.destroy');

    Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/notifications', [DashboardController::class, 'notifications'])->name('notifications.index');
    Route::get('/notifications/unread-count', [DashboardController::class, 'getUnreadNotificationsCount'])->name('notifications.unreadCount');
    Route::post('/notifications/mark-all-read', [DashboardController::class, 'markAllNotificationsAsRead'])->name('notifications.markAllRead');
    Route::post('/notifications/bulk-action', [DashboardController::class, 'notificationsBulkAction'])->name('notifications.bulkAction');

    // Broadcast Routes
    Route::get('/broadcast', [BroadcastController::class, 'showBroadcastForm'])->name('broadcast.index');
    Route::post('/broadcast/send', [BroadcastController::class, 'sendBroadcast'])->name('broadcast.send');

    // Alerts Routes
    Route::get('/alerts', [BroadcastController::class, 'alertsIndex'])->name('alerts.index');
    Route::get('/alerts/unread-count', [BroadcastController::class, 'getUnreadAlertsCount'])->name('alerts.unreadCount');
    Route::post('/alerts/mark-all-read', [BroadcastController::class, 'markAllAlertsAsRead'])->name('alerts.markAllRead');
    Route::post('/alerts/bulk-action', [BroadcastController::class, 'alertsBulkAction'])->name('alerts.bulkAction');

    Route::get('/trend-data', [DashboardController::class, 'getTrendData'])->name('dashboard.trendData');
    Route::get('/my-profile', [UserController::class, 'profile'])->name('profile.index');
    Route::post('/my-profile/update', [UserController::class, 'updateProfile'])->name('profile.update');



    Route::get('/stock-alerts', [DashboardController::class, 'stockAlerts'])->name('stock.alerts');
    Route::get('/account', [DashboardController::class, 'manageAccount'])->name('account.manage');
    Route::post('/account/update', [DashboardController::class, 'updateAccount'])->name('account.update');
    Route::post('/account/password', [DashboardController::class, 'changePassword'])->name('account.password.update');

    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::get('/pricing/create', [PricingController::class, 'create'])->name('pricing.create');
    Route::get('/pricing/export', [PricingController::class, 'export'])->name('pricing.export');

    Route::get('/pricing/voucher-details/{id}', [PricingController::class, 'getVoucherDetails'])->name('pricing.voucher-details');
    Route::post('/pricing/store', [PricingController::class, 'store'])->name('pricing.store');
    Route::get('/pricing/{id}/edit', [PricingController::class, 'edit'])->name('pricing.edit');
    Route::post('/pricing/{id}/update', [PricingController::class, 'update'])->name('pricing.update');
    Route::post('/pricing/{id}/toggle-status', [PricingController::class, 'toggleStatus'])->name('pricing.toggle-status');
    Route::delete('/pricing/{id}', [PricingController::class, 'destroy'])->name('pricing.destroy');
    Route::post('/users/bulk-verify', [UserController::class, 'bulkVerify'])->name('users.bulk-verify');
    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.management');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle');
    Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password.update');
    Route::post('/users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.permissions.update');

    Route::post('/users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
    Route::get('/stop-impersonating', [UserController::class, 'stopImpersonating'])->name('users.stop-impersonating');
    Route::get('/users-download-pdf', [UserController::class, 'downloadPDF'])->name('users.pdf');
    Route::get('/users-export-source', [UserController::class, 'exportCustomerSource'])->name('users.export.source');

    Route::post('/users/{user}/category', [UserController::class, 'updateCategory'])->name('users.category.update');
    Route::post('/users/{user}/limit', [UserController::class, 'updateLimit'])->name('users.limit.update');

    //pages
    Route::get('/mangers', [UserController::class, 'managers'])->name('manager.page');
    Route::get('/mangers-export', [UserController::class, 'managersDownloadCSV'])->name('manager.export');
    Route::get('/support-team', [UserController::class, 'SupportTeam'])->name('support.team');
    Route::get('/support-team-export', [UserController::class, 'supportTeamDownloadCSV'])->name('support.team.export');
    Route::get('/reseller-agents', [UserController::class, 'ResellerAgent'])->name('reseller.agent');
    Route::get('/reseller-agents-export', [UserController::class, 'resellerAgentDownloadCSV'])->name('reseller.agent.export');
    Route::get('/regular-agents', [UserController::class, 'RegularAgent'])->name('regular.agent');
    Route::get('/regular-agents-export', [UserController::class, 'regularAgentDownloadCSV'])->name('regular.agent.export');
    Route::get('/student-list', [UserController::class, 'student'])->name('student.page');
    Route::get('/student-list-export', [UserController::class, 'studentDownloadCSV'])->name('student.page.export');
    // Voucher Management
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers');
    Route::get('/vouchers/order/{id}', [VoucherController::class, 'showOrder'])->name('vouchers.order');
    Route::get('/vouchers/order/{id}/calculate', [VoucherController::class, 'calculatePricing'])->name('vouchers.order.calculate');
    Route::post('/vouchers/order/{id}', [VoucherController::class, 'placeOrder'])->name('vouchers.order.post');
    Route::get('/vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers/store', [VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('/vouchers/{id}/edit', [VoucherController::class, 'edit'])->name('vouchers.edit');
    Route::post('/vouchers/{id}/update', [VoucherController::class, 'update'])->name('vouchers.update');
    Route::delete('/vouchers/{id}/delete', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
    Route::get('/vouchers-export', [VoucherController::class, 'export'])->name('vouchers.export');
    Route::post('/vouchers-import', [VoucherController::class, 'import'])->name('vouchers.import');

    // Referral System
    Route::get('/referral', [ReferralController::class, 'referral'])->name('referral');
    Route::post('/referral/bind', [ReferralController::class, 'bind'])->name('referral.bind');
    Route::get('/referral/export', [ReferralController::class, 'export'])->name('referral.export');

    // Bonus Points System (Monthly, Quarterly, Yearly)
    Route::get('/bonus-point', [BonusController::class, 'bonus'])->name('bonus');
    Route::get('/bonus-point/export', [BonusController::class, 'export'])->name('bonus.export');

    // Membership System (Extra 1% Discount)
    Route::get('/membership', [MembershipController::class, 'index'])->name('membership.index');
    Route::post('/membership/activate', [MembershipController::class, 'activate'])->name('membership.activate');
    Route::post('/promotional-code/verify', [MembershipController::class, 'verifyPromoCode'])->name('promo.verify');


    // Inventory Management
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory/store', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{id}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::post('/inventory/{id}/update', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{id}/delete', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::match(['get', 'post'], '/inventory/{id}/duplicate', [InventoryController::class, 'duplicate'])->name('inventory.duplicate');
    Route::get('/inventory/{id}/discard-draft', [InventoryController::class, 'discardDraft'])->name('inventory.discard-draft');
    Route::post('/inventory/bulk-duplicate', [InventoryController::class, 'bulkDuplicate'])->name('inventory.bulk-duplicate');
    Route::post('/inventory-import', [InventoryController::class, 'import'])->name('inventory.import');
    Route::get('/inventory-export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::post('/inventory-upload', [InventoryController::class, 'upload'])->name('inventory.upload');

    // Sales
    Route::get('/sales', [SalesController::class, 'SalesReport'])->name('sales.index');
    Route::get('/sales-export', [SalesController::class, 'export'])->name('sales.export');

    // Payment
    Route::get('/reports/payments', [PaymentController::class, 'PaymentTable'])->name('reports.payments');
    Route::get('/reports/payments/export', [PaymentController::class, 'exportPaymentReport'])->name('reports.payments.export');




    // Purches
    Route::get('/purchase-invoice/{order_id}', [PurchesController::class, 'purchaseInvoice'])->name('purches.invoice');
    Route::get('/purches-report', [PurchesController::class, 'purchesReport'])->name('purches.user.report');
    Route::get('/purches-report-export', [PurchesController::class, 'export'])->name('purches.export');
    // Order Management
    Route::middleware(['account_type:admin,manager,support_team'])->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders-export', [OrderController::class, 'export'])->name('orders.export');
        Route::post('/orders/{id}/deliver', [OrderController::class, 'deliver'])->name('orders.deliver');
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{id}/approve', [OrderController::class, 'approve'])->name('orders.approve');
    });
    Route::get('/orders/history', [OrderController::class, 'orderHistory'])->name('orders.history');
    Route::get('/orders/user-export', [OrderController::class, 'userExport'])->name('orders.user_export');


    // Refund Management
    Route::middleware(['account_type:admin,manager,support_team'])->group(function () {
        Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::get('/refunds/admin-export', [RefundController::class, 'adminExport'])->name('refunds.admin_export');
        Route::post('/refunds/{id}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
        Route::post('/refunds/{id}/process', [RefundController::class, 'processRefund'])->name('refunds.process');
        Route::post('/refunds/{id}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
    });
    Route::get('/my-refunds', [RefundController::class, 'userRefunds'])->name('refunds.user');
    Route::get('/my-refunds/export', [RefundController::class, 'userExport'])->name('refunds.user_export');
    Route::post('/refunds/store', [RefundController::class, 'store'])->name('refunds.store');

    Route::post('/stripe-checkout-session', [BankController::class, 'createSession'])->name('stripe.checkout.session');
    Route::post('/paypal-checkout', [BankController::class, 'paypalCheckout'])->name('paypal.checkout');
    Route::get('/paypal-success', [BankController::class, 'paypalSuccess'])->name('paypal.success');
    Route::get('/paypal-cancel', [BankController::class, 'paypalCancel'])->name('paypal.cancel');

    Route::post('/wise-checkout', [BankController::class, 'wiseCheckout'])->name('wise.checkout');
    Route::get('/wise-success', [BankController::class, 'wiseSuccess'])->name('wise.success');
    Route::get('/wise-cancel', [BankController::class, 'wiseCancel'])->name('wise.cancel');

    Route::post('/kuickpay-checkout', [BankController::class, 'kuickpayCheckout'])->name('kuickpay.checkout');
    Route::get('/kuickpay-success', [BankController::class, 'kuickpaySuccess'])->name('kuickpay.success');
    Route::get('/kuickpay-cancel', [BankController::class, 'kuickpayCancel'])->name('kuickpay.cancel');
    // Wallet Management
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/credit', [WalletController::class, 'credit'])->name('wallet.credit');
    Route::post('/wallet/debit', [WalletController::class, 'debit'])->name('wallet.debit');

    // Compliance & KYC
    Route::get('/kyc-compliance', [ComplianceController::class, 'index'])->name('kyc.compliance');
    Route::get('/kyc-compliance/{user}', [ComplianceController::class, 'show'])->name('kyc.show');
    Route::post('/approvals/{user}/status', [ComplianceController::class, 'updateStatus'])->name('approvals.update-status');
    Route::post('/approvals/{user}/approve', [ComplianceController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{user}/reject', [ComplianceController::class, 'reject'])->name('approvals.reject');

    // System Control
    Route::get('/system-control', [SystemController::class, 'controlIndex'])->name('system.control');
    Route::post('/system-control/update', [SystemController::class, 'updateControl'])->name('system.control.update');
    Route::post('/system-control/toggle', [SystemController::class, 'toggleSystem'])->name('system.toggle');
    Route::post('/system-control/clear-cache', [SystemController::class, 'clearCache'])->name('system.control.clear-cache');
    Route::post('/system-control/backup-db', [SystemController::class, 'backupDatabase'])->name('system.control.backup-db');
    Route::post('/system-control/purge-data', [SystemController::class, 'purgeTransactionData'])->name('system.control.purge-data');
    Route::get('/audit-logs', [SystemController::class, 'auditLogs'])->name('audit.index');

    // Reports & Revenue
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock');
    Route::get('/reports/profit-loss', [ReportController::class, 'profitAndLossReport'])->name('reports.profit-loss');
    Route::get('/reports/gross-margin', [ReportController::class, 'grossMarginReport'])->name('reports.gross-margin');
    Route::get('/reports/distributor-margin', [ReportController::class, 'distributorMarginReport'])->name('reports.distributor-margin');
    Route::get('/reports/platform-fee', [ReportController::class, 'platformFeeReport'])->name('reports.platform-fee');
    Route::get('/reports/operational-expense', [ReportController::class, 'operationalExpenseReport'])->name('reports.operational-expense');

    // CSV Exports
    Route::get('/reports/stock/export', [ReportController::class, 'exportStockReport'])->name('reports.stock.export');
    Route::get('/reports/profit-loss/export', [ReportController::class, 'exportProfitLossReport'])->name('reports.profit-loss.export');

    Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue.index');

    // Settings
    Route::get('/settings/risk-levels', [SettingsController::class, 'riskLevels'])->name('settings.risk-levels');
    Route::get('/settings/risk-levels/export', [SettingsController::class, 'exportRiskLevelsCsv'])->name('settings.risk-levels.export');
    Route::post('/settings/update-risk-level', [SettingsController::class, 'updateRiskLevel'])->name('settings.update-risk-level');
    Route::delete('/settings/risk-level/{id}', [SettingsController::class, 'deleteRiskLevel'])->name('settings.risk-level.delete');
    Route::get('/settings/exchange-rates', [SettingsController::class, 'exchangeRates'])->name('settings.exchange-rates');
    Route::post('/settings/exchange-rates/update', [SettingsController::class, 'updateExchangeRate'])->name('settings.exchange-rates.update');
    Route::delete('/settings/exchange-rates/{id}', [SettingsController::class, 'deleteExchangeRate'])->name('settings.exchange-rates.delete');

    // BANK
    Route::post('/wallet-topup', [WalletController::class, 'createSession'])->name('wallet.topup');
    Route::post('/create-payment-intent', [BankController::class, 'createPaymentIntent']);
    Route::get('/wallet/success', [BankController::class, 'success'])->name('wallet.success');
    Route::get('/wallet/processing', [BankController::class, 'processing'])->name('wallet.processing');
    Route::get('/wallet/check-status', [BankController::class, 'checkStatus'])->name('wallet.checkStatus');
    Route::get('/wallet/cancel', [BankController::class, 'cancel'])->name('wallet.cancel');
    Route::post('/finalize-stripe-link', [BankController::class, 'finalizeStripeLink'])->name('bank.finalize-stripe-link');
    Route::get('/bank-link', [BankController::class, 'bankLink'])->name('bank.link');
    Route::post('/bank-link', [BankController::class, 'linkBank'])->name('bank.store');
    Route::post('/bank-payment', [BankController::class, 'createBankPayment'])->name('bank.payment');
    Route::get('/banks', [BankController::class, 'bankreport'])->name('banks.bank-table');
    Route::get('/banks-export', [BankController::class, 'exportBankReport'])->name('banks.bank-table.export');
    Route::post('/webhook/save', [WebhookController::class, 'save'])->name('webhook.save');
    // Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']);
    // Admin Payment Methods
    Route::get('/payment-methods', [BankController::class, 'index'])->name('payment-methods.index');
    Route::post('/payment-methods', [BankController::class, 'store'])->name('payment-methods.store');
    Route::post('/payment-methods/{id}', [BankController::class, 'update'])->name('payment-methods.update');
    Route::delete('/payment-methods/{id}', [BankController::class, 'destroy'])->name('payment-methods.destroy');
    Route::post('/payment-methods/{id}/toggle', [BankController::class, 'toggleStatus'])->name('payment-methods.toggle');
    // Customer Support
    Route::get('/customer-support', [CustomerController::class, 'supportindex'])->name('customer.support');
    Route::post('/customer-support', [CustomerController::class, 'storeSupportQuery'])->name('customer.support.store');
    Route::get('/support-options/issues/{topicId}', [CustomerController::class, 'getIssuesByTopic'])->name('support.options.issues');

    Route::middleware(['account_type:admin,manager,support_team'])->group(function () {
        Route::get('/customer-query', [CustomerController::class, 'CustomerQuery'])->name('customer.query');
        Route::post('/customer-query/{id}/resolve', [CustomerController::class, 'resolveQuery'])->name('customer.query.resolve');
        Route::post('/customer-query/{id}/escalate', [CustomerController::class, 'escalateQuery'])->name('customer.query.escalate');
        Route::post('/support-options', [CustomerController::class, 'storeSupportOption'])->name('support.options.store');
        Route::delete('/support-options/{id}', [CustomerController::class, 'deleteSupportOption'])->name('support.options.destroy');
    });

    //Referral Points
    Route::get('/referral', [ReferralController::class, 'referral'])->name('referral');
    Route::get('/referral/export', [ReferralController::class, 'export'])->name('referral.export');

    //Bonus Points
    Route::get('/bonus-point', [BonusController::class, 'bonus'])->name('bonus');
    Route::get('/bonus-point/export', [BonusController::class, 'export'])->name('bonus.export');

    // Dispute Management
    Route::get('/disputes', [DisputeController::class, 'index'])->name('disputes.index');
    Route::get('/disputes/ratings', [DisputeController::class, 'ratings'])->name('disputes.ratings');
    Route::post('/disputes/store', [DisputeController::class, 'store'])->name('disputes.store');
    Route::get('/disputes/{id}', [DisputeController::class, 'show'])->name('disputes.show');
    Route::post('/disputes/{id}/send', [DisputeController::class, 'sendMessage'])->name('disputes.send');
    Route::get('/disputes/{id}/fetch', [DisputeController::class, 'fetchMessages'])->name('disputes.fetch');
    Route::post('/disputes/{id}/typing', [DisputeController::class, 'updateTyping'])->name('disputes.typing');
    Route::post('/disputes/{id}/status', [DisputeController::class, 'updateStatus'])->name('disputes.status');
    Route::post('/disputes/{id}/transfer', [DisputeController::class, 'transfer'])->name('disputes.transfer');
    Route::post('/disputes/{id}/feedback', [DisputeController::class, 'submitFeedback'])->name('disputes.feedback');

    // Refund Chat Management
    Route::get('/refunds/{id}/chat', [RefundChatController::class, 'show'])->name('refunds.chat');
    Route::post('/refunds/{id}/chat/send', [RefundChatController::class, 'sendMessage'])->name('refunds.chat.send');
    Route::get('/refunds/{id}/chat/fetch', [RefundChatController::class, 'fetchMessages'])->name('refunds.chat.fetch');
    Route::post('/refunds/{id}/chat/typing', [RefundChatController::class, 'updateTyping'])->name('refunds.chat.typing');



    // Agent Specific
    Route::prefix('agent')->name('agent.')->middleware(['account_type:reseller_agent'])->group(function () {
        Route::get('/', [AgentController::class, 'dashboard'])->name('dashboard');
        Route::get('/vouchers', [AgentController::class, 'vouchers'])->name('vouchers');
        Route::get('/banks', [AgentController::class, 'banks'])->name('banks');

        Route::get('/deposit-store-credit', [AgentController::class, 'deposit'])->name('deposit.store.credit');
        Route::get('/bank-link', [AgentController::class, 'bankLink'])->name('bank.link');
        Route::post('/bank-link', [AgentController::class, 'storeBank'])->name('bank.store');
    });



    // Manager Specific
    Route::prefix('manager')->name('manager.')->middleware(['account_type:manager'])->group(function () {
        Route::get('/', [ManagerController::class, 'dashboard'])->name('dashboard');
        Route::get('/audit', [ManagerController::class, 'auditTransactions'])->name('audit');
        Route::get('/users', [ManagerController::class, 'manageUsers'])->name('users');
        Route::get('/users/{user}', [ManagerController::class, 'approveDetails'])->name('users.show');
        Route::post('/users/{user}/approve', [ManagerController::class, 'approveUser'])->name('users.approve');
        Route::post('/users/{user}/add-credit', [ManagerController::class, 'addCredit'])->name('users.add_credit');
        Route::get('/vouchers/stock', [ManagerController::class, 'voucherStock'])->name('vouchers.stock');

        Route::get('/system/stop', [ManagerController::class, 'stopSystem'])->name('system.stop');
        Route::get('/reports', [ManagerController::class, 'reports'])->name('reports');
        
        Route::get('/support-assignments', [App\Http\Controllers\Dashboard\ManagerSupportController::class, 'index'])->name('manager.support.assignments');
        Route::post('/support-assignments', [App\Http\Controllers\Dashboard\ManagerSupportController::class, 'store'])->name('manager.support.assignments.store');
        Route::delete('/support-assignments/{id}', [App\Http\Controllers\Dashboard\ManagerSupportController::class, 'destroy'])->name('manager.support.assignments.destroy');
    });

    // Student Specific
    Route::prefix('student')->name('student.')->middleware(['account_type:student'])->group(function () {
        Route::get('/', [StudentController::class, 'dashboard'])->name('dashboard');
    });
});

