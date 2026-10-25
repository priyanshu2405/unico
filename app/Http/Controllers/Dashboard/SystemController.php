<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\VoucherPriceRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SystemController extends Controller
{
    public function controlIndex()
    {
        try {
            $settings = \Illuminate\Support\Facades\Schema::hasTable('system_settings')
                ? SystemSetting::all()->pluck('settings_value', 'settings_key')
                : collect([]);

            $logs = \Illuminate\Support\Facades\Schema::hasTable('audit_logs')
                ? AuditLog::where('action', 'like', 'System %')->latest()->limit(10)->get()
                : collect([]);
            
            // Get unique countries from users table
            $countries = User::whereNotNull('country_iso')
                ->where('country_iso', '!=', '')
                ->select('country_iso', 'country')
                ->distinct()
                ->get();

            // Get vouchers from InventoryVoucher (Stocks Upload)
            $vouchers = \Illuminate\Support\Facades\Schema::hasTable('inventory_vouchers')
                ? \App\Models\InventoryVoucher::select('id', 'brand_name', 'voucher_variant', 'country_region as country_name')->get()
                : collect([]);
        } catch (\Throwable $e) {
            \Log::warning('System control center query fallback: ' . $e->getMessage());
            $settings = collect([]);
            $logs = collect([]);
            $countries = collect([]);
            $vouchers = collect([]);
        }

        return view('dashboard.system.control', compact('settings', 'logs', 'countries', 'vouchers'));
    }

    public function updateControl(Request $request)
    {
        $inputSettings = $request->input('settings', []);

        // 1. Handle Global Stop Sales
        $stopAll = isset($inputSettings['stop_all_sales']) && $inputSettings['stop_all_sales'] == 'on';
        SystemSetting::updateOrCreate(['settings_key' => 'stop_all_sales'], ['settings_value' => $stopAll ? 'on' : 'off']);
        VoucherPriceRule::query()->update(['is_stopped' => $stopAll ? 1 : 0]);

        // 2. Handle Brand-wise Stop
        $stoppedBrands = $inputSettings['stopped_brands'] ?? [];
        SystemSetting::updateOrCreate(['settings_key' => 'stopped_brands'], ['settings_value' => json_encode($stoppedBrands)]);
        
        if (!empty($stoppedBrands)) {
            VoucherPriceRule::whereIn('brand_name', $stoppedBrands)->update(['is_brand_stopped' => 1]);
            VoucherPriceRule::whereNotIn('brand_name', $stoppedBrands)->update(['is_brand_stopped' => 0]);
        } else {
            VoucherPriceRule::query()->update(['is_brand_stopped' => 0]);
        }

        // 3. Handle Country-wise Stop
        $stoppedCountries = $inputSettings['stopped_countries'] ?? [];
        SystemSetting::updateOrCreate(['settings_key' => 'stopped_countries'], ['settings_value' => json_encode($stoppedCountries)]);

        if (!empty($stoppedCountries)) {
            VoucherPriceRule::whereIn('country_name', $stoppedCountries)->update(['is_country_stopped' => 1]);
            VoucherPriceRule::whereNotIn('country_name', $stoppedCountries)->update(['is_country_stopped' => 0]);
        } else {
            VoucherPriceRule::query()->update(['is_country_stopped' => 0]);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'System Update',
            'description' => 'System sales controls updated',
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', 'System settings updated.');
    }

    public function auditLogs(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $logs = AuditLog::with('user')->latest()->paginate($perPage)->withQueryString();
        return view('dashboard.audit.index', compact('logs'));
    }

    public function toggleSystem(Request $request)
    {
        if (auth()->user()->account_type === 'manager' && !auth()->user()->can_stop_system_sales) {
            return back()->with('error', 'Unauthorized action.');
        }

        $currentStatus = SystemSetting::where('settings_key', 'emergency_freeze')->first();
        $newStatus = ($currentStatus && $currentStatus->settings_value == 'on') ? 'off' : 'on';

        SystemSetting::updateOrCreate(
            ['settings_key' => 'emergency_freeze'],
            ['settings_value' => $newStatus]
        );

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'System Toggle',
            'description' => 'Emergency freeze toggled to ' . $newStatus,
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', 'System emergency status updated to ' . $newStatus);
    }

    public function clearCache(Request $request)
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'System Update',
                'description' => 'System caches cleared by admin',
                'ip_address' => $request->ip()
            ]);

            return back()->with('success', 'System caches cleared successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    public function backupDatabase(Request $request)
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $dbName = DB::getDatabaseName();
            $tableKey = "Tables_in_" . $dbName;
            
            $sqlDump = "-- Laravel Database Backup\n";
            $sqlDump .= "-- Database: " . $dbName . "\n";
            $sqlDump .= "-- Generated: " . now()->toDateTimeString() . "\n\n";
            
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                
                // Get Create Table statement
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                if (!empty($createTable)) {
                    $sqlDump .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                    $sqlDump .= $createTable[0]->{'Create Table'} . ";\n\n";
                }
                
                // Get rows
                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $rowArray = (array)$row;
                    $keys = array_keys($rowArray);
                    $escapedKeys = array_map(function($key) {
                        return "`" . addslashes($key) . "`";
                    }, $keys);
                    
                    $values = array_values($rowArray);
                    $escapedValues = array_map(function($value) {
                        if ($value === null) {
                            return "NULL";
                        }
                        return "'" . addslashes($value) . "'";
                    }, $values);
                    
                    $sqlDump .= "INSERT INTO `{$tableName}` (" . implode(', ', $escapedKeys) . ") VALUES (" . implode(', ', $escapedValues) . ");\n";
                }
                $sqlDump .= "\n";
            }
            
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'System Update',
                'description' => 'Database backup downloaded by admin',
                'ip_address' => $request->ip()
            ]);

            $fileName = $dbName . "_" . date('Y-m-d_H-i-s') . ".sql";
            
            return response($sqlDump)
                ->header('Content-Type', 'application/sql')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to backup database: ' . $e->getMessage());
        }
    }

    public function purgeTransactionData(Request $request)
    {
        // Only admin can purge
        if (auth()->user()->account_type !== 'admin') {
            return back()->with('error', 'Unauthorized: Only admin can perform this action.');
        }

        // Validate password confirmation
        $request->validate([
            'admin_password' => 'required|string',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->admin_password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password. Purge cancelled.');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');

            $tablesToPurge = [
                'orders',
                'inventory_vouchers',
                'wallet_ledger',
                'refund_requests',
                'refund_chat_messages',
                'exchange_rates',
                'voucher_price_rules',
                'admin_payment_methods',
                'agent_details',
                'student_details',
                'bank_accounts',
                'support_queries',
                'support_options',
                'support_assignments',
                'disputes',
                'dispute_messages',
                'notifications',
                'country_risk_levels',
                'contact_us',
                'job_applications',
                'job_vacancies',
                'sessions',
                'password_reset_tokens',
                'cache',
                'cache_locks',
                'jobs',
                'job_batches',
                'failed_jobs',
            ];

            $purgedTables = [];
            $skippedTables = [];

            foreach ($tablesToPurge as $table) {
                try {
                    DB::table($table)->truncate();
                    $purgedTables[] = $table;
                } catch (\Exception $e) {
                    $skippedTables[] = $table . ' (' . $e->getMessage() . ')';
                }
            }

            // Reset wallet balances on users table (keep user accounts intact)
            DB::table('users')->update([
                'wallet_balance' => 0,
                'withdrawable_balance' => 0,
            ]);

            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            // Log the purge action (audit_logs was truncated, so create a fresh entry)
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'System Purge',
                'description' => 'All transaction data purged. Tables cleared: ' . count($purgedTables) . '. Skipped: ' . count($skippedTables),
                'ip_address' => $request->ip()
            ]);

            $message = 'Transaction data purged successfully. ' . count($purgedTables) . ' tables cleared.';
            if (!empty($skippedTables)) {
                $message .= ' Skipped: ' . implode(', ', $skippedTables);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            return back()->with('error', 'Purge failed: ' . $e->getMessage());
        }
    }
}
