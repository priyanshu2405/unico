<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InventoryVoucher;
use App\Services\CurrencyService;
use App\Helpers\CurrencyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InventoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                $user = auth()->user();
                if (!$user->isAdmin() && !$user->can_view_voucher_stock) {
                    abort(403, 'Unauthorized action.');
                }
                
                $routeAction = $request->route()->getActionMethod();
                $writeActions = ['create', 'store', 'edit', 'update', 'destroy', 'duplicate', 'bulkDuplicate', 'import', 'upload'];
                if (in_array($routeAction, $writeActions) && !$user->isAdmin() && !$user->can_edit_voucher_stock) {
                    abort(403, 'Unauthorized action.');
                }
                
                return $next($request);
            }),
        ];
    }

    public function index(Request $request)
    {
        $query = InventoryVoucher::query();

        // Basic stats
        $stats = [
            'total_stock' => InventoryVoucher::sum('quantity'),
            'total_valuation' => InventoryVoucher::sum('purchase_value'),
            'active_brands' => InventoryVoucher::distinct('brand_name')->count('brand_name'),
        ];

        // Filters
        if ($request->filled('sku_id')) {
            $query->where('sku_id', 'like', '%' . $request->sku_id . '%');
        }
        if ($request->filled('countries')) {
            $countries = is_array($request->countries) ? $request->countries : [$request->countries];
            $query->where(function ($q) use ($countries) {
                foreach ($countries as $country) {
                    $q->orWhereJsonContains('country_region', $country);
                }
                $q->orWhereJsonContains('country_region', 'all');
            });
        }
        if ($request->filled('states')) {
            $states = is_array($request->states) ? $request->states : [$request->states];
            $query->where(function ($q) use ($states) {
                foreach ($states as $state) {
                    $q->orWhereJsonContains('state', $state);
                }
                $q->orWhereJsonContains('state', 'all');
            });
        }
        if ($request->filled('brands')) {
            $brands = is_array($request->brands) ? $request->brands : [$request->brands];
            $query->whereIn('brand_name', $brands);
        }
        if ($request->filled('types')) {
            $types = is_array($request->types) ? $request->types : [$request->types];
            $query->whereIn('voucher_type', $types);
        }
        if ($request->filled('variants')) {
            $variants = is_array($request->variants) ? $request->variants : [$request->variants];
            $query->whereIn('voucher_variant', $variants);
        }
        if ($request->filled('status')) {
            $status = is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('status', $status);
        }
        if ($request->filled('expiry_date')) {
            $query->whereDate('expiry_date', $request->expiry_date);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        if ($sort == 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $perPage = $request->get('per_page', 15);
        $inventory = $query->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.inventory.partials.voucher-list', compact('inventory'))->render();
        }

        // Dynamic filter options from current inventory
        $countries = InventoryVoucher::whereNotNull('country_region')->get()->pluck('country_region')->flatten()->unique()->sort();
        $states = InventoryVoucher::whereNotNull('state')->get()->pluck('state')->flatten()->unique()->sort();
        $brands = InventoryVoucher::whereNotNull('brand_name')->distinct()->pluck('brand_name')->sort();
        $voucherTypes = InventoryVoucher::whereNotNull('voucher_type')->distinct()->pluck('voucher_type')->sort();
        $voucherVariants = InventoryVoucher::whereNotNull('voucher_variant')->distinct()->pluck('voucher_variant')->sort();

        return view('dashboard.inventory.index', compact('inventory', 'stats', 'countries', 'states', 'brands', 'voucherTypes', 'voucherVariants'));
    }

    public function create(Request $request)
    {
        $last_voucher = InventoryVoucher::orderBy('id', 'desc')->first();
        $next_id = 1;

        if ($last_voucher) {
            $next_id = (int)$last_voucher->id + 1;
        }

        $duplicateFrom = null;
        $suggestedSku = null;
        if ($request->filled('duplicate_from')) {
            $duplicateFrom = InventoryVoucher::find($request->duplicate_from);
            if ($duplicateFrom) {
                // Generate next unique SKU based on the original SKU
                $baseSku = $duplicateFrom->sku_id;
                $basePrefix = preg_replace('/-\d+$/', '', $baseSku);
                $count = 1;
                $newSku = $basePrefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
                while (InventoryVoucher::where('sku_id', $newSku)->exists()) {
                    $count++;
                    $newSku = $basePrefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
                }
                $suggestedSku = $newSku;
            }
        }

        $currencies = CurrencyHelper::getAllCurrencies();
        $baseCurrency = CurrencyService::getBaseCurrency();
        $exchangeRates = CurrencyService::getAllActiveRates();
        $existingCodesMap = $this->getAllVoucherCodesMap();

        return view('dashboard.inventory.create', compact('next_id', 'currencies', 'baseCurrency', 'exchangeRates', 'existingCodesMap', 'duplicateFrom', 'suggestedSku'));
    }

    public function duplicate($id)
    {
        $original = InventoryVoucher::findOrFail($id);

        return redirect()->route('inventory.create', ['duplicate_from' => $original->id]);
    }

    public function discardDraft($id)
    {
        $voucher = InventoryVoucher::findOrFail($id);
        $ordersCount = \App\Models\Order::where('voucher_id', $voucher->sku_id)->count();
        $allCodes = is_array($voucher->upload_vouchers) ? $voucher->upload_vouchers : [];

        // Only delete if it has 0 orders and 0 uploaded codes
        if ($ordersCount === 0 && count($allCodes) === 0) {
            $sku = $voucher->sku_id;
            $voucher->delete();
            return redirect()->route('inventory.index')->with('info', "Unstocked voucher {$sku} was discarded.");
        }

        return redirect()->route('inventory.index');
    }

    public function bulkDuplicate(Request $request)
    {
        $ids = $request->input('ids');
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No vouchers selected.'], 400);
        }

        $duplicatedCount = 0;
        foreach ($ids as $id) {
            $original = InventoryVoucher::find($id);
            if ($original) {
                $new = $original->replicate();

                $baseSku = $original->sku_id;
                $basePrefix = preg_replace('/-\d+$/', '', $baseSku);

                $count = 1;
                $newSku = $basePrefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

                while (InventoryVoucher::where('sku_id', $newSku)->exists()) {
                    $count++;
                    $newSku = $basePrefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
                }

                $new->sku_id = $newSku;
                $new->upload_vouchers = null;
                $new->delivered_vouchers = null;
                $new->opening_stock_qty = 0;
                $new->purchased_qty = 0;
                $new->quantity = 0;
                $new->status = 'OUT OF STOCK';
                $new->save();
                $duplicatedCount++;
            }
        }

        return response()->json(['success' => true, 'message' => "$duplicatedCount vouchers duplicated successfully. Please add voucher codes before updating stock."]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku_id' => 'required|string|unique:inventory_vouchers,sku_id',
            'brand_name' => 'required|string|max:255',
            'country_region' => 'required|array',
            'state' => 'nullable|array',
            'city' => 'nullable|array',
            'currency' => 'required|string|max:10',
            'voucher_variant' => 'nullable|string|max:255',
            'voucher_type' => 'required|string|max:255',
            'purchase_invoice_no' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:purchase_date',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|string|in:IN STOCK,OUT OF STOCK',
            'purchase_value' => 'required|numeric|min:0',
            'purchase_value_per_unit' => 'required|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'bank' => 'nullable|string|max:255',
            'local_currency' => 'nullable|string|max:10',
            'currency_conversion_rate' => 'nullable|numeric|min:0',
            'agent_sale_price' => 'required|numeric|min:0',
            'student_sale_price' => 'required|numeric|min:0',
            'referral_points_reseller' => 'nullable|integer|min:0',
            'agent_referral_points_per_unit' => 'nullable|integer|min:0',
            'agent_bonus_points_per_unit' => 'nullable|integer|min:0',
            'student_referral_points_per_unit' => 'nullable|integer|min:0',
            'student_bonus_points_per_unit' => 'nullable|integer|min:0',
            'upload_vouchers' => 'required|string',
            'logo' => 'nullable|image|max:2048',
            'instructions' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'mobile_no' => 'nullable|string|max:50',
        ], [
            'quantity.min' => 'A voucher cannot be created with 0 quantity. Please upload at least 1 voucher code.',
            'upload_vouchers.required' => 'Please upload at least 1 voucher code. A voucher cannot be created with 0 quantity.',
        ]);

       if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('inventory_logos', 'public');
        }

        $rawCodes = array_map('trim', preg_split('/[, \n\r\t]+/', $request->upload_vouchers));
        $rawCodes = array_values(array_filter($rawCodes, fn($c) => $c !== ''));

        if (empty($rawCodes)) {
            return back()->withInput()->withErrors([
                'upload_vouchers' => 'A voucher cannot be created with 0 quantity. Please upload at least 1 voucher code.',
                'quantity' => 'Quantity must be at least 1.'
            ]);
        }

            // 1. Check for duplicate codes in the submitted batch
            $upperCounts = array_count_values(array_map('strtoupper', $rawCodes));
            $duplicatesInInput = array_keys(array_filter($upperCounts, fn($count) => $count > 1));
            if (!empty($duplicatesInInput)) {
                $sample = implode(', ', array_slice($duplicatesInInput, 0, 5));
                $more = count($duplicatesInInput) > 5 ? ' and ' . (count($duplicatesInInput) - 5) . ' more' : '';
                return back()->withInput()->withErrors([
                    'upload_vouchers' => "Duplicate voucher code(s) found in your input: [{$sample}{$more}]. Each voucher code must be unique."
                ]);
            }

            // 2. Check if any code already exists in ANY existing inventory voucher
            $conflict = $this->findCodeConflictAcrossVouchers($rawCodes);
            if ($conflict) {
                return back()->withInput()->withErrors([
                    'upload_vouchers' => "Voucher code '{$conflict['code']}' is already uploaded in voucher SKU '{$conflict['sku_id']}'."
                ]);
            }

            $validated['upload_vouchers'] = $rawCodes;
            $count = count($rawCodes);
            $validated['opening_stock_qty'] = $count;
            $validated['purchased_qty'] = $count;
            $validated['quantity'] = $count;

        if (isset($validated['expiry_date']) && $validated['expiry_date'] && \Carbon\Carbon::parse($validated['expiry_date'])->isPast()) {
            $validated['is_expired'] = true;
        } else {
            $validated['is_expired'] = false;
        }

        // Validate that both Purchase and Sale currencies are configured by Admin in Currency Rates
        $purchaseCurrency = strtoupper(trim($validated['local_currency'] ?? ($validated['currency'] ?? 'USD')));
        $saleCurrency = strtoupper(trim($validated['currency'] ?? 'PKR'));
        $baseCurrency = CurrencyService::getBaseCurrency();
        $validated['local_currency'] = $purchaseCurrency;

        if (!CurrencyService::isCurrencyConfigured($purchaseCurrency)) {
            return back()->withInput()->withErrors([
                'local_currency' => "First set currency '{$purchaseCurrency}' in Currency Rates."
            ]);
        }

        if (!CurrencyService::isCurrencyConfigured($saleCurrency)) {
            return back()->withInput()->withErrors([
                'currency' => "First set currency '{$saleCurrency}' in Currency Rates."
            ]);
        }

        // Auto-resolve conversion rate to base currency from Admin active exchange rates
        if (empty($validated['currency_conversion_rate']) || (float)$validated['currency_conversion_rate'] <= 0) {
            $validated['currency_conversion_rate'] = CurrencyService::getExchangeRate($purchaseCurrency, $baseCurrency);
        }

        $rate = (float)$validated['currency_conversion_rate'];
        $validated['purchase_value_usd'] = (float)($validated['purchase_value'] ?? 0) * $rate;
        $validated['purchase_value_per_unit_usd'] = (float)($validated['purchase_value_per_unit'] ?? 0) * $rate;
        $validated['taxes_usd'] = (float)($validated['taxes'] ?? 0) * $rate;
        $validated['rate_approved_by'] = auth()->id();

        InventoryVoucher::create($validated);

        return redirect()->route('inventory.index')->with('success', 'Inventory added successfully.');
    }

    public function edit($id)
    {
        $inventory = InventoryVoucher::findOrFail($id);
        $ordersCount = \App\Models\Order::where('voucher_id', $inventory->sku_id)->count();
        $allCodes = is_array($inventory->upload_vouchers) ? $inventory->upload_vouchers : [];
        $deliveredCodes = is_array($inventory->delivered_vouchers) ? $inventory->delivered_vouchers : [];
        $remainingCodes = array_diff($allCodes, $deliveredCodes);
        $availableCount = count($remainingCodes);
        $isDraftEmpty = ($availableCount === 0 && count($allCodes) === 0 && $ordersCount === 0);

        $currencies = CurrencyHelper::getAllCurrencies();
        $baseCurrency = CurrencyService::getBaseCurrency();
        $exchangeRates = CurrencyService::getAllActiveRates();
        $existingCodesMap = $this->getAllVoucherCodesMap($id);

        return view('dashboard.inventory.edit', compact(
            'inventory',
            'ordersCount',
            'currencies',
            'baseCurrency',
            'exchangeRates',
            'existingCodesMap',
            'allCodes',
            'deliveredCodes',
            'remainingCodes',
            'availableCount',
            'isDraftEmpty'
        ));
    }

    public function update(Request $request, $id)
    {
        $inventory = InventoryVoucher::findOrFail($id);
        $ordersCount = \App\Models\Order::where('voucher_id', $inventory->sku_id)->count();

        // Strict accounting protection: Do NOT allow modifying SKU or Currencies if orders exist
        if ($ordersCount > 0) {
            if ($request->filled('sku_id') && trim($request->sku_id) !== $inventory->sku_id) {
                return back()->withInput()->withErrors([
                    'sku_id' => "Cannot change SKU ID '{$inventory->sku_id}' because {$ordersCount} order(s) have already been placed for this voucher."
                ]);
            }
            if ($request->filled('currency') && strtoupper(trim($request->currency)) !== strtoupper(trim($inventory->currency))) {
                return back()->withInput()->withErrors([
                    'currency' => "Cannot change Sale Currency from '{$inventory->currency}' because {$ordersCount} order(s) have already been placed in this currency."
                ]);
            }
            $reqPurch = strtoupper(trim($request->input('local_currency', '')));
            $curPurch = strtoupper(trim($inventory->local_currency ?? ''));
            if (!empty($reqPurch) && !empty($curPurch) && $reqPurch !== $curPurch) {
                return back()->withInput()->withErrors([
                    'local_currency' => "Cannot change Purchase Currency from '{$inventory->local_currency}' because {$ordersCount} order(s) rely on this purchase cost basis."
                ]);
            }

            if ($request->filled('upload_vouchers')) {
                return back()->withInput()->withErrors([
                    'upload_vouchers' => 'Create a new SKU for additional stock after sales have been recorded, so each purchase retains its own cost basis.'
                ]);
            }

            foreach (['purchase_value', 'purchase_value_per_unit', 'taxes', 'currency_conversion_rate', 'purchase_date', 'purchase_invoice_no'] as $field) {
                if (!$request->exists($field)) {
                    continue;
                }

                $currentValue = $inventory->{$field};
                $requestedValue = $request->input($field);
                if ($field === 'purchase_date') {
                    $currentValue = $currentValue ? \Carbon\Carbon::parse($currentValue)->toDateString() : '';
                    $requestedValue = $requestedValue ? \Carbon\Carbon::parse($requestedValue)->toDateString() : '';
                }

                $unchanged = in_array($field, ['purchase_value', 'purchase_value_per_unit', 'taxes', 'currency_conversion_rate'], true)
                    ? (float) ($requestedValue ?? 0) === (float) ($currentValue ?? 0)
                    : (string) ($requestedValue ?? '') === (string) ($currentValue ?? '');

                if (!$unchanged) {
                    return back()->withInput()->withErrors([
                        $field => "Cannot change purchase cost details for '{$inventory->sku_id}' because {$ordersCount} order(s) already use this cost basis. Create a new SKU for new purchase batches."
                    ]);
                }
            }
        }
        
        $isCopiedOrNew = ($inventory->purchased_qty == 0 || empty($inventory->upload_vouchers));
        $qtyRule = ($isCopiedOrNew || $ordersCount == 0) ? 'required|integer|min:1' : 'required|integer|min:0';

        $rules = [
            'sku_id' => 'required|string|unique:inventory_vouchers,sku_id,' . $id,
            'brand_name' => 'required|string|max:255',
            'country_region' => 'required|array',
            'state' => 'nullable|array',
            'city' => 'nullable|array',
            'currency' => 'required|string|max:10',
            'voucher_variant' => 'nullable|string|max:255',
            'voucher_type' => 'required|string|max:255',
            'purchase_invoice_no' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:purchase_date',
            'quantity' => $qtyRule,
            'status' => 'required|string|in:IN STOCK,OUT OF STOCK',
            'purchase_value' => 'required|numeric|min:0',
            'purchase_value_per_unit' => 'required|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'bank' => 'nullable|string|max:255',
            'local_currency' => 'nullable|string|max:10',
            'currency_conversion_rate' => 'nullable|numeric|min:0',
            'agent_sale_price' => 'required|numeric|min:0',
            'student_sale_price' => 'required|numeric|min:0',
            'referral_points_reseller' => 'nullable|integer|min:0',
            'agent_referral_points_per_unit' => 'nullable|integer|min:0',
            'agent_bonus_points_per_unit' => 'nullable|integer|min:0',
            'student_referral_points_per_unit' => 'nullable|integer|min:0',
            'student_bonus_points_per_unit' => 'nullable|integer|min:0',
            'upload_vouchers' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'instructions' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'mobile_no' => 'nullable|string|max:50',
        ];

        $request->validate($rules, [
            'quantity.min' => 'A voucher cannot be created or updated with 0 quantity. Please upload at least 1 voucher code.',
        ]);

        $validated = $request->except(['upload_vouchers', 'quantity', 'logo']);
        $validated['state'] = $request->input('state', []);
        $validated['city'] = $request->input('city', []);

 if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($inventory->logo) {
                Storage::disk('public')->delete($inventory->logo);
            }
            $validated['logo'] = $request->file('logo')->store('inventory_logos', 'public');
        }

        $allCodes = $inventory->upload_vouchers ?: [];
        if ($request->filled('upload_vouchers')) {
            $newCodesString = $request->upload_vouchers;
            // Support comma, space, tab, and newline as separators
            $newCodes = array_map('trim', preg_split('/[, \n\r\t]+/', $newCodesString));
            $newCodes = array_values(array_filter($newCodes, fn($c) => $c !== ''));

            // 1. Check for duplicates within the submitted input
            $upperCounts = array_count_values(array_map('strtoupper', $newCodes));
            $duplicatesInInput = array_keys(array_filter($upperCounts, fn($count) => $count > 1));
            if (!empty($duplicatesInInput)) {
                $sample = implode(', ', array_slice($duplicatesInInput, 0, 5));
                $more = count($duplicatesInInput) > 5 ? ' and ' . (count($duplicatesInInput) - 5) . ' more' : '';
                return back()->withInput()->withErrors([
                    'upload_vouchers' => "Duplicate voucher code(s) found in your input: [{$sample}{$more}]. Each voucher code must be unique."
                ]);
            }

            // 2. Check if any submitted code already exists in THIS voucher
            $existingUpper = array_map('strtoupper', $inventory->upload_vouchers ?: []);
            $alreadyInThisVoucher = [];
            foreach ($newCodes as $nc) {
                if (in_array(strtoupper($nc), $existingUpper)) {
                    $alreadyInThisVoucher[] = $nc;
                }
            }
            if (!empty($alreadyInThisVoucher)) {
                $sample = implode(', ', array_slice($alreadyInThisVoucher, 0, 5));
                $more = count($alreadyInThisVoucher) > 5 ? ' and ' . (count($alreadyInThisVoucher) - 5) . ' more' : '';
                return back()->withInput()->withErrors([
                    'upload_vouchers' => "Code(s) [{$sample}{$more}] are already uploaded in this voucher."
                ]);
            }

            // 3. Check if any submitted code exists in ANY OTHER voucher in the database
            $conflict = $this->findCodeConflictAcrossVouchers($newCodes, $inventory->id);
            if ($conflict) {
                return back()->withInput()->withErrors([
                    'upload_vouchers' => "Voucher code '{$conflict['code']}' is already uploaded in another voucher (SKU '{$conflict['sku_id']}')."
                ]);
            }

            $allCodes = array_values(array_merge($inventory->upload_vouchers ?: [], $newCodes));
            $validated['upload_vouchers'] = $allCodes;

            // Update purchased_qty: all unique codes ever added
            $validated['purchased_qty'] = count($allCodes);
        }

        // Always update the quantity column to match remaining codes minus pending orders
        $deliveredCodes = $inventory->delivered_vouchers ?: [];
        $remainingCodes = array_diff($allCodes, $deliveredCodes);

        $pendingOrdersQty = \App\Models\Order::where('voucher_id', $inventory->sku_id)
            ->whereIn('status', ['pending', 'completed'])
            ->sum('quantity');

        $validated['quantity'] = max(0, count($remainingCodes) - $pendingOrdersQty);

        // Strict 0-quantity protection for creation / copied vouchers / vouchers without orders
        if (($isCopiedOrNew || $ordersCount == 0) && ($validated['quantity'] <= 0 || empty($allCodes))) {
            return back()->withInput()->withErrors([
                'upload_vouchers' => 'A voucher cannot be created or updated with 0 quantity. Please upload at least 1 valid voucher code before updating stock.',
                'quantity' => 'Quantity must be at least 1.'
            ]);
        }

        if (isset($validated['expiry_date']) && $validated['expiry_date'] && \Carbon\Carbon::parse($validated['expiry_date'])->isPast()) {
            $validated['is_expired'] = true;
        } else {
            $validated['is_expired'] = false;
        }

        // Validate that both Purchase and Sale currencies are configured by Admin in Currency Rates
        $purchaseCurrency = strtoupper(trim($validated['local_currency'] ?? ($inventory->local_currency ?? ($validated['currency'] ?? ($inventory->currency ?? 'USD')))));
        $saleCurrency = strtoupper(trim($validated['currency'] ?? ($inventory->currency ?? 'PKR')));
        $baseCurrency = CurrencyService::getBaseCurrency();
        $validated['local_currency'] = $purchaseCurrency;

        if (!CurrencyService::isCurrencyConfigured($purchaseCurrency)) {
            return back()->withInput()->withErrors([
                'local_currency' => "First set currency '{$purchaseCurrency}' in Currency Rates."
            ]);
        }

        if (!CurrencyService::isCurrencyConfigured($saleCurrency)) {
            return back()->withInput()->withErrors([
                'currency' => "First set currency '{$saleCurrency}' in Currency Rates."
            ]);
        }

        // Auto-resolve conversion rate to base currency from Admin active exchange rates
        if ($ordersCount > 0) {
            $validated['currency_conversion_rate'] = $inventory->currency_conversion_rate;
            $validated['purchase_value_usd'] = $inventory->purchase_value_usd;
            $validated['purchase_value_per_unit_usd'] = $inventory->purchase_value_per_unit_usd;
            $validated['taxes_usd'] = $inventory->taxes_usd;
            $validated['rate_approved_by'] = $inventory->rate_approved_by;
        } else {
            if (empty($validated['currency_conversion_rate']) || (float)$validated['currency_conversion_rate'] <= 0) {
                $validated['currency_conversion_rate'] = CurrencyService::getExchangeRate($purchaseCurrency, $baseCurrency);
            }

            $rate = (float)$validated['currency_conversion_rate'];
            $validated['purchase_value_usd'] = (float)($validated['purchase_value'] ?? $inventory->purchase_value ?? 0) * $rate;
            $validated['purchase_value_per_unit_usd'] = (float)($validated['purchase_value_per_unit'] ?? $inventory->purchase_value_per_unit ?? 0) * $rate;
            $validated['taxes_usd'] = (float)($validated['taxes'] ?? $inventory->taxes ?? 0) * $rate;
            $validated['rate_approved_by'] = auth()->id();
        }

        $oldSku = $inventory->sku_id;
        $inventory->update($validated);

        // Cascade SKU rename to existing orders to prevent orphaned records in Stock Report
        if (!empty($validated['sku_id']) && $oldSku !== $validated['sku_id']) {
            \App\Models\Order::where('voucher_id', $oldSku)->update(['voucher_id' => $validated['sku_id']]);
        }

        return redirect()->route('inventory.index')->with('success', 'Inventory updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $inventory = InventoryVoucher::findOrFail($id);

        // Prevent deleting inventory items that already have orders attached
        $ordersCount = \App\Models\Order::where('voucher_id', $inventory->sku_id)->count();
        if ($ordersCount > 0) {
            $msg = "Cannot delete inventory voucher '{$inventory->sku_id}' because {$ordersCount} order(s) are associated with it.";
            if ($request->ajax()) {
                return response()->json(['error' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        if ($inventory->logo) {
            Storage::disk('public')->delete($inventory->logo);
        }
        $inventory->delete();

        if ($request->ajax()) {
            return response()->json(['success' => 'Inventory deleted successfully.']);
        }

        return redirect()->route('inventory.index')->with('success', 'Inventory deleted successfully.');
    }

    public function export(Request $request)
    {
        $query = InventoryVoucher::query();

        // Apply filters
        if ($request->filled('sku_id')) {
            $query->where('sku_id', 'like', '%' . $request->sku_id . '%');
        }
        if ($request->filled('countries')) {
            $countries = is_array($request->countries) ? $request->countries : [$request->countries];
            $query->where(function ($q) use ($countries) {
                foreach ($countries as $country) {
                    $q->orWhereJsonContains('country_region', $country);
                }
                $q->orWhereJsonContains('country_region', 'all');
            });
        }
        if ($request->filled('states')) {
            $states = is_array($request->states) ? $request->states : [$request->states];
            $query->where(function ($q) use ($states) {
                foreach ($states as $state) {
                    $q->orWhereJsonContains('state', $state);
                }
                $q->orWhereJsonContains('state', 'all');
            });
        }
        if ($request->filled('brands')) {
            $brands = is_array($request->brands) ? $request->brands : [$request->brands];
            $query->whereIn('brand_name', $brands);
        }
        if ($request->filled('types')) {
            $types = is_array($request->types) ? $request->types : [$request->types];
            $query->whereIn('voucher_type', $types);
        }
        if ($request->filled('variants')) {
            $variants = is_array($request->variants) ? $request->variants : [$request->variants];
            $query->whereIn('voucher_variant', $variants);
        }
        if ($request->filled('status')) {
            $status = is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('status', $status);
        }
        if ($request->filled('expiry_date')) {
            $query->whereDate('expiry_date', $request->expiry_date);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }

        $records = $query->latest()->get();

        $filename = "inventory_report_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0",
            "Expires"             => "0"
        ];

        // All columns from inventory_vouchers
        $columns = [
            'ID',
            'SKU ID',
            'Brand Name',
            'Country/Region',
            'State',
            'City',
            'Currency',
            'Voucher Variant',
            'Voucher Type',
            'Purchase Invoice No.',
            'Purchase Date',
            'Expiry Date',
            'Is Expired',
            'Quantity',
            'Purchase Value (Local)',
            'Purchase Value Per Unit (Local)',
            'Taxes',
            'Exchange Rate (to USD)',
            'Purchase Value (USD)',
            'Purchase Value Per Unit (USD)',
            'Local Currency',
            'Bank',
            'Currency Conversion Rate',
            'Referral Points Reseller',
            'Agent Referral Points/Unit',
            'Agent Bonus Points/Unit',
            'Agent Sale Price',
            'Student Referral Points/Unit',
            'Student Bonus Points/Unit',
            'Student Sale Price',
            'Opening Stock Qty',
            'Purchased Qty',
            'Status',
            'Created At'
        ];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $record) {
                $curr = $record->currency ?: 'USD';
                // Use pre-calculated locked USD columns
                $rate = (float)($record->currency_conversion_rate ?? 0) ?: (float) CurrencyService::getExchangeRate($curr, 'USD');
                $valUsd = (float)($record->purchase_value_usd ?? ((float)$record->purchase_value * $rate));
                $unitUsd = (float)($record->purchase_value_per_unit_usd ?? ((float)$record->purchase_value_per_unit * $rate));

                fputcsv($file, [
                    $record->id,
                    $record->sku_id,
                    $record->brand_name,
                    is_array($record->country_region) ? implode(', ', $record->country_region) : ($record->country_region ?: 'Global'),
                    is_array($record->state) ? implode(', ', $record->state) : ($record->state ?: 'All States'),
                    is_array($record->city) ? implode(', ', $record->city) : ($record->city ?: 'All Cities'),
                    $curr,
                    $record->voucher_variant,
                    $record->voucher_type,
                    $record->purchase_invoice_no,
                    $record->purchase_date ? $record->purchase_date->format('Y-m-d') : '',
                    $record->expiry_date ? $record->expiry_date->format('Y-m-d') : '',
                    $record->is_expired ? 'Yes' : 'No',
                    $record->quantity,
                    number_format((float)$record->purchase_value, 2, '.', ''),
                    number_format((float)$record->purchase_value_per_unit, 2, '.', ''),
                    number_format((float)$record->taxes, 2, '.', ''),
                    number_format($rate, 6, '.', ''),
                    number_format($valUsd, 2, '.', ''),
                    number_format($unitUsd, 2, '.', ''),
                    $record->local_currency,
                    $record->bank,
                    $record->currency_conversion_rate,
                    $record->referral_points_reseller,
                    $record->agent_referral_points_per_unit,
                    $record->agent_bonus_points_per_unit,
                    $record->agent_sale_price,
                    $record->student_referral_points_per_unit,
                    $record->student_bonus_points_per_unit,
                    $record->student_sale_price,
                    $record->opening_stock_qty,
                    $record->purchased_qty,
                    $record->status,
                    $record->created_at->format('Y-m-d H:i:s')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        return back()->with('success', 'Import functionality coming soon.');
    }

    public function upload(Request $request)
    {
        return back()->with('success', 'Upload functionality coming soon.');
    }

    /**
     * Get an associative array of all uploaded codes in uppercase => SKU ID.
     *
     * @param int|null $ignoreId
     * @return array
     */
    protected function getAllVoucherCodesMap($ignoreId = null): array
    {
        $query = InventoryVoucher::whereNotNull('upload_vouchers');
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $vouchers = $query->get(['id', 'sku_id', 'upload_vouchers']);
        $map = [];
        foreach ($vouchers as $v) {
            $codes = is_array($v->upload_vouchers) ? $v->upload_vouchers : [];
            foreach ($codes as $code) {
                $u = strtoupper(trim($code));
                if ($u !== '') {
                    $map[$u] = $v->sku_id;
                }
            }
        }
        return $map;
    }

    /**
     * Check if any code exists in any other inventory voucher in the database.
     *
     * @param array $codes
     * @param int|null $ignoreId
     * @return array|null ['code' => string, 'sku_id' => string] or null
     */
    protected function findCodeConflictAcrossVouchers(array $codes, $ignoreId = null): ?array
    {
        if (empty($codes)) {
            return null;
        }

        $existingMap = $this->getAllVoucherCodesMap($ignoreId);

        foreach ($codes as $c) {
            $u = strtoupper(trim($c));
            if ($u !== '' && isset($existingMap[$u])) {
                return [
                    'code' => $c,
                    'sku_id' => $existingMap[$u],
                ];
            }
        }

        return null;
    }
}
