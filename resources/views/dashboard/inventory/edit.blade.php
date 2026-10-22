@extends('layouts.master')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Edit Voucher Stock</h2>
            <p class="text-muted mb-0">Update the details for voucher: <strong>{{ $inventory->brand_name }} ({{ $inventory->sku_id }})</strong></p>
        </div>
        <a href="{{ ($isDraftEmpty ?? false) ? route('inventory.discard-draft', $inventory->id) : route('inventory.index') }}" class="btn btn-light rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i> Back to Inventory
        </a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 20px;">
        <div class="card-body p-4">
            <form action="{{ route('inventory.update', $inventory->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @php
                    $allCodes = is_array($inventory->upload_vouchers) ? $inventory->upload_vouchers : [];
                    $deliveredCodes = is_array($inventory->delivered_vouchers) ? $inventory->delivered_vouchers : [];
                    $remainingCodes = array_diff($allCodes, $deliveredCodes);
                    $availableCount = count($remainingCodes);
                    $canViewCodes = auth()->user()->isAdmin() || auth()->user()->can_view_voucher_code;
                @endphp
                @if(session('info'))
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4" style="border-radius: 12px; background: #cff4fc; color: #055160;">
                    <i class="fas fa-info-circle me-3 fs-4 text-info"></i>
                    <div>{{ session('info') }}</div>
                </div>
                @endif
                <div id="top_zero_quantity_banner" class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4 {{ $availableCount > 0 ? 'd-none' : '' }}" style="border-radius: 12px; background: #fff2f2; color: #b02a37;">
                    <i class="fas fa-ban me-3 fs-4 text-danger"></i>
                    <div>
                        <strong class="d-block mb-1"><i class="fas fa-exclamation-triangle me-1"></i> Stock Quantity is 0 &mdash; Update Voucher Stock Disabled</strong>
                        This voucher currently has <strong>0 available codes</strong>. A voucher cannot be created or saved with 0 quantity. Please scroll down to <strong>Voucher Codes</strong>, enter or upload at least 1 valid code, and the <strong>Update Voucher Stock</strong> button will be automatically enabled.
                    </div>
                </div>
                @if(isset($ordersCount) && $ordersCount > 0)
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4" style="border-radius: 12px; background: #fff3cd; color: #664d03;">
                    <i class="fas fa-lock me-3 fs-4 text-warning"></i>
                    <div>
                        <strong class="d-block mb-1"><i class="fas fa-shield-alt me-1"></i> Accounting Integrity Lock Active</strong>
                        This voucher is linked to <strong>{{ $ordersCount }} order(s)</strong>.
                        To protect audit history, financial reports, and invoicing consistency, the <strong>SKU ID</strong>, <strong>Purchase Currency</strong>, and <strong>Sale Currency</strong> cannot be altered.
                    </div>
                </div>
                @endif
                <div class="row g-4">
                    <!-- Basic Info -->
                    <div class="col-12">
                        <h5 class="fw-bold mb-3 text-primary">Basic Information</h5>
                        <hr>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase d-flex justify-content-between">
                            <span>SKU ID</span>
                            @if(isset($ordersCount) && $ordersCount > 0)
                                <span class="badge bg-danger-subtle text-danger"><i class="fas fa-lock me-1"></i> Locked</span>
                            @endif
                        </label>
                        <input type="text" name="sku_id" id="sku_id" class="form-control bg-light @error('sku_id') is-invalid @enderror" value="{{ old('sku_id', $inventory->sku_id) }}" readonly required>
                        @error('sku_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Brand Name</label>
                        <input type="text" name="brand_name" id="brand_name" class="form-control" value="{{ old('brand_name', $inventory->brand_name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Country/Region</label>
                        <select name="country_region[]" id="country" class="form-select select2" multiple required>
                            <option value="all" {{ (is_array($inventory->country_region) && in_array('all', $inventory->country_region)) ? 'selected' : '' }}>All Countries (GLB)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">State</label>
                        <select name="state[]" id="state" class="form-select select2-state" multiple required>
                            <option value="all" {{ (is_array($inventory->state) && in_array('all', $inventory->state)) ? 'selected' : '' }}>All States</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">City</label>
                        <select name="city[]" id="city" class="form-select select2-city" multiple>
                            <option value="all" {{ (is_array($inventory->city) && in_array('all', $inventory->city)) ? 'selected' : '' }}>All Cities</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Voucher Variant</label>
                        <input type="text" name="voucher_variant" id="voucher_variant" class="form-control" value="{{ old('voucher_variant', $inventory->voucher_variant) }}" placeholder="e.g. Subscription">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Voucher Type</label>
                        <input name="voucher_type" class="form-control" value="{{ old('voucher_type', $inventory->voucher_type) }}" required>


                    </div>
                    <div class=" col-md-6">
                        <label class="form-label small fw-bold text-uppercase">Logo Image</label>
                        <div class="d-flex gap-3 align-items-center">
                            @if($inventory->logo)
                            <img src="{{ $inventory->logo }}" alt="Logo" style="width: 40px; height: 40px; border-radius: 8px; object-fit: contain; background: #f8f9fa;">
                            @endif
                            <input type="file" name="logo" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <!-- Purchase Details -->
                    <div class="col-12 mt-5">
                        <h5 class="fw-bold mb-3 text-primary">Purchase & Stock Details</h5>
                        <hr>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Purchase Invoice No.</label>
                        <input type="text" name="purchase_invoice_no" class="form-control" value="{{ old('purchase_invoice_no', $inventory->purchase_invoice_no) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $inventory->purchase_date ? $inventory->purchase_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date', $inventory->expiry_date ? $inventory->expiry_date->format('Y-m-d') : '') }}">
                        @if($inventory->is_expired)
                        <small class="text-danger fw-bold mt-2 d-block">
                            <i class="fas fa-exclamation-circle me-1"></i>This voucher is EXPIRED
                        </small>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase d-flex justify-content-between align-items-center">
                            <span>Quantity (Available)</span>
                            <span id="qty_zero_badge" class="badge bg-danger-subtle text-danger {{ $availableCount > 0 ? 'd-none' : '' }}">0 Available</span>
                        </label>
                        <input type="number" name="quantity" class="form-control bg-light" value="{{ $availableCount }}" readonly required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="IN STOCK" {{ $inventory->status == 'IN STOCK' ? 'selected' : '' }}>IN STOCK</option>

                            <option value="OUT OF STOCK" {{ $inventory->status == 'OUT OF STOCK' ? 'selected' : '' }}>OUT OF STOCK</option>
                        </select>
                    </div>
                    @php
                        $initPurchaseRate = (float)($inventory->currency_conversion_rate ?: \App\Services\CurrencyService::getRate($inventory->local_currency ?? 'USD'));
                        $initSaleRate = (float)\App\Services\CurrencyService::getRate($inventory->currency ?? 'PKR');
                        $initPurchaseValUsd = (float)($inventory->purchase_value ?? 0) * $initPurchaseRate;
                        $initPurchaseUnitUsd = (float)($inventory->purchase_value_per_unit ?? 0) * $initPurchaseRate;
                        $initAgentPriceUsd = (float)($inventory->agent_sale_price ?? 0) * $initSaleRate;
                        $initStudentPriceUsd = (float)($inventory->student_sale_price ?? 0) * $initSaleRate;
                    @endphp
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase d-flex align-items-center justify-content-between">
                            <span>Purchase Value</span>
                            <span id="purchase_val_usd_bracket" class="text-primary fw-normal">(${{ number_format($initPurchaseValUsd, 2) }} USD)</span>
                        </label>
                        <input type="number" step="0.01" name="purchase_value" class="form-control" value="{{ old('purchase_value', $inventory->purchase_value) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase d-flex align-items-center justify-content-between">
                            <span>Purchase Value / Unit</span>
                            <span id="purchase_unit_usd_bracket" class="text-primary fw-normal">(${{ number_format($initPurchaseUnitUsd, 2) }} USD)</span>
                        </label>
                        <input type="number" step="0.01" name="purchase_value_per_unit" class="form-control bg-light" value="{{ old('purchase_value_per_unit', $inventory->purchase_value_per_unit) }}" readonly required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Taxes</label>
                        <input type="number" step="0.01" name="taxes" class="form-control" value="{{ old('taxes', $inventory->taxes) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Bank Name</label>
                        <input type="text" name="bank" class="form-control" value="{{ old('bank', $inventory->bank) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase d-flex justify-content-between">
                            <span>Purchase Currency</span>
                            @if(isset($ordersCount) && $ordersCount > 0)
                                <span class="badge bg-secondary-subtle text-secondary"><i class="fas fa-lock me-1"></i> Locked</span>
                            @endif
                        </label>
                        @if(isset($ordersCount) && $ordersCount > 0)
                            <input type="hidden" name="local_currency" id="local_currency" value="{{ $inventory->local_currency ?? 'USD' }}">
                            <select class="form-select bg-light" disabled>
                                @foreach($currencies ?? \App\Helpers\CurrencyHelper::getAllCurrencies() as $code => $c)
                                    <option value="{{ $code }}" {{ ($inventory->local_currency ?? 'USD') == $code ? 'selected' : '' }}>
                                        {{ $code }} - {{ $c['name'] }} ({{ $c['symbol'] }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <select name="local_currency" id="local_currency" class="form-select @error('local_currency') is-invalid @enderror">
                                <option value="">-- Select Currency --</option>
                                @foreach($currencies ?? \App\Helpers\CurrencyHelper::getAllCurrencies() as $code => $c)
                                    <option value="{{ $code }}" {{ old('local_currency', $inventory->local_currency ?? 'USD') == $code ? 'selected' : '' }}>
                                        {{ $code }} - {{ $c['name'] }} ({{ $c['symbol'] }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
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
                        <input type="hidden" name="currency_conversion_rate" id="conversion_rate" value="{{ old('currency_conversion_rate', $inventory->currency_conversion_rate ?? 1.0) }}">
                        <div class="small mt-1 text-muted d-flex align-items-center justify-content-between">
                            <span>Admin Rate: <strong id="admin_rate_badge">{{ number_format($inventory->currency_conversion_rate ?? 1.0, 6) }}</strong></span>
                            <span class="badge bg-secondary-subtle text-secondary" id="rate_ratio_label">1 {{ $inventory->local_currency ?: 'USD' }} = {{ number_format($inventory->currency_conversion_rate ?? 1.0, 6) }} {{ $baseCurrency ?? 'USD' }}</span>
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
                        <label class="form-label small fw-bold text-uppercase text-primary d-flex align-items-center justify-content-between" style="min-height: 20px;">
                            <span>Sale Currency</span>
                            @if(isset($ordersCount) && $ordersCount > 0)
                                <span class="badge bg-secondary-subtle text-secondary"><i class="fas fa-lock me-1"></i> Locked</span>
                            @endif
                        </label>
                        @if(isset($ordersCount) && $ordersCount > 0)
                            <input type="hidden" name="currency" id="currency" value="{{ $inventory->currency ?? 'PKR' }}">
                            <select class="form-select bg-light" disabled>
                                @foreach($currencies ?? \App\Helpers\CurrencyHelper::getAllCurrencies() as $code => $c)
                                    <option value="{{ $code }}" {{ ($inventory->currency ?? 'PKR') == $code ? 'selected' : '' }}>
                                        {{ $code }} - {{ $c['name'] }} ({{ $c['symbol'] }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <select name="currency" id="currency" class="form-select border-primary @error('currency') is-invalid @enderror" required>
                                <option value="">-- Select Currency --</option>
                                @foreach($currencies ?? \App\Helpers\CurrencyHelper::getAllCurrencies() as $code => $c)
                                    <option value="{{ $code }}" {{ old('currency', $inventory->currency ?? 'PKR') == $code ? 'selected' : '' }}>
                                        {{ $code }} - {{ $c['name'] }} ({{ $c['symbol'] }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <div id="sale_currency_error" class="text-danger small mt-1 fw-semibold {{ $errors->has('currency') ? '' : 'd-none' }}">
                            <i class="fas fa-exclamation-triangle me-1"></i> <span id="sale_error_msg">{{ $errors->first('currency') ?? 'First set currency in Currency Rates' }}</span>
                            <a href="{{ route('settings.exchange-rates') }}" target="_blank" class="ms-1 fw-bold text-danger text-decoration-underline">Currency Rates &rarr;</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-success d-flex align-items-center justify-content-between" style="min-height: 20px;">
                            <span>Agent / Reseller Price (Per Unit)</span>
                            <span id="agent_price_usd_bracket" class="fw-normal text-success">(${{ number_format($initAgentPriceUsd, 2) }} USD)</span>
                        </label>
                        <input type="number" step="0.01" name="agent_sale_price" class="form-control border-success" value="{{ old('agent_sale_price', $inventory->agent_sale_price) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-success d-flex align-items-center justify-content-between" style="min-height: 20px;">
                            <span>Student Sale Price (Per Unit)</span>
                            <span id="student_price_usd_bracket" class="fw-normal text-success">(${{ number_format($initStudentPriceUsd, 2) }} USD)</span>
                        </label>
                        <input type="number" step="0.01" name="student_sale_price" class="form-control border-success" value="{{ old('student_sale_price', $inventory->student_sale_price) }}" required>
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
                                        <input type="number" name="referral_points_reseller" class="form-control border-start-0 bg-white" value="{{ old('referral_points_reseller', $inventory->referral_points_reseller) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-secondary">Agent Ref. Points / Unit</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-user-tag text-muted"></i></span>
                                        <input type="number" name="agent_referral_points_per_unit" class="form-control border-start-0 bg-white" value="{{ old('agent_referral_points_per_unit', $inventory->agent_referral_points_per_unit) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-secondary">Agent Bonus Points / Unit</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-star text-warning"></i></span>
                                        <input type="number" name="agent_bonus_points_per_unit" class="form-control border-start-0 bg-white" value="{{ old('agent_bonus_points_per_unit', $inventory->agent_bonus_points_per_unit) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-secondary">Student Ref. Points / Unit</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-user-graduate text-muted"></i></span>
                                        <input type="number" name="student_referral_points_per_unit" class="form-control border-start-0 bg-white" value="{{ old('student_referral_points_per_unit', $inventory->student_referral_points_per_unit) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-secondary">Student Bonus Points / Unit</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-award text-warning"></i></span>
                                        <input type="number" name="student_bonus_points_per_unit" class="form-control border-start-0 bg-white" value="{{ old('student_bonus_points_per_unit', $inventory->student_bonus_points_per_unit) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-uppercase d-flex align-items-center justify-content-between">
                                    <span>Voucher Codes (Remaining: {{ count($remainingCodes) }})</span>
                                    <span id="voucher_codes_badge" class="badge bg-secondary-subtle text-secondary">+0 new codes</span>
                                </label>
                                <textarea name="upload_vouchers" id="upload_vouchers" class="form-control @error('upload_vouchers') is-invalid @enderror" rows="6" placeholder="Add more voucher codes here (separated by commas, spaces, or newlines)...">{{ old('upload_vouchers') }}</textarea>
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
                                <div class="form-text">Note: Entered codes will be appended to existing ones.</div>
                                
                                <div class="mt-3 p-3 bg-light rounded shadow-sm border" style="max-height: 200px; overflow-y: auto;">
                                    <h6 class="fw-bold small text-uppercase mb-2 text-success">Available Codes in Database:</h6>
                                    @if($canViewCodes)
                                        @forelse($remainingCodes as $code)
                                            <span class="badge bg-white text-dark border mb-1 me-1">{{ $code }}</span>
                                        @empty
                                            <p class="text-muted small mb-0">No available codes.</p>
                                        @endforelse
                                    @else
                                        <p class="text-danger small mb-0"><i class="fas fa-lock me-1"></i> You don't have permission to view voucher codes.</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-uppercase text-muted">Delivered Codes (Used: {{ count($deliveredCodes) }})</label>
                                <div class="p-3 bg-light rounded shadow-sm border" style="max-height: 350px; overflow-y: auto;">
                                    @if($canViewCodes)
                                        @forelse($deliveredCodes as $code)
                                            <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                                                <span class="small">{{ $code }}</span>
                                                <span class="badge bg-secondary-soft text-secondary tiny-text">DELIVERED</span>
                                            </div>
                                        @empty
                                            <p class="text-muted small mb-0">No codes delivered yet.</p>
                                        @endforelse
                                    @else
                                        <p class="text-danger small mb-0"><i class="fas fa-lock me-1"></i> You don't have permission to view voucher codes.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <hr>
                        <div id="zero_quantity_alert" class="alert alert-warning text-start py-2 px-3 mb-3 {{ $availableCount > 0 ? 'd-none' : '' }}" style="border-radius: 10px;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2 fs-5 text-warning"></i>
                                <div>
                                    <strong>Update Voucher Stock Disabled (0 Quantity):</strong>
                                    <span>A voucher cannot be created or saved with 0 quantity. Please upload at least 1 voucher code in the <strong>Voucher Codes</strong> section above to enable the "Update Voucher Stock" button.</span>
                                </div>
                            </div>
                        </div>
                        @if(($isDraftEmpty ?? false) || ($availableCount == 0 && count($allCodes) == 0 && ($ordersCount ?? 0) == 0))
                            <a href="{{ route('inventory.discard-draft', $inventory->id) }}" class="btn btn-light rounded-pill px-5 me-2">Cancel</a>
                        @else
                            <a href="{{ route('inventory.index') }}" class="btn btn-light rounded-pill px-5 me-2">Cancel</a>
                        @endif
                        <button type="submit" id="update_voucher_btn" class="btn btn-primary rounded-pill px-5" {{ $availableCount == 0 ? 'disabled' : '' }}>Update Voucher Stock</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    $(document).ready(function() {
        const currentId = "{{ $inventory->id }}";
        
        $('.select2').select2({
            placeholder: "Select Country/Region",
            allowClear: true
        });

        $('.select2-state').select2({
            placeholder: "Select State",
            allowClear: true,
            closeOnSelect: false
        });

        $('.select2-city').select2({
            placeholder: "Select City",
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
                let suffix = "001";
                let currentSku = "{{ $inventory->sku_id }}";
                if (currentSku.includes('-')) {
                    let parts = currentSku.split('-');
                    suffix = parts[parts.length - 1];
                }

                // Final SKU: COUNTRY-BRAND-VARIANT-001
                $('#sku_id').val(countryPart + "-" + brandPart + "-" + variantPart + "-" + suffix);
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
            updateConversionCalculation();
        }

        $('input[name="quantity"], input[name="purchase_value"]').on('input', function() {
            calculateUnitValue();
        });

        const initialQty = parseInt($('input[name="quantity"]').val()) || 0;
        const existingCodesInThisVoucher = @json(array_map('strtoupper', $allCodes));
        const otherVouchersMap = @json($existingCodesMap ?? []);
        let voucherCodesValidationState = {
            hasDuplicates: false,
            errorMessage: ''
        };

        function validateVoucherCodes(newCodes) {
            let seen = {};
            let inputDuplicates = [];
            let alreadyInThisVoucher = [];
            let crossConflicts = [];

            for (let code of newCodes) {
                let upper = code.toUpperCase();
                // 1. Input duplicates
                if (seen[upper]) {
                    if (!inputDuplicates.includes(code)) inputDuplicates.push(code);
                } else {
                    seen[upper] = true;
                }

                // 2. Already in this voucher
                if (existingCodesInThisVoucher.includes(upper)) {
                    if (!alreadyInThisVoucher.includes(code)) alreadyInThisVoucher.push(code);
                }

                // 3. In another voucher
                if (otherVouchersMap[upper]) {
                    crossConflicts.push({ code: code, sku: otherVouchersMap[upper] });
                }
            }

            let errors = [];
            if (inputDuplicates.length > 0) {
                let sample = inputDuplicates.slice(0, 5).join(', ');
                if (inputDuplicates.length > 5) sample += ' and ' + (inputDuplicates.length - 5) + ' more';
                errors.push('Duplicate code(s) in input: [' + sample + ']');
            }
            if (alreadyInThisVoucher.length > 0) {
                let sample = alreadyInThisVoucher.slice(0, 5).join(', ');
                if (alreadyInThisVoucher.length > 5) sample += ' and ' + (alreadyInThisVoucher.length - 5) + ' more';
                errors.push('Already in this voucher: [' + sample + ']');
            }
            if (crossConflicts.length > 0) {
                let sampleConflict = crossConflicts.slice(0, 3).map(c => "'" + c.code + "' (in SKU: " + c.sku + ")").join(', ');
                if (crossConflicts.length > 3) sampleConflict += ' and ' + (crossConflicts.length - 3) + ' more';
                errors.push('Already in other voucher: ' + sampleConflict);
            }

            if (errors.length > 0) {
                voucherCodesValidationState.hasDuplicates = true;
                voucherCodesValidationState.errorMessage = errors.join(' | ');
                $('#duplicate_codes_list').text(voucherCodesValidationState.errorMessage);
                $('#duplicate_codes_warning').removeClass('d-none');
                $('#upload_vouchers').addClass('is-invalid');
                $('#voucher_codes_badge').removeClass('bg-secondary-subtle text-secondary bg-success-subtle text-success').addClass('bg-danger-subtle text-danger').text('+' + newCodes.length + ' codes (duplicates detected)');
            } else {
                voucherCodesValidationState.hasDuplicates = false;
                voucherCodesValidationState.errorMessage = '';
                $('#duplicate_codes_warning').addClass('d-none');
                $('#upload_vouchers').removeClass('is-invalid');
                if (newCodes.length > 0) {
                    $('#voucher_codes_badge').removeClass('bg-secondary-subtle text-secondary bg-danger-subtle text-danger').addClass('bg-success-subtle text-success').text('+' + newCodes.length + ' new codes valid');
                } else {
                    $('#voucher_codes_badge').removeClass('bg-success-subtle text-success bg-danger-subtle text-danger').addClass('bg-secondary-subtle text-secondary').text('+0 new codes');
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

            // Calculate quantity based on number of codes
            let codes = cleaned.split(',').map(c => c.trim()).filter(c => c.length > 0);
            let count = codes.length;

            validateVoucherCodes(codes);

            // Update quantity field: initial + new codes
            let newQty = initialQty + count;
            $('input[name="quantity"]').val(newQty);
            if (newQty > 0 && $('select[name="status"]').val() === 'OUT OF STOCK' && initialQty === 0) {
                $('select[name="status"]').val('IN STOCK');
            }
            calculateUnitValue();

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
            let newQty = initialQty + codes.length;
            $('input[name="quantity"]').val(newQty);
            if (newQty > 0 && $('select[name="status"]').val() === 'OUT OF STOCK' && initialQty === 0) {
                $('select[name="status"]').val('IN STOCK');
            }
            calculateUnitValue();
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
            let purchaseCurr = ($('[name="local_currency"]').val() || $('#local_currency').val() || '{{ $inventory->local_currency ?? "USD" }}').trim().toUpperCase();
            let saleCurr = ($('[name="currency"]').val() || $('#currency').val() || '{{ $inventory->currency ?? "PKR" }}').trim().toUpperCase();
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
                let lockedPurchaseRate = {{ ($ordersCount ?? 0) > 0 ? (float) ($inventory->currency_conversion_rate ?? 1.0) : 'null' }};
                let rate = lockedPurchaseRate === null
                    ? (exchangeRates[curr] !== undefined ? parseFloat(exchangeRates[curr]) : 1.0)
                    : lockedPurchaseRate;
                
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

            // 3. Quantity check: vouchers cannot be created or updated with 0 quantity
            let currentQty = parseInt($('input[name="quantity"]').val()) || 0;
            let ordersCount = {{ $ordersCount ?? 0 }};
            let allCodesCount = {{ count($allCodes) }};
            let hasValidQty = currentQty > 0 || (ordersCount > 0 && allCodesCount > 0);

            if (!hasValidQty) {
                $('#zero_quantity_alert').removeClass('d-none');
                $('#top_zero_quantity_banner').removeClass('d-none');
                $('#qty_zero_badge').removeClass('d-none');
            } else {
                $('#zero_quantity_alert').addClass('d-none');
                $('#top_zero_quantity_banner').addClass('d-none');
                $('#qty_zero_badge').addClass('d-none');
            }

            // 4. Submit button state
            let canSubmit = purchaseRateOk && saleRateOk && hasValidQty && !voucherCodesValidationState.hasDuplicates;
            let $btn = $('#update_voucher_btn, button[type="submit"]');
            if (!canSubmit) {
                let reason = 'Please complete all required fields';
                if (!hasValidQty) {
                    reason = 'Cannot update voucher stock with 0 quantity. Please upload voucher codes first.';
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
            let ordersCount = {{ $ordersCount ?? 0 }};
            let allCodesCount = {{ count($allCodes) }};
            let hasValidQty = currentQty > 0 || (ordersCount > 0 && allCodesCount > 0);

            if (!hasValidQty) {
                e.preventDefault();
                alert("Cannot update voucher stock with 0 quantity. Please upload at least 1 voucher code in 'Voucher Codes' first.");
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
        const initialCountries = {!! json_encode($inventory->country_region ?? []) !!};
        const initialState = {!! json_encode($inventory->state ?? []) !!};
        const initialCity = {!! json_encode($inventory->city ?? '') !!};

        // Populate Countries
        const countries = Country.getAllCountries();
        countries.forEach(country => {
            const isSelected = initialCountries.includes(country.name);
            const option = new Option(country.name, country.name, isSelected, isSelected);
            option.dataset.code = country.isoCode;
            countrySelect.append(option);
        });

        function updateStates(countryCode, selectedStates = []) {
            const isAllSelected = selectedStates.includes('all');
            const allOption = new Option('All States', 'all', isAllSelected, isAllSelected);
            stateSelect.empty().append(allOption);
            if (countryCode) {
                const states = State.getStatesOfCountry(countryCode);
                states.forEach(state => {
                    const isSelected = selectedStates.includes(state.name);
                    const option = new Option(state.name, state.name, isSelected, isSelected);
                    option.dataset.code = state.isoCode;
                    stateSelect.append(option);
                });
            }
            stateSelect.trigger('change.select2');
        }

        function updateCities(countryCode, stateCode, selectedCities = []) {
            const isAllSelected = selectedCities.includes('all');
            const allOption = new Option('All Cities', 'all', isAllSelected, isAllSelected);
            citySelect.empty().append(allOption);
            if (countryCode && stateCode) {
                const cities = City.getCitiesOfState(countryCode, stateCode);
                cities.forEach(city => {
                    const isSelected = selectedCities.includes(city.name);
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
                const initialCountryName = initialCountries[0];
                if (selectedCountryName === initialCountryName) {
                    updateStates(countryCode, initialState);
                } else {
                    updateStates(countryCode, ['all']);
                }
            }
        });

        // Trigger initial state
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
                    if (selectedStateName === initialState[0]) {
                        updateCities(countryCode, stateCode, initialCity);
                    } else {
                        updateCities(countryCode, stateCode, ['all']);
                    }
                }
            }
        });
        
        // Trigger state logic initially to handle city population
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