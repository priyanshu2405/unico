@extends('layouts.master')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            @if(isset($duplicateFrom) && $duplicateFrom)
                <h2 class="fw-bold mb-1">Duplicate Voucher (Draft)</h2>
                <p class="text-muted mb-0">Duplicating settings from <strong>{{ $duplicateFrom->brand_name }} ({{ $duplicateFrom->sku_id }})</strong>. Upload codes to create stock.</p>
            @else
                <h2 class="fw-bold mb-1">Add New Voucher Stock</h2>
                <p class="text-muted mb-0">Fill in the details to add a new voucher to the inventory.</p>
            @endif
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-light rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i> Back to Inventory
        </a>
    </div>

    @if(isset($duplicateFrom) && $duplicateFrom)
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4" style="border-radius: 12px; background: #cff4fc; color: #055160;">
        <i class="fas fa-copy me-3 fs-4 text-info"></i>
        <div>
            <strong class="d-block mb-1"><i class="fas fa-info-circle me-1"></i> Duplicating Voucher: {{ $duplicateFrom->sku_id }}</strong>
            All settings, pricing, and point rules have been pre-filled. New SKU: <strong>{{ $suggestedSku }}</strong>. 
            Nothing has been saved to the database yet. Simply enter new voucher codes below and click <strong>Save Voucher Stock</strong>, or click <strong>Cancel</strong> to discard.
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius: 20px;">
        <div class="card-body p-4">
            <form action="{{ route('inventory.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                    <!-- Basic Info -->
                    <div class="col-12">
                        <h5 class="fw-bold mb-3 text-primary">Basic Information</h5>
                        <hr>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">SKU ID</label>
                        <input type="text" name="sku_id" id="sku_id" class="form-control @error('sku_id') is-invalid @enderror" value="{{ old('sku_id', $suggestedSku ?? '') }}" readonly required>
                        @error('sku_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Brand Name</label>
                        <input type="text" name="brand_name" id="brand_name" class="form-control" value="{{ old('brand_name', $duplicateFrom->brand_name ?? '') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Country/Region</label>
                        <select name="country_region[]" id="country" class="form-select select2" multiple required>
                            <option value="all" {{ (is_array(old('country_region', $duplicateFrom->country_region ?? null)) && in_array('all', old('country_region', $duplicateFrom->country_region ?? []))) ? 'selected' : '' }}>All Countries (GLB)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">State</label>
                        <select name="state[]" id="state" class="form-select select2-state" multiple required>
                            <option value="all" {{ (is_array(old('state', $duplicateFrom->state ?? null)) && in_array('all', old('state', $duplicateFrom->state ?? []))) ? 'selected' : '' }}>All States</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">City</label>
                        <select name="city[]" id="city" class="form-select select2-city" multiple>
                            <option value="all" {{ (is_array(old('city', $duplicateFrom->city ?? null)) && in_array('all', old('city', $duplicateFrom->city ?? []))) ? 'selected' : '' }}>All Cities</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Voucher Variant</label>
                        <input type="text" name="voucher_variant" id="voucher_variant" class="form-control" value="{{ old('voucher_variant', $duplicateFrom->voucher_variant ?? '') }}" placeholder="e.g. Subscription">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Voucher Type</label>
                        <input name="voucher_type" class="form-control" value="{{ old('voucher_type', $duplicateFrom->voucher_type ?? '') }}" required>
                    </div>
                    <div class=" col-md-6">
                        <label class="form-label small fw-bold text-uppercase">Logo Image</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>

                    <!-- Purchase Details -->
                    <div class="col-12 mt-5">
                        <h5 class="fw-bold mb-3 text-primary">Purchase & Stock Details</h5>
                        <hr>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Purchase Invoice No.</label>
                        <input type="text" name="purchase_invoice_no" class="form-control" value="{{ old('purchase_invoice_no', $duplicateFrom->purchase_invoice_no ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', (isset($duplicateFrom) && $duplicateFrom && $duplicateFrom->purchase_date) ? (\Carbon\Carbon::parse($duplicateFrom->purchase_date)->format('Y-m-d')) : date('Y-m-d')) }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date', (isset($duplicateFrom) && $duplicateFrom && $duplicateFrom->expiry_date) ? (\Carbon\Carbon::parse($duplicateFrom->expiry_date)->format('Y-m-d')) : '') }}" min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase d-flex justify-content-between align-items-center">
                            <span>Quantity</span>
                            <span id="qty_zero_badge" class="badge bg-danger-subtle text-danger {{ (int)old('quantity', 0) > 0 ? 'd-none' : '' }}">0 Quantity</span>
                        </label>
                        <input type="number" name="quantity" class="form-control bg-light" value="{{ old('quantity', 0) }}" readonly required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="IN STOCK" {{ old('status', 'OUT OF STOCK') == 'IN STOCK' ? 'selected' : '' }}>IN STOCK</option>
                            <option value="OUT OF STOCK" {{ old('status', 'OUT OF STOCK') == 'OUT OF STOCK' ? 'selected' : '' }}>OUT OF STOCK</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase d-flex align-items-center justify-content-between">
                            <span>Purchase Value</span>
                            <span id="purchase_val_usd_bracket" class="text-primary fw-normal">($0.00 USD)</span>
                        </label>
                        <input type="number" step="0.01" name="purchase_value" class="form-control" value="{{ old('purchase_value', $duplicateFrom->purchase_value ?? 0) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase d-flex align-items-center justify-content-between">
                            <span>Purchase Value / Unit</span>
                            <span id="purchase_unit_usd_bracket" class="text-primary fw-normal">($0.00 USD)</span>
                        </label>
                        <input type="number" step="0.01" name="purchase_value_per_unit" class="form-control bg-light" value="{{ old('purchase_value_per_unit', $duplicateFrom->purchase_value_per_unit ?? 0) }}" readonly required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Taxes</label>
                        <input type="number" step="0.01" name="taxes" class="form-control" value="{{ old('taxes', $duplicateFrom->taxes ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Bank Name</label>
                        <input type="text" name="bank" class="form-control" value="{{ old('bank', $duplicateFrom->bank ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Purchase Currency</label>
                        <select name="local_currency" id="local_currency" class="form-select @error('local_currency') is-invalid @enderror">
                            <option value="">-- Select Currency --</option>
                            @foreach($currencies ?? \App\Helpers\CurrencyHelper::getAllCurrencies() as $code => $c)
                                <option value="{{ $code }}" {{ old('local_currency', $duplicateFrom->local_currency ?? 'USD') == $code ? 'selected' : '' }}>
                                    {{ $code }} - {{ $c['name'] }} ({{ $c['symbol'] }})
                                </option>
                            @endforeach
                        </select>
                        <div id="purchase_currency_error" class="text-danger small mt-1 fw-semibold {{ $errors->has('local_currency') ? '' : 'd-none' }}">
                            <i class="fas fa-exclamation-triangle me-1"></i> <span id="purchase_error_msg">{{ $errors->first('local_currency') ?? 'First set currency in Currency Rates' }}</span>
                            <a href="{{ route('settings.exchange-rates') }}" target="_blank" class="ms-1 fw-bold text-danger text-decoration-underline">Currency Rates &rarr;</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-muted">
                            <i class="fas fa-coins me-1 text-primary"></i> Purchase Value (USD)
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted fw-bold">$</span>
                            <input type="text" id="base_purchase_value" class="form-control bg-light fw-bold text-primary" value="0.00" readonly>
                        </div>
                        <input type="hidden" name="currency_conversion_rate" id="conversion_rate" value="{{ old('currency_conversion_rate', $duplicateFrom->currency_conversion_rate ?? 1.0) }}">
                        <div class="small mt-1 text-muted d-flex align-items-center justify-content-between">
                            <span>Admin Rate: <strong id="admin_rate_badge">1.000000</strong></span>
                            <span class="badge bg-secondary-subtle text-secondary" id="rate_ratio_label">1 USD = 1.000000 USD</span>
                        </div>
                    </div>

                    <!-- Sale & Points Info -->
                    <div class="col-12 mt-5">
                        <h5 class="fw-bold mb-3 text-primary d-flex align-items-center">
                            <i class="fas fa-tags me-2"></i> Sale Price & Rewards
                        </h5>
                        <hr class="mt-0">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-primary d-flex align-items-center" style="min-height: 20px;">Sale Currency</label>
                        <select name="currency" id="currency" class="form-select border-primary @error('currency') is-invalid @enderror" required>
                            <option value="">-- Select Currency --</option>
                            @foreach($currencies ?? \App\Helpers\CurrencyHelper::getAllCurrencies() as $code => $c)
                                <option value="{{ $code }}" {{ old('currency', $duplicateFrom->currency ?? 'PKR') == $code ? 'selected' : '' }}>
                                    {{ $code }} - {{ $c['name'] }} ({{ $c['symbol'] }})
                                </option>
                            @endforeach
                        </select>
                        <div id="sale_currency_error" class="text-danger small mt-1 fw-semibold {{ $errors->has('currency') ? '' : 'd-none' }}">
                            <i class="fas fa-exclamation-triangle me-1"></i> <span id="sale_error_msg">{{ $errors->first('currency') ?? 'First set currency in Currency Rates' }}</span>
                            <a href="{{ route('settings.exchange-rates') }}" target="_blank" class="ms-1 fw-bold text-danger text-decoration-underline">Currency Rates &rarr;</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-success d-flex align-items-center justify-content-between" style="min-height: 20px;">
                            <span>Agent / Reseller Price (Per Unit)</span>
                            <span id="agent_price_usd_bracket" class="fw-normal text-success">($0.00 USD)</span>
                        </label>
                        <input type="number" step="0.01" name="agent_sale_price" class="form-control border-success" value="{{ old('agent_sale_price', $duplicateFrom->agent_sale_price ?? 0) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-success d-flex align-items-center justify-content-between" style="min-height: 20px;">
                            <span>Student Sale Price (Per Unit)</span>
                            <span id="student_price_usd_bracket" class="fw-normal text-success">($0.00 USD)</span>
                        </label>
                        <input type="number" step="0.01" name="student_sale_price" class="form-control border-success" value="{{ old('student_sale_price', $duplicateFrom->student_sale_price ?? 0) }}" required>
                    </div>

                    <!-- Rewards & Points Configuration Box -->
                    <div class="col-12 mt-3">
                        <div class="p-3 bg-light rounded-4 border">
                            <h6 class="fw-bold small text-uppercase text-muted mb-3 d-flex align-items-center">
                                <i class="fas fa-gift text-primary me-2"></i> Reward & Bonus Points Configuration
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-secondary">Referral Points (Reseller)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-handshake text-muted"></i></span>
                                        <input type="number" name="referral_points_reseller" class="form-control border-start-0 bg-white" value="{{ old('referral_points_reseller', $duplicateFrom->referral_points_reseller ?? 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-secondary">Agent Ref. Points / Unit</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-user-tag text-muted"></i></span>
                                        <input type="number" name="agent_referral_points_per_unit" class="form-control border-start-0 bg-white" value="{{ old('agent_referral_points_per_unit', $duplicateFrom->agent_referral_points_per_unit ?? 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-secondary">Agent Bonus Points / Unit</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-star text-warning"></i></span>
                                        <input type="number" name="agent_bonus_points_per_unit" class="form-control border-start-0 bg-white" value="{{ old('agent_bonus_points_per_unit', $duplicateFrom->agent_bonus_points_per_unit ?? 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-secondary">Student Ref. Points / Unit</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-user-graduate text-muted"></i></span>
                                        <input type="number" name="student_referral_points_per_unit" class="form-control border-start-0 bg-white" value="{{ old('student_referral_points_per_unit', $duplicateFrom->student_referral_points_per_unit ?? 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-secondary">Student Bonus Points / Unit</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-award text-warning"></i></span>
                                        <input type="number" name="student_bonus_points_per_unit" class="form-control border-start-0 bg-white" value="{{ old('student_bonus_points_per_unit', $duplicateFrom->student_bonus_points_per_unit ?? 0) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <label class="form-label small fw-bold text-uppercase d-flex align-items-center justify-content-between">
                            <span>Voucher Codes / Upload Info</span>
                            <span id="voucher_codes_badge" class="badge bg-secondary-subtle text-secondary">0 codes</span>
                        </label>
                        <textarea name="upload_vouchers" id="upload_vouchers" class="form-control @error('upload_vouchers') is-invalid @enderror" rows="4" placeholder="Enter voucher codes separated by commas, spaces, or new lines...">{{ old('upload_vouchers') }}</textarea>
                        @error('upload_vouchers')
                            <div class="invalid-feedback d-block fw-semibold mt-1">
                                <i class="fas fa-exclamation-circle me-1"></i> {{ $message }}
                            </div>
                        @enderror
                        <div id="duplicate_codes_warning" class="alert alert-danger py-2 px-3 mt-2 d-none" style="border-radius: 10px;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-triangle me-2 fs-5 mt-1"></i>
                                <div>
                                    <strong class="d-block">Duplicate Voucher Code Detected!</strong>
                                    <div id="duplicate_codes_list" class="small mt-1 fw-medium"></div>
                                    <div class="small text-danger-emphasis mt-1">Each voucher code must be unique across all vouchers in the system. Please remove duplicates before saving.</div>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">Enter multiple codes separated by commas, spaces, or new lines (e.g., CODE1, CODE2, CODE3)</small>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <hr>
                        <div id="zero_quantity_alert" class="alert alert-warning text-start py-2 px-3 mb-3 {{ (int)old('quantity', 0) > 0 ? 'd-none' : '' }}" style="border-radius: 10px;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2 fs-5 text-warning"></i>
                                <div>
                                    <strong>Save Voucher Stock Disabled (0 Quantity):</strong>
                                    <span>A voucher cannot be created with 0 quantity. Please upload at least 1 voucher code in the <strong>Voucher Codes / Upload Info</strong> box above to enable "Save Voucher Stock".</span>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('inventory.index') }}" class="btn btn-light rounded-pill px-5 me-2">Cancel</a>
                        <button type="submit" id="save_voucher_btn" class="btn btn-primary rounded-pill px-5" {{ (int)old('quantity', 0) <= 0 ? 'disabled' : '' }}>Save Voucher Stock</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    $(document).ready(function() {
        const nextId = "{{ $next_id }}";
        const suggestedSku = "{{ $suggestedSku ?? '' }}";
        let preserveSuggestedSku = suggestedSku !== '';

        $('.select2').select2({
            placeholder: "Select Country/Region",
            allowClear: true,
            closeOnSelect: false
        });

        $('.select2-state').select2({
            placeholder: 'Select State',
            allowClear: true,
            closeOnSelect: false
        });
        $('.select2-city').select2({
            placeholder: 'Select City',
            allowClear: true,
            closeOnSelect: false
        });

        function generateSKU() {
            if (preserveSuggestedSku) {
                return;
            }
            let brand = $('#brand_name').val().trim();
            let variant = $('#voucher_variant').val().trim();
            let country = $('#country').val(); // Array if multiple select

            if (brand.length >= 1 && country && country.length >= 1) {
                let countryPart = "";

                if (country.includes('all')) {
                    countryPart = "GLB";
                } else if (country.length > 1) {
                    countryPart = "MULTY";
                } else {
                    countryPart = country[0].substring(0, 3).toUpperCase();
                }

                // 2. Brand Part (2 characters)
                let brandWords = brand.split(/\s+/).filter(w => w.length > 0);
                let brandPart = "";
                if (brandWords.length >= 2) {
                    brandPart = brandWords[0][0].toUpperCase() + brandWords[1][0].toUpperCase();
                } else if (brand.length >= 2) {
                    brandPart = brand.substring(0, 2).toUpperCase();
                } else {
                    brandPart = brand.toUpperCase() + "X";
                }

                // 3. Variant Part (2 characters)
                let variantPart = "VAR";
                if (variant) {
                    let variantWords = variant.split(/\s+/).filter(w => w.length > 0);
                    if (variantWords.length >= 2) {
                        variantPart = variantWords[0][0].toUpperCase() + variantWords[1][0].toUpperCase();
                    } else if (variant.length >= 2) {
                        variantPart = variant.substring(0, 2).toUpperCase();
                    } else {
                        variantPart = variant.toUpperCase() + "X";
                    }
                }

                // 4. Numeric ID
                let suffix = nextId.toString().padStart(3, '0');

                // Final SKU: COUNTRY-BRAND-VARIANT-001
                $('#sku_id').val(countryPart + "-" + brandPart + "-" + variantPart + "-" + suffix);
            } else {
                $('#sku_id').val('');
            }
        }

        $('#brand_name, #voucher_variant').on('input', function() {
            preserveSuggestedSku = false;
            generateSKU();
        });

        $('#country').on('select2:select select2:unselect select2:clear', function() {
            preserveSuggestedSku = false;
            generateSKU();
        });

        function calculateUnitValue() {
            let quantity = parseFloat($('input[name="quantity"]').val()) || 0;
            let totalValue = parseFloat($('input[name="purchase_value"]').val()) || 0;
            let unitValue = 0;

            if (quantity > 0) {
                unitValue = totalValue / quantity;
            }

            $('input[name="purchase_value_per_unit"]').val(unitValue.toFixed(2));
            updateConversionCalculation();
        }

        $('input[name="quantity"], input[name="purchase_value"]').on('input', function() {
            calculateUnitValue();
        });

        const existingCodesMap = @json($existingCodesMap ?? []);
        let voucherCodesValidationState = {
            hasDuplicates: false,
            errorMessage: ''
        };

        function validateVoucherCodes(codes) {
            let seen = {};
            let duplicates = [];
            let crossConflicts = [];

            for (let code of codes) {
                let upper = code.toUpperCase();
                if (seen[upper]) {
                    if (!duplicates.includes(code)) {
                        duplicates.push(code);
                    }
                } else {
                    seen[upper] = true;
                }

                if (existingCodesMap[upper]) {
                    crossConflicts.push({ code: code, sku: existingCodesMap[upper] });
                }
            }

            let errors = [];
            if (duplicates.length > 0) {
                let sample = duplicates.slice(0, 5).join(', ');
                if (duplicates.length > 5) sample += ' and ' + (duplicates.length - 5) + ' more';
                errors.push('Duplicate code(s) in input: [' + sample + ']');
            }
            if (crossConflicts.length > 0) {
                let sampleConflict = crossConflicts.slice(0, 3).map(c => "'" + c.code + "' (already uploaded in SKU: " + c.sku + ")").join(', ');
                if (crossConflicts.length > 3) sampleConflict += ' and ' + (crossConflicts.length - 3) + ' more';
                errors.push('Already exists in inventory: ' + sampleConflict);
            }

            if (errors.length > 0) {
                voucherCodesValidationState.hasDuplicates = true;
                voucherCodesValidationState.errorMessage = errors.join(' | ');
                $('#duplicate_codes_list').text(voucherCodesValidationState.errorMessage);
                $('#duplicate_codes_warning').removeClass('d-none');
                $('#upload_vouchers').addClass('is-invalid');
                $('#voucher_codes_badge').removeClass('bg-secondary-subtle text-secondary bg-success-subtle text-success').addClass('bg-danger-subtle text-danger').text(codes.length + ' codes (duplicates detected)');
            } else {
                voucherCodesValidationState.hasDuplicates = false;
                voucherCodesValidationState.errorMessage = '';
                $('#duplicate_codes_warning').addClass('d-none');
                $('#upload_vouchers').removeClass('is-invalid');
                if (codes.length > 0) {
                    $('#voucher_codes_badge').removeClass('bg-secondary-subtle text-secondary bg-danger-subtle text-danger').addClass('bg-success-subtle text-success').text(codes.length + ' codes valid');
                } else {
                    $('#voucher_codes_badge').removeClass('bg-success-subtle text-success bg-danger-subtle text-danger').addClass('bg-secondary-subtle text-secondary').text('0 codes');
                }
            }
        }

        function formatVouchers(val) {
            // Replace newlines, tabs, and spaces with a comma
            let cleaned = val.replace(/[\n\r\t\s]+/g, ',');
            // Replace multiple commas with a single comma
            cleaned = cleaned.replace(/,+/g, ',');
            // Trim leading/trailing commas
            cleaned = cleaned.replace(/^,|,$/g, '');

            let codes = cleaned.split(',').map(c => c.trim()).filter(c => c.length > 0);
            let count = codes.length;

            validateVoucherCodes(codes);

            $('input[name="quantity"]').val(count).trigger('input');
            if (count > 0 && $('select[name="status"]').val() === 'OUT OF STOCK') {
                $('select[name="status"]').val('IN STOCK');
            }

            // For display, use ", " for better readability
            let displayValue = codes.join(', ');

            return {
                display: displayValue,
                count: count
            };
        }

        $('#upload_vouchers').on('input', function() {
            let rawVal = $(this).val();
            let codes = rawVal.split(/[, \n\r\t]+/).map(c => c.trim()).filter(c => c.length > 0);
            $('input[name="quantity"]').val(codes.length).trigger('input');
            if (codes.length > 0 && $('select[name="status"]').val() === 'OUT OF STOCK') {
                $('select[name="status"]').val('IN STOCK');
            }
            validateVoucherCodes(codes);
        });

        $('#upload_vouchers').on('blur paste', function(e) {
            let self = this;
            setTimeout(function() {
                let res = formatVouchers($(self).val());
                if (e.type === 'paste' || $(self).val().includes('\n') || $(self).val().includes('\t')) {
                    $(self).val(res.display);
                }
            }, 100);
        });

        // Initialize validation on page load if old value exists
        if ($('#upload_vouchers').val()) {
            formatVouchers($('#upload_vouchers').val());
        }

        const exchangeRates = @json($exchangeRates ?? \App\Services\CurrencyService::getAllActiveRates());
        const baseCurrency = @json($baseCurrency ?? 'USD');

        function isRateDefined(curr) {
            if (!curr) return true;
            curr = curr.trim().toUpperCase();
            if (curr === baseCurrency) return true;
            return (exchangeRates[curr] !== undefined && parseFloat(exchangeRates[curr]) > 0);
        }

        function updateConversionCalculation() {
            let purchaseCurr = ($('#local_currency').val() || '').trim().toUpperCase();
            let saleCurr = ($('#currency').val() || '').trim().toUpperCase();
            let purchaseRateOk = isRateDefined(purchaseCurr);
            let saleRateOk = isRateDefined(saleCurr);

            // 1. Purchase Currency check
            if (!purchaseRateOk) {
                $('#local_currency').addClass('is-invalid');
                $('#purchase_error_msg').html('First set <strong>' + purchaseCurr + '</strong> in Currency Rates');
                $('#purchase_currency_error').removeClass('d-none');

                $('#conversion_rate').val('0');
                $('#admin_rate_badge').html('<span class="badge bg-danger">Not Set</span>');
                $('#rate_ratio_label').html('<span class="text-danger fw-bold">Rate Missing in Currency Rates</span>');
                $('#base_purchase_value').val('0.00');
                $('#purchase_val_usd_bracket').text('($0.00 USD)');
                $('#purchase_unit_usd_bracket').text('($0.00 USD)');
            } else {
                $('#local_currency').removeClass('is-invalid');
                $('#purchase_currency_error').addClass('d-none');

                let curr = purchaseCurr || baseCurrency;
                let rate = exchangeRates[curr] !== undefined ? parseFloat(exchangeRates[curr]) : 1.0;
                
                $('#conversion_rate').val(rate.toFixed(6));
                $('#admin_rate_badge').text(rate.toFixed(6));
                $('#rate_ratio_label').html('1 ' + curr + ' = ' + rate.toFixed(6) + ' ' + baseCurrency);

                let purchaseVal = parseFloat($('input[name="purchase_value"]').val()) || 0;
                let baseVal = purchaseVal * rate;
                $('#base_purchase_value').val(baseVal.toFixed(2));
                $('#purchase_val_usd_bracket').text('($' + baseVal.toFixed(2) + ' USD)');

                let purchaseUnitVal = parseFloat($('input[name="purchase_value_per_unit"]').val()) || 0;
                let baseUnitVal = purchaseUnitVal * rate;
                $('#purchase_unit_usd_bracket').text('($' + baseUnitVal.toFixed(2) + ' USD)');
            }

            // 2. Sale Currency check
            if (!saleRateOk) {
                $('#currency').addClass('is-invalid');
                $('#sale_error_msg').html('First set <strong>' + saleCurr + '</strong> in Currency Rates');
                $('#sale_currency_error').removeClass('d-none');
                $('#agent_price_usd_bracket').text('($0.00 USD)');
                $('#student_price_usd_bracket').text('($0.00 USD)');
            } else {
                $('#currency').removeClass('is-invalid');
                $('#sale_currency_error').addClass('d-none');

                let curr = saleCurr || baseCurrency;
                let sRate = exchangeRates[curr] !== undefined ? parseFloat(exchangeRates[curr]) : 1.0;

                let agentPrice = parseFloat($('input[name="agent_sale_price"]').val()) || 0;
                let agentPriceUsd = agentPrice * sRate;
                $('#agent_price_usd_bracket').text('($' + agentPriceUsd.toFixed(2) + ' USD)');

                let studentPrice = parseFloat($('input[name="student_sale_price"]').val()) || 0;
                let studentPriceUsd = studentPrice * sRate;
                $('#student_price_usd_bracket').text('($' + studentPriceUsd.toFixed(2) + ' USD)');
            }

            // 3. Quantity check: vouchers cannot be created with 0 quantity
            let currentQty = parseInt($('input[name="quantity"]').val()) || 0;
            let hasValidQty = currentQty > 0;

            if (!hasValidQty) {
                $('#zero_quantity_alert').removeClass('d-none');
                $('#qty_zero_badge').removeClass('d-none');
            } else {
                $('#zero_quantity_alert').addClass('d-none');
                $('#qty_zero_badge').addClass('d-none');
            }

            // 4. Submit button state
            let canSubmit = purchaseRateOk && saleRateOk && hasValidQty && !voucherCodesValidationState.hasDuplicates;
            let $btn = $('#save_voucher_btn, button[type="submit"]');
            if (!canSubmit) {
                let reason = 'Please complete all required fields';
                if (!hasValidQty) {
                    reason = 'A voucher cannot be created with 0 quantity. Please upload at least 1 voucher code.';
                } else if (!purchaseRateOk || !saleRateOk) {
                    reason = 'Please set missing currency in Currency Rates first';
                } else if (voucherCodesValidationState.hasDuplicates) {
                    reason = 'Please resolve duplicate voucher codes before saving';
                }
                $btn.prop('disabled', true).addClass('opacity-50').attr('title', reason);
            } else {
                $btn.prop('disabled', false).removeClass('opacity-50').removeAttr('title');
            }
        }

        $('#local_currency, #currency, input[name="purchase_value"], input[name="purchase_value_per_unit"], input[name="agent_sale_price"], input[name="student_sale_price"]').on('input change', function() {
            updateConversionCalculation();
        });

        $('form').on('submit', function(e) {
            let currentQty = parseInt($('input[name="quantity"]').val()) || 0;
            if (currentQty <= 0) {
                e.preventDefault();
                alert("A voucher cannot be created with 0 quantity. Please upload at least 1 voucher code in 'Voucher Codes / Upload Info' first.");
                $('#upload_vouchers').focus();
                return false;
            }

            let purchaseCurr = ($('#local_currency').val() || '').trim().toUpperCase();
            let saleCurr = ($('#currency').val() || '').trim().toUpperCase();

            if (!isRateDefined(purchaseCurr)) {
                e.preventDefault();
                alert("Please first set '" + purchaseCurr + "' in Currency Rates.");
                $('#local_currency').focus();
                return false;
            }
            if (!isRateDefined(saleCurr)) {
                e.preventDefault();
                alert("Please first set '" + saleCurr + "' in Currency Rates.");
                $('#currency').focus();
                return false;
            }

            if (voucherCodesValidationState.hasDuplicates) {
                e.preventDefault();
                alert("Cannot save voucher stock:\n\n" + voucherCodesValidationState.errorMessage + "\n\nPlease remove duplicate codes before proceeding.");
                $('#upload_vouchers').focus();
                return false;
            }
        });

        updateConversionCalculation();
    });
