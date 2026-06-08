@extends('layouts.master')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Add New Voucher Stock</h2>
            <p class="text-muted mb-0">Fill in the details to add a new voucher to the inventory.</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-light rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i> Back to Inventory
        </a>
    </div>

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
                        <input type="text" name="sku_id" id="sku_id" class="form-control @error('sku_id') is-invalid @enderror" value="{{ old('sku_id') }}" readonly required>
                        @error('sku_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Brand Name</label>
                        <input type="text" name="brand_name" id="brand_name" class="form-control" value="{{ old('brand_name') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Country/Region</label>
                        <select name="country_region[]" id="country" class="form-select select2" multiple required>
                            <option value="all">All Countries (GLB)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">State</label>
                        <select name="state[]" id="state" class="form-select select2-state" multiple required>
                            <option value="all">All States</option>
                        </select>
                    </div>
                    <!-- <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">City</label>
                        <select name="city" id="city" class="form-select">
                            <option value="">Select City</option>
                        </select>
                    </div> -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Currency</label>
                        <input type="text" name="currency" id="currency" class="form-control" value="{{ old('currency', 'PKR') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Voucher Variant</label>
                        <input type="text" name="voucher_variant" id="voucher_variant" class="form-control" value="{{ old('voucher_variant') }}" placeholder="e.g. Subscription">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Voucher Type</label>
                        <input name="voucher_type" class="form-control" value="{{ old('voucher_type') }}" required>
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
                        <input type="text" name="purchase_invoice_no" class="form-control" value="{{ old('purchase_invoice_no') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}" min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Quantity</label>
                        <input type="number" name="quantity" class="form-control bg-light" value="{{ old('quantity', 0) }}" readonly required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="IN STOCK">IN STOCK</option>

                            <option value="OUT OF STOCK">OUT OF STOCK</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Purchase Value</label>
                        <input type="number" step="0.01" name="purchase_value" class="form-control" value="{{ old('purchase_value', 0) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Purchase Value / Unit</label>
                        <input type="number" step="0.01" name="purchase_value_per_unit" class="form-control" value="{{ old('purchase_value_per_unit', 0) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Taxes</label>
                        <input type="number" step="0.01" name="taxes" class="form-control" value="{{ old('taxes', 0) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Bank Name</label>
                        <input type="text" name="bank" class="form-control" value="{{ old('bank') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Local Currency</label>
                        <input type="text" name="local_currency" class="form-control" value="{{ old('local_currency') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Currency Conv. @</label>
                        <input type="number" step="0.000001" name="currency_conversion_rate" id="conversion_rate" class="form-control" value="{{ old('currency_conversion_rate', 1.0) }}">
                        <div id="conversion_explanation" class="small text-muted mt-1 fw-bold">1 <span class="primary_curr_label">PKR</span> = <span class="rate_label">1.000000</span> <span class="local_curr_label">---</span></div>
                    </div>

                    <!-- Sale & Points Info -->
                    <div class="col-12 mt-5">
                        <h5 class="fw-bold mb-3 text-primary">Sale Price & Rewards</h5>
                        <hr>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-success">Reseller Agent /Agent Sale Price</label>
                        <input type="number" step="0.01" name="agent_sale_price" class="form-control border-success" value="{{ old('agent_sale_price', 0) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-success">Student Sale Price</label>
                        <input type="number" step="0.01" name="student_sale_price" class="form-control border-success" value="{{ old('student_sale_price', 0) }}" required>
                    </div>


                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Referral Points (Reseller)</label>
                        <input type="number" name="referral_points_reseller" class="form-control" value="{{ old('referral_points_reseller', 0) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Agent Ref. Points / Unit</label>
                        <input type="number" name="agent_referral_points_per_unit" class="form-control" value="{{ old('agent_referral_points_per_unit', 0) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Agent Bonus Points / Unit</label>
                        <input type="number" name="agent_bonus_points_per_unit" class="form-control" value="{{ old('agent_bonus_points_per_unit', 0) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Student Ref. Points / Unit</label>
                        <input type="number" name="student_referral_points_per_unit" class="form-control" value="{{ old('student_referral_points_per_unit', 0) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Student Bonus Points / Unit</label>
                        <input type="number" name="student_bonus_points_per_unit" class="form-control" value="{{ old('student_bonus_points_per_unit', 0) }}">
                    </div>

                    <div class="col-12 mt-4">
                        <label class="form-label small fw-bold text-uppercase">Voucher Codes / Upload Info</label>
                        <textarea name="upload_vouchers" class="form-control" rows="4" placeholder="Enter voucher codes separated by comma...">{{ old('upload_vouchers') }}</textarea>
                        <small class="text-muted">Enter multiple codes separated by commas (e.g., CODE1, CODE2, CODE3)</small>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <hr>
                        <button type="reset" class="btn btn-light rounded-pill px-5">Reset</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-5">Save Voucher Stock</button>
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

        $('.select2').select2({
            placeholder: "Select Country/Region",
            allowClear: true,
            closeOnSelect: false
        });

        $('.select2-state').select2({
            placeholder: "Select State",
            allowClear: true,
            closeOnSelect: false
        });

        function generateSKU() {
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

        $('#brand_name, #voucher_variant, #country').on('input change', function() {
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
        }

        $('input[name="quantity"], input[name="purchase_value"]').on('input', function() {
            calculateUnitValue();
        });

        function formatVouchers(val) {
            // Replace newlines, tabs, and spaces with a comma
            let cleaned = val.replace(/[\n\r\t\s]+/g, ',');
            // Replace multiple commas with a single comma
            cleaned = cleaned.replace(/,+/g, ',');
            // Trim leading/trailing commas
            cleaned = cleaned.replace(/^,|,$/g, '');

            // Calculate quantity based on number of codes
            let codes = cleaned.split(',').filter(code => code.trim().length > 0);
            let count = codes.length;

            $('input[name="quantity"]').val(count).trigger('input');

            // For display, use ", " for better readability
            let displayValue = codes.join(', ');

            return {
                display: displayValue,
                count: count
            };
        }

        // Better handling for paste
        $('textarea[name="upload_vouchers"]').on('input paste', function(e) {
            let self = this;
            setTimeout(function() {
                let res = formatVouchers($(self).val());
                $(self).val(res.display);
            }, 100);
        });

        function updateConversionExplanation() {
            let primary = $('#currency').val() || '---';
            let local = $('input[name="local_currency"]').val() || '---';
            let rate = $('#conversion_rate').val() || '1.0';

            $('.primary_curr_label').text(primary);
            $('.local_curr_label').text(local);
            $('.rate_label').text(parseFloat(rate).toFixed(6));
        }

        $('#currency, input[name="local_currency"], #conversion_rate').on('input change', function() {
            updateConversionExplanation();
        });

        updateConversionExplanation();
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

        // Populate Countries
        const countries = Country.getAllCountries();
        countries.forEach(country => {
            const option = new Option(country.name, country.name, false, false);
            option.dataset.code = country.isoCode;
            countrySelect.append(option);
        });

        countrySelect.trigger('change');

        function updateStates(countryCode) {
            stateSelect.empty().append('<option value="all">All States</option>');
            citySelect.html('<option value="">Select City</option>');
            if (countryCode) {
                const states = State.getStatesOfCountry(countryCode);
                states.forEach(state => {
                    const option = new Option(state.name, state.name, false, false);
                    option.dataset.code = state.isoCode;
                    stateSelect.append(option);
                });
            }
            stateSelect.trigger('change');
        }

        function updateCities(countryCode, stateCode) {
            citySelect.html('<option value="">Select City</option>');
            if (countryCode && stateCode) {
                const cities = City.getCitiesOfState(countryCode, stateCode);
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.name;
                    option.textContent = city.name;
                    citySelect.append(option);
                });
            }
        }

        // Country Change Event
        countrySelect.on('change', function() {
            const selectedValues = $(this).val();

            // Handle All Countries logic
            if (selectedValues && selectedValues.includes('all')) {
                if (selectedValues.length > 1) {
                    // If 'all' is selected along with others, just keep 'all'
                    $(this).val(['all']).trigger('change.select2');
                    return;
                }
            }

            if (!selectedValues || selectedValues.length !== 1 || selectedValues.includes('all')) {
                // Disable state and city if multiple countries or all countries or no country selected
                stateSelect.prop('disabled', true).trigger('change.select2');
                citySelect.disabled = true;
                stateSelect.html('<option value="">State Disabled (Multi/All)</option>');
                citySelect.innerHTML = '<option value="">City Disabled (Multi/All)</option>';
            } else {
                stateSelect.prop('disabled', false).trigger('change.select2');
                citySelect.disabled = false;
                const countryCode = $(this).find('option:selected').data('code');
                updateStates(countryCode);
            }
        });

        // State Change Event
        stateSelect.on('change', function() {
            const selectedStates = $(this).val();

            // Handle All States logic
            if (selectedStates && selectedStates.includes('all')) {
                if (selectedStates.length > 1) {
                    // If 'all' is selected along with others, just keep 'all'
                    $(this).val(['all']).trigger('change.select2');
                    return;
                }
            }

            const countryOption = countrySelect.find('option:selected')[0];
            const countryCode = countryOption ? countryOption.dataset.code : null;

            if (!selectedStates || selectedStates.length !== 1 || selectedStates.includes('all')) {
                // Disable city if multiple states or all states or no state selected
                citySelect.disabled = true;
                citySelect.innerHTML = '<option value="">City Disabled (Multi/All)</option>';
            } else {
                citySelect.disabled = false;
                const stateOption = $(this).find('option:selected')[0];
                const stateCode = stateOption ? stateOption.dataset.code : null;
                if (countryCode && stateCode) {
                    updateCities(countryCode, stateCode);
                }
            }
        });
    });
</script>
@endpush
@endsection