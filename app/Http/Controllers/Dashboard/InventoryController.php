<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InventoryVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InventoryController extends Controller
{
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

    public function create()
    {
        $last_voucher = InventoryVoucher::orderBy('id', 'desc')->first();
        $next_id = 1;

        if ($last_voucher) {
            $next_id = (int)$last_voucher->id + 1;
        }

        return view('dashboard.inventory.create', compact('next_id'));
    }

    public function duplicate($id)
    {
        $original = InventoryVoucher::findOrFail($id);
        $new = $original->replicate();

        // Generate new unique SKU ID
        $baseSku = $original->sku_id;
        // Remove trailing numbers if any to get the base
        $basePrefix = preg_replace('/-\d+$/', '', $baseSku);

        $count = 1;
        $newSku = $basePrefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        while (InventoryVoucher::where('sku_id', $newSku)->exists()) {
            $count++;
            $newSku = $basePrefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        $new->sku_id = $newSku;
        $new->quantity = 0; // Reset quantity for duplicate
        $new->status = 'OUT OF STOCK';
        $new->save();

        return back()->with('success', 'Voucher duplicated successfully with new SKU: ' . $newSku);
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
                $new->quantity = 0;
                $new->status = 'OUT OF STOCK';
                $new->save();
                $duplicatedCount++;
            }
        }

        return response()->json(['success' => true, 'message' => "$duplicatedCount vouchers duplicated successfully."]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku_id' => 'required|string|unique:inventory_vouchers,sku_id',
            'brand_name' => 'required|string|max:255',
            'country_region' => 'required|array',
            'state' => 'nullable|array',
            'city' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'voucher_variant' => 'nullable|string|max:255',
            'voucher_type' => 'required|string|max:255',
            'purchase_invoice_no' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:purchase_date',
            'quantity' => 'required|integer|min:0',
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
        ]);

       if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('inventory_logos', 'public');
        }

        if ($request->filled('upload_vouchers')) {
            $validated['upload_vouchers'] = array_map('trim', explode(',', $request->upload_vouchers));
            $count = count($validated['upload_vouchers']);
            $validated['opening_stock_qty'] = $count;
            $validated['purchased_qty'] = $count;
            $validated['quantity'] = $count;
        }

        if (isset($validated['expiry_date']) && $validated['expiry_date'] && \Carbon\Carbon::parse($validated['expiry_date'])->isPast()) {
            $validated['is_expired'] = true;
        } else {
            $validated['is_expired'] = false;
        }

        InventoryVoucher::create($validated);

        return redirect()->route('inventory.index')->with('success', 'Inventory added successfully.');
    }

    public function edit($id)
    {
        $inventory = InventoryVoucher::findOrFail($id);
        return view('dashboard.inventory.edit', compact('inventory'));
    }

    public function update(Request $request, $id)
    {
        $inventory = InventoryVoucher::findOrFail($id);
        
        $rules = [
            'sku_id' => 'required|string|unique:inventory_vouchers,sku_id,' . $id,
            'brand_name' => 'required|string|max:255',
            'country_region' => 'required|array',
            'state' => 'nullable|array',
            'city' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'voucher_variant' => 'nullable|string|max:255',
            'voucher_type' => 'required|string|max:255',
            'purchase_invoice_no' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:purchase_date',
            'quantity' => 'required|integer|min:0',
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
        ];

        $request->validate($rules);

        $validated = $request->except(['upload_vouchers', 'quantity', 'logo']);
        $validated['state'] = $request->input('state', []);

 if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($inventory->logo) {
                Storage::disk('public')->delete($inventory->logo);
            }
            $validated['logo'] = $request->file('logo')->store('inventory_logos', 'public');
        }

        if ($request->filled('upload_vouchers')) {
            $newCodesString = $request->upload_vouchers;
            // Support both comma and newline as separator for convenience
            $newCodes = array_map('trim', preg_split('/[, \n\r]+/', $newCodesString));
            $newCodes = array_filter($newCodes); // Remove empty values

            $existingCodes = $inventory->upload_vouchers ?: [];
            $allCodes = array_unique(array_merge($existingCodes, $newCodes));
            $validated['upload_vouchers'] = array_values($allCodes);

            // Quantity should be all codes minus delivered codes
            $deliveredCodes = $inventory->delivered_vouchers ?: [];
            $remainingCodes = array_diff($allCodes, $deliveredCodes);

            $pendingOrdersQty = \App\Models\Order::where('voucher_id', $inventory->sku_id)
                ->whereIn('status', ['pending', 'completed'])
                ->sum('quantity');

            $validated['quantity'] = max(0, count($remainingCodes) - $pendingOrdersQty);

            // Update purchased_qty: It's basically all unique codes ever added
            $validated['purchased_qty'] = count($allCodes);
        }

        if (isset($validated['expiry_date']) && $validated['expiry_date'] && \Carbon\Carbon::parse($validated['expiry_date'])->isPast()) {
            $validated['is_expired'] = true;
        } else {
            $validated['is_expired'] = false;
        }

        $inventory->update($validated);

        return redirect()->route('inventory.index')->with('success', 'Inventory updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $inventory = InventoryVoucher::findOrFail($id);
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
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
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
            'Purchase Value',
            'Purchase Value Per Unit',
            'Taxes',
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
                fputcsv($file, [
                    $record->id,
                    $record->sku_id,
                    $record->brand_name,
                    $record->country_region,
                    $record->state,
                    $record->city,
                    $record->currency,
                    $record->voucher_variant,
                    $record->voucher_type,
                    $record->purchase_invoice_no,
                    $record->purchase_date ? $record->purchase_date->format('Y-m-d') : '',
                    $record->expiry_date ? $record->expiry_date->format('Y-m-d') : '',
                    $record->is_expired ? 'Yes' : 'No',
                    $record->quantity,
                    $record->purchase_value,
                    $record->purchase_value_per_unit,
                    $record->taxes,
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
}