</script>

<script type="module">
    import {
        Country,
        State,
        City
    } from "https://cdn.jsdelivr.net/npm/country-state-city@3.2.1/+esm";

    document.addEventListener("DOMContentLoaded", function() {
        const countrySelect = $("#country");
        const stateSelect = $("#state");
        const citySelect = $("#city");
        const initialCountries = {!! json_encode(old('country_region', $duplicateFrom->country_region ?? [])) !!};
        const initialState = {!! json_encode(old('state', $duplicateFrom->state ?? [])) !!};
        const initialCity = {!! json_encode(old('city', $duplicateFrom->city ?? [])) !!};

        // Populate Countries
        const countries = Country.getAllCountries();
        countries.forEach(country => {
            const isSelected = Array.isArray(initialCountries) && initialCountries.includes(country.name);
            const option = new Option(country.name, country.name, isSelected, isSelected);
            option.dataset.code = country.isoCode;
            countrySelect.append(option);
        });

        function updateStates(countryCode, selectedStates = []) {
            const isAllSelected = Array.isArray(selectedStates) && selectedStates.includes('all');
            const allOption = new Option('All States', 'all', isAllSelected, isAllSelected);
            stateSelect.empty().append(allOption);
            if (countryCode) {
                const states = State.getStatesOfCountry(countryCode);
                states.forEach(state => {
                    const isSelected = Array.isArray(selectedStates) && selectedStates.includes(state.name);
                    const option = new Option(state.name, state.name, isSelected, isSelected);
                    option.dataset.code = state.isoCode;
                    stateSelect.append(option);
                });
            }
            stateSelect.trigger('change.select2');
        }

        function updateCities(countryCode, stateCode, selectedCities = []) {
            const isAllSelected = Array.isArray(selectedCities) && selectedCities.includes('all');
            const allOption = new Option('All Cities', 'all', isAllSelected, isAllSelected);
            citySelect.empty().append(allOption);
            if (countryCode && stateCode) {
                const cities = City.getCitiesOfState(countryCode, stateCode);
                cities.forEach(city => {
                    const isSelected = Array.isArray(selectedCities) && selectedCities.includes(city.name);
                    const option = new Option(city.name, city.name, isSelected, isSelected);
                    citySelect.append(option);
                });
            }
            citySelect.trigger('change.select2');
        }

        // Country Change Event
        countrySelect.on('change', function() {
            const selectedValues = $(this).val();

            // Handle All Countries logic
            if (selectedValues && selectedValues.includes('all')) {
                if (selectedValues.length > 1) {
                    $(this).val(['all']).trigger('change.select2');
                    return;
                }
            }

            if (!selectedValues || selectedValues.length !== 1 || selectedValues.includes('all')) {
                stateSelect.prop('disabled', true).trigger('change.select2');
                citySelect.prop('disabled', true).trigger('change.select2');
                stateSelect.html('<option value="">State Disabled (Multi/All)</option>');
                citySelect.html('<option value="">City Disabled (Multi/All)</option>');
            } else {
                stateSelect.prop('disabled', false).trigger('change.select2');
                citySelect.prop('disabled', false).trigger('change.select2');
                const countryCode = $(this).find('option:selected').data('code');
                const selectedCountryName = $(this).val()[0];
                const initialCountryName = Array.isArray(initialCountries) && initialCountries.length > 0 ? initialCountries[0] : null;
                if (selectedCountryName === initialCountryName) {
                    updateStates(countryCode, Array.isArray(initialState) ? initialState : [initialState]);
                } else {
                    updateStates(countryCode, ['all']);
                }
            }
        });

        // Trigger initial country change
        countrySelect.trigger('change');

        // State Change Event
        stateSelect.on('change', function() {
            const selectedStates = $(this).val();

            // Handle All States logic
            if (selectedStates && selectedStates.includes('all')) {
                if (selectedStates.length > 1) {
                    $(this).val(['all']).trigger('change.select2');
                    return;
                }
            }

            const countryOption = countrySelect.find('option:selected')[0];
            const countryCode = countryOption ? countryOption.dataset.code : null;

            if (!selectedStates || selectedStates.length !== 1 || selectedStates.includes('all')) {
                citySelect.prop('disabled', true).trigger('change.select2');
                citySelect.html('<option value="">City Disabled (Multi/All)</option>');
            } else {
                citySelect.prop('disabled', false).trigger('change.select2');
                const stateOption = $(this).find('option:selected')[0];
                const stateCode = stateOption ? stateOption.dataset.code : null;
                if (countryCode && stateCode) {
                    const selectedStateName = $(this).val()[0];
                    const initialSingleState = Array.isArray(initialState) && initialState.length > 0 ? initialState[0] : null;
                    if (selectedStateName === initialSingleState) {
                        updateCities(countryCode, stateCode, Array.isArray(initialCity) ? initialCity : [initialCity]);
                    } else {
                        updateCities(countryCode, stateCode, ['all']);
                    }
                }
            }
        });

        // Trigger initial state change
        stateSelect.trigger('change');

        // City Change Event
        citySelect.on('change', function() {
            const selectedCities = $(this).val();
            if (selectedCities && selectedCities.includes('all')) {
                if (selectedCities.length > 1) {
                    $(this).val(['all']).trigger('change.select2');
                    return;
                }
            }
        });
    });
</script>
@endpush
@endsection