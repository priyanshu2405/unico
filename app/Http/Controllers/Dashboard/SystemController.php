<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\VoucherPriceRule;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function controlIndex()
    {
        $settings = SystemSetting::all()->pluck('settings_value', 'settings_key');
        $logs = AuditLog::where('action', 'like', 'System %')->latest()->limit(10)->get();
        
        // Get unique countries from users table
        $countries = User::whereNotNull('country_iso')
            ->where('country_iso', '!=', '')
            ->select('country_iso', 'country')
            ->distinct()
            ->get();

        // Get vouchers from VoucherPriceRule
        $vouchers = VoucherPriceRule::select('id', 'brand_name', 'voucher_variant', 'country_name')->get();

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
}
