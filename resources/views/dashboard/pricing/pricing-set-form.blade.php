@extends('layouts.master')

@section('content')
@php $isEdit = isset($rule); @endphp
<style>
    /* Fix height & alignment */
    .select2-container--default .select2-selection--multiple,
    .select2-container--default .select2-selection--single {
        min-height: 42px;
        padding: 4px 8px;
        border: 1px solid #ced4da;
        border-radius: 6px;
    }

    /* Selected items style */
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #0d6efd;
        border: none;
        color: #fff;
        padding: 2px 8px;
        margin-top: 4px;
        font-size: 13px;
    }

    /* Clean Select2 Multiple Fix */
    .select2-container--default .select2-selection--multiple {
        min-height: 42px;
        padding: 4px 8px;
        border: 1px solid #ced4da;
        border-radius: 6px;
    }

    .select2-container {
        width: 100% !important;
    }

    /* Fix dropdown position */
    .select2-container--open {
        z-index: 9999;
    }

    /* Remove weird gray full-width highlight */
</style>

<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">{{ $isEdit ? 'Edit Purches Details' : 'Purches Details' }}</h5>
            <a href="{{ route('pricing.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Rules
            </a>
        </div>
        <div class="card-body px-4">
            <form id="setPriceForm">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Brand Name</label>
                        <input type="text" name="brand_name" id="brand_name" class="form-control" value="{{ $isEdit ? $rule->brand_name : '' }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Currency</label>
                        <input type="text" name="currency" id="currency" class="form-control" value="{{ $isEdit ? $rule->currency : '' }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Country *</label>
                        @if($isEdit)
                        <input type="hidden" name="country_code" value="{{ $rule->country_code }}">
                        <input type="hidden" name="country_name" value="{{ $rule->country_name }}">
                        <input type="text" class="form-control" value="{{ $rule->country_name }}" disabled>
                        @else
                        <select name="country_name" id="country" class="form-select select2" required></select>
                        <input type="hidden" name="country_code" id="country_code">
                        @endif
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">State / Province</label>
                        <select name="state" id="state" class="form-select select2" {{ $isEdit ? 'disabled' : '' }}>
                            @if($isEdit)
                            <option selected>{{ $rule->state }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">City</label>
                        <select name="city" id="city" class="form-select select2" {{ $isEdit ? 'disabled' : '' }}>
                            @if($isEdit)
                            <option selected>{{ $rule->city }}</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Voucher Variant</label>
                        <input type="text" name="voucher_variant" id="voucher_variant" class="form-control" value="{{ $isEdit ? $rule->voucher_variant : '' }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Voucher Type</label>
                        <input type="text" name="voucher_type" id="voucher_type" class="form-control" value="{{ $isEdit ? $rule->voucher_type : '' }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Purchase Invoice No.</label>
                        <input type="text" name="purchase_invoice_no" id="purchase_invoice_no" class="form-control" value="{{ $isEdit ? $rule->purchase_invoice_no : '' }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Purchase Date</label>
                        <input type="date" name="purchase_date" id="purchase_date" class="form-control" max="{{ date('Y-m-d') }}" value="{{ $isEdit && $rule->purchase_date ? \Carbon\Carbon::parse($rule->purchase_date)->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Total Quantity</label>
                        <input type="number" name="total_quantity" id="total_quantity" class="form-control" value="{{ $isEdit ? $rule->total_quantity : '' }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Purchase Value</label>
                        <input type="number" step="0.01" name="purchase_value" id="purchase_value" class="form-control" value="{{ $isEdit ? $rule->purchase_value : '' }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Taxes</label>
                        <input type="number" step="0.01" name="taxes" id="taxes" class="form-control" value="{{ $isEdit ? $rule->taxes : '' }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Per Unit Price</label>
                        <input type="number" step="0.01" name="per_unit_price" id="per_unit_price" class="form-control" value="{{ $isEdit ? $rule->per_unit_price : '' }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Credit Limit</label>
                        <input type="number" step="0.01" name="credit_limit" id="credit_limit" class="form-control" value="{{ $isEdit ? $rule->credit_limit : '' }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Issue Date</label>
                        <input type="date" name="issue_date" id="issue_date" class="form-control" max="{{ date('Y-m-d') }}" value="{{ $isEdit && $rule->issue_date ? \Carbon\Carbon::parse($rule->issue_date)->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-uppercase small">Expiry Date</label>
                        <input type="date" name="expiry_date" id="expiry_date" class="form-control" min="{{ date('Y-m-d', strtotime('+1 day')) }}" value="{{ $isEdit && $rule->expiry_date ? \Carbon\Carbon::parse($rule->expiry_date)->format('Y-m-d') : '' }}">
                    </div>
                </div>

                <div class="mt-4 pb-4 border-top pt-4 text-end">
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" id="submitBtn">
                        <i class="fas fa-check-circle me-1"></i> {{ $isEdit ? 'Update Purchase Details' : 'Save Purchase Details' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import {
        Country,
        State,
        City
    } from "https://cdn.jsdelivr.net/npm/country-state-city@3.2.1/+esm";

    $(document).ready(function() {
        const countrySelect = document.getElementById("country");
        const stateSelect = document.getElementById("state");
        const citySelect = document.getElementById("city");

        if (countrySelect) {
            countrySelect.innerHTML = '<option value="">Select Country</option>';
            const countries = Country.getAllCountries();
            countries.forEach(country => {
                const option = document.createElement('option');
                option.value = country.name;
                option.textContent = country.name;
                option.dataset.code = country.isoCode;
                countrySelect.appendChild(option);
            });

            $(countrySelect).on('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const countryCode = selectedOption ? selectedOption.dataset.code : '';
                $('#country_code').val(countryCode);

                stateSelect.innerHTML = '<option value="">Select State</option>';
                citySelect.innerHTML = '<option value="">Select City</option>';

                if (countryCode) {
                    const states = State.getStatesOfCountry(countryCode);
                    states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.name;
                        option.textContent = state.name;
                        option.dataset.code = state.isoCode;
                        stateSelect.appendChild(option);
                    });
                }
                $(stateSelect).trigger('change');
            });

            $(stateSelect).on('change', function() {
                const selectedCountryOption = countrySelect.options[countrySelect.selectedIndex];
                const countryCode = selectedCountryOption ? selectedCountryOption.dataset.code : '';

                const selectedStateOption = this.options[this.selectedIndex];
                const stateCode = selectedStateOption ? selectedStateOption.dataset.code : '';

                citySelect.innerHTML = '<option value="">Select City</option>';

                if (countryCode && stateCode) {
                    const cities = City.getCitiesOfState(countryCode, stateCode);
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.name;
                        option.textContent = city.name;
                        citySelect.appendChild(option);
                    });
                }
                $(citySelect).trigger('change');
            });
        }
    });
