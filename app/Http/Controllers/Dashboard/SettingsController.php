<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CountryRiskLevel;
use App\Models\ExchangeRate;
use App\Services\CurrencyService;
use App\Helpers\CountryHelper;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function riskLevels(Request $request)
    {
        // Get all countries from helper for the modal dropdown
        $allCountries = CountryHelper::getAllCountries();
        
        // Get query for risk levels
        $query = CountryRiskLevel::query();

        // Apply filters
        if ($request->filled('country_name')) {
            $query->where('country_name', 'like', '%' . $request->country_name . '%');
        }

        if ($request->filled('country_code')) {
            $query->where('country_code', 'like', '%' . $request->country_code . '%');
        }

        if ($request->filled('risk_level') && $request->risk_level != 'all') {
            $query->where('risk_level', $request->risk_level);
        }

        $riskLevels = $query->latest()->get();

        return view('dashboard.settings.risk-levels', compact('allCountries', 'riskLevels'));
    }

    public function updateRiskLevel(Request $request)
    {
        $validated = $request->validate([
            'country_code' => 'required|string',
            'country_name' => 'required|string',
            'risk_level'   => 'required|in:Low,Medium,High',
        ]);

        $risk = CountryRiskLevel::updateOrCreate(
            ['country_code' => $validated['country_code']],
            [
                'country_name' => $validated['country_name'],
                'risk_level'   => $validated['risk_level'],
            ]
        );

        return response()->json([
            'success' => true, 
            'message' => 'Risk level updated successfully.',
            'data' => $risk
        ]);
    }

    public function deleteRiskLevel($id)
    {
        CountryRiskLevel::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Record deleted.']);
    }

    public function exportRiskLevelsCsv(Request $request)
    {
        $query = CountryRiskLevel::query();

        if ($request->filled('country_name')) {
            $query->where('country_name', 'like', '%' . $request->country_name . '%');
        }

        if ($request->filled('country_code')) {
            $query->where('country_code', 'like', '%' . $request->country_code . '%');
        }

        if ($request->filled('risk_level') && $request->risk_level != 'all') {
            $query->where('risk_level', $request->risk_level);
        }

        $records = $query->latest()->get();

        $filename = "country_risk_levels_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0",
            "Expires"             => "0"
        ];

        $columns = ['Country Name', 'Country Code', 'Risk Level', 'Created At'];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $record) {
                fputcsv($file, [
                    $record->country_name,
                    $record->country_code,
                    $record->risk_level,
                    $record->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exchangeRates(Request $request)
    {
        $baseCurrency = CurrencyService::getBaseCurrency();
        $rates = ExchangeRate::orderBy('from_currency')->get();

        return view('dashboard.settings.exchange-rates', compact('baseCurrency', 'rates'));
    }

    public function updateExchangeRate(Request $request)
    {
        $validated = $request->validate([
            'from_currency' => 'required|string|max:10',
            'to_currency'   => 'nullable|string|max:10',
            'rate'          => 'required|numeric|min:0.000001',
            'is_active'     => 'nullable',
        ]);

        $fromCurrency = CurrencyService::normalizeCurrencyCode($validated['from_currency']);
        $toCurrency   = CurrencyService::getBaseCurrency();
        $isActive     = $request->has('is_active') ? (bool)$request->is_active : true;

        $rateRecord = ExchangeRate::updateOrCreate(
            [
                'from_currency' => $fromCurrency,
                'to_currency'   => $toCurrency,
            ],
            [
                'rate'      => (float)$validated['rate'],
                'is_active' => $isActive,
                'effective_from' => now(),
                'approved_by' => auth()->id(),
            ]
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Exchange rate for {$fromCurrency} updated successfully.",
                'data'    => $rateRecord,
            ]);
        }

        return redirect()->back()->with('success', "Exchange rate for {$fromCurrency} updated successfully.");
    }

    public function deleteExchangeRate($id)
    {
        $rate = ExchangeRate::findOrFail($id);
        if ($rate->from_currency === 'USD') {
            return response()->json(['success' => false, 'message' => 'Base currency rate cannot be deleted.'], 400);
        }
        $rate->delete();

        return response()->json(['success' => true, 'message' => 'Exchange rate deleted.']);
    }
}

