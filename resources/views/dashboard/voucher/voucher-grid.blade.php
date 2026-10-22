<div class="row">
    @forelse($vouchers as $voucher)
    @php
    $user = auth()->user();
    $finalPrice = $voucher->final_price ?? ($user->isStudent() ? $voucher->student_sale_price : $voucher->agent_sale_price);

    $allCodes = is_array($voucher->upload_vouchers) ? $voucher->upload_vouchers : [];
    $deliveredCodes = is_array($voucher->delivered_vouchers) ? $voucher->delivered_vouchers : [];
    $stock = count(array_diff($allCodes, $deliveredCodes));
    $isInStock = ($stock > 0) && ($voucher->status === 'IN STOCK');

    $isExpired = $voucher->expiry_date && $voucher->expiry_date->isPast();

    $countryDisplay = 'N/A';
    if (is_array($voucher->country_region)) {
        if (in_array('all', $voucher->country_region)) {
            $countryDisplay = 'GLB';
        } elseif (count($voucher->country_region) > 1) {
            $countryDisplay = 'MULTY';
        } elseif (count($voucher->country_region) === 1) {
            $countryDisplay = $voucher->country_region[0];
        }
    } else {
        $countryDisplay = $voucher->country_region ?? 'N/A';
    }
    @endphp
    <div class="col-xl-4 col-lg-12 mb-5">
        <div class="gift-voucher-card {{ $isInStock ? '' : 'grayscale' }}">
            <!-- Status & Meta Overlay -->
            <div class="card-status-overlay">
                @if(!$isInStock)
                    <span class="badge bg-danger shadow-sm">OUT OF STOCK</span>
                @endif
                <span class="badge bg-light text-dark shadow-sm ms-1">{{ $countryDisplay }}</span>
            </div>

            <div class="voucher-top">
                <!-- Brand Header -->
                <div class="brand-header">
                  
                  
                  <div class="brand-info">
    <h6 class="text-uppercase fw-bold mb-1">
        {{ $voucher->brand_name }}
    </h6>

    <div class="small text-muted">
        <span class="me-2">
            <i class="fas fa-ticket-alt me-1"></i>
            {{ $voucher->voucher_type ?? 'General Voucher' }}
        </span>

        <span class="me-2">•</span>

        <span>
            <i class="fas fa-layer-group me-1"></i>
            {{ $voucher->voucher_variant ?? 'Standard' }}
        </span>
    </div>

    <div class="small text-secondary mt-1">
        SKU: {{ $voucher->sku_id }}
    </div>
</div>
                    
                </div>

                <!-- Main Titles -->
                <div class="voucher-titles">
                     @if($voucher->logo)
                        <img src="{{ asset($voucher->logo) }}" alt="{{ $voucher->brand_name }}" class="brand-logo-img">
                    @else
                        <div class="brand-logo-img bg-light d-flex align-items-center justify-content-center rounded">
                            <i class="fas fa-ticket-alt text-teal"></i>
                        </div>
                    @endif
                </div>

                <!-- Circular Price Ribbon -->
                <div class="price-ribbon">
                    <span class="currency">{{ $voucher->currency }}</span>
                    <span class="amount">{{ number_format($finalPrice, 0) }}</span>
                    @php
                        $finalPriceUsd = \App\Services\CurrencyService::convertToBase($finalPrice, $voucher->currency)['base_amount'] ?? $finalPrice;
                    @endphp
                    <span class="sub-text">(${{ number_format($finalPriceUsd, 2) }} USD)</span>
                </div>

                <!-- Order Button (Floating) -->
                @if($isInStock && !auth()->user()->isSupport() && !$isExpired)
                    <a href="{{ route('vouchers.order', $voucher->id) }}" class="btn-order-floating">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                @endif
            </div>

            <!-- Footer Section -->
            <div class="voucher-footer">
                <div class="instructions-section">
                    <h6>INSTRUCTIONS:</h6>
                    <div class="instructions-text">
                        This voucher is valid for {{ $voucher->brand_name }} products. 
                        @if($voucher->expiry_date) Exp: {{ $voucher->expiry_date->format('d M Y') }}. @endif
                        @if($voucher->remaining_limit >= 0) 24h Limit: {{ $voucher->is_limited ? 'Reached' : $voucher->remaining_limit . ' left' }}. @endif
                    </div>
                </div>

                <div class="qr-section d-none d-sm-block">
                    <i class="fas fa-qrcode"></i>
                </div>

                <div class="contact-info d-none d-md-flex">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i> <span>Region: {{ $countryDisplay }}</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i> <span>support@unicou.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i> <span>+123-456-7890</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <div class="empty-state">
            <i class="fas fa-search fa-4x text-light mb-3"></i>
            <h4 class="fw-bold">No Vouchers Found</h4>
            <p class="text-muted">Try adjusting your filters.</p>
            <a href="{{ route('vouchers') }}" class="btn btn-primary btn-sm px-4 fw-bold">SHOW ALL</a>
        </div>
    </div>
    @endforelse
</div>

<div class="mt-4">
    @include('dashboard.partials.custom-pagination', ['items' => $vouchers])
</div>