</script>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').each(function() {
            $(this).select2({
                placeholder: $(this).attr('placeholder') || 'Select an option',
                allowClear: true,
                width: '100%'
            });
        });

        // Auto calculation: Purchase Value / Total Quantity = Per Unit Price
        function calculatePerUnit() {
            var totalQty = parseFloat($('#total_quantity').val()) || 0;
            var totalVal = parseFloat($('#purchase_value').val()) || 0;
            if (totalQty > 0) {
                var perUnit = totalVal / totalQty;
                $('#per_unit_price').val(perUnit.toFixed(2));
            } else {
                $('#per_unit_price').val('0.00');
            }
        }

        $('#total_quantity, #purchase_value').on('input change', function() {
            calculatePerUnit();
        });

        $('#setPriceForm').on('submit', function(e) {
            e.preventDefault();

            // Date validation
            var purchaseDate = $('#purchase_date').val();
            var issueDate = $('#issue_date').val();
            var expiryDate = $('#expiry_date').val();
            var today = new Date().toISOString().split('T')[0];

            if (purchaseDate && purchaseDate > today) {
                toastr.error('Purchase date cannot be in the future.');
                return;
            }

            if (issueDate && issueDate > today) {
                toastr.error('Issue date cannot be in the future.');
                return;
            }

            if (expiryDate && expiryDate <= today) {
                toastr.error('Expiry date must be in the future.');
                return;
            }

            var btn = $('#submitBtn');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> {{ $isEdit ? "Updating..." : "Saving..." }}');

            $.ajax({
                url: "{{ $isEdit ? route('pricing.update', $rule->id) : route('pricing.store') }}",
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(function() {
                            window.location.href = "{{ route('pricing.index') }}";
                        }, 1500);
                    } else {
                        toastr.error(response.message);
                        btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> {{ $isEdit ? "Update Purchase Details" : "Save Purchase Details" }}');
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON.errors;
                    if (errors) {
                        Object.keys(errors).forEach(function(key) {
                            toastr.error(errors[key][0]);
                        });
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                    btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> {{ $isEdit ? "Update Purchase Details" : "Save Purchase Details" }}');
                }
            });
        });
    });
</script>
@endpush