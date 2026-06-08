@extends('layouts.master')
@push('css')
<style>
    .payment-type-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid #eee !important;
    }

    .payment-type-card.active {
        border-color: #0d6efd !important;
        background-color: #f8f9ff;
    }

    .payment-type-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .brand-logo-container {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 12px;
        overflow: hidden;
    }
</style>
@endpush
@section('content')
<script src="https://js.stripe.com/v3/"></script>
<div class="container-fluid py-4">

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Available Store Credit </div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">${{ number_format($userPoints['store_credit'], 0) }}</h3>
                        <span class="ms-auto text-success small fw-bold"><i class="fas fa-arrow-up me-1"></i>12%</span>
                    </div>
                </div>
            </div>
        </div>

        @if(auth()->user()->isRegularAgent() )
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Agent Referral Points </div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0"><span class="referral-points-display" data-base="{{ $voucher->agent_referral_points_per_unit }}">{{ $voucher->agent_referral_points_per_unit }}</span></h3>
                        <span class="ms-auto text-success small fw-bold"><i class="fas fa-arrow-up me-1"></i>12%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Agent Bonus Points </div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0"><span class="bonus-points-display" data-base="{{ $voucher->agent_bonus_points_per_unit }}">{{ $voucher->agent_bonus_points_per_unit }}</span></h3>
                        <span class="ms-auto text-success small fw-bold"><i class="fas fa-arrow-up me-1"></i>12%</span>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if( auth()->user()->isStudent())
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Student Referral Points </div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0"><span class="referral-points-display" data-base="{{ $voucher->student_referral_points_per_unit }}">{{ $voucher->student_referral_points_per_unit }}</span></h3>
                        <span class="ms-auto text-success small fw-bold"><i class="fas fa-arrow-up me-1"></i>12%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Student Bonus Points </div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0"><span class="bonus-points-display" data-base="{{ $voucher->student_bonus_points_per_unit }}">{{ $voucher->student_bonus_points_per_unit }}</span></h3>
                        <span class="ms-auto text-success small fw-bold"><i class="fas fa-arrow-up me-1"></i>12%</span>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if( auth()->user()->isResellerAgent())
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Reseller Referral Points </div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0"><span class="referral-points-display" data-base="{{ $voucher->referral_points_reseller }}">{{ $voucher->referral_points_reseller }}</span></h3>
                        <span class="ms-auto text-success small fw-bold"><i class="fas fa-arrow-up me-1"></i>12%</span>
                    </div>
                </div>
            </div>
        </div>

        @endif
    </div>
    <div class="row">
        <!-- Left Column: Order Summary -->
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <p class="text-muted small text-uppercase fw-bold mb-1">ORDER SUMMARY</p>
                    <h4 class="fw-bold mb-4">Review & Pay For Your Voucher</h4>

                    <!-- Voucher Details Header -->
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-4 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="brand-logo-container me-3">
                                @if($rule->logo)
                                <img src="{{ asset($rule->logo) }}" alt="{{ $rule->brand_name }}" class="img-fluid">
                                @else
                                <i class="fas fa-ticket-alt fa-2x text-primary"></i>
                                @endif
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">{{ $rule->brand_name }}</h5>
                                <p class="text-muted small mb-0">Voucher purchase</p>
                            </div>
                        </div>
                        <div class="quantity-selector d-flex align-items-center bg-light rounded-pill px-2 py-1">
                            <button class="btn btn-sm btn-light rounded-circle shadow-none border-0" id="decrease-qty">
                                <i class="fas fa-minus small text-muted"></i>
                            </button>
                            <input type="number" id="voucher-quantity" class="form-control form-control-sm bg-transparent border-0 text-center fw-bold" value="1" min="1" max="{{ $userPoints['max_allowed'] > 0 ? min($userPoints['max_allowed'], $rule->quantity) : $rule->quantity }}" style="width: 50px;">
                            <button class="btn btn-sm btn-light rounded-circle shadow-none border-0" id="increase-qty">
                                <i class="fas fa-plus small text-muted"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Price Details Table -->
                    <div class="price-details-table mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Price</span>
                            <span class="fw-bold">{{ $rule->currency }} <span id="unit-price">{{ number_format($rule->final_price, 0) }}</span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Quantity</span>
                            <span class="fw-bold" id="display-qty">1</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-bold">{{ $rule->currency }} <span id="subtotal">{{ number_format($rule->final_price, 0) }}</span></span>
                        </div>

                        <!-- Payment Method Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Select Payment Method</label>
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                <!-- <div class="form-check payment-type-card p-3 border rounded-3 flex-fill cursor-pointer active" id="type-card">
                                    <input class="form-check-input d-none" type="radio" name="payment_type" id="payment_card" value="card" checked>
                                    <label class="form-check-label d-flex align-items-center cursor-pointer" for="payment_card">
                                        <i class="fas fa-credit-card me-2 text-primary"></i>
                                        <span>Card Payment</span>
                                    </label>
                                </div> -->
                                <div class="form-check payment-type-card p-3 border rounded-3 flex-fill cursor-pointer" id="type-admin-bank">
                                    <input class="form-check-input d-none" type="radio" name="payment_type" id="payment_admin_bank" value="admin_bank">
                                    <label class="form-check-label d-flex align-items-center cursor-pointer" for="payment_admin_bank">
                                        <i class="fas fa-university me-2 text-success"></i>
                                        <span>Direct Transfer (Admin)</span>
                                    </label>
                                </div>
                              <!--  <div class="form-check payment-type-card p-3 border rounded-3 flex-fill cursor-pointer" id="type-linked-bank">
                                    <input class="form-check-input d-none" type="radio" name="payment_type" id="payment_linked_bank" value="linked_bank">
                                    <label class="form-check-label d-flex align-items-center cursor-pointer" for="payment_linked_bank">
                                        <i class="fas fa-link me-2 text-info"></i>
                                        <span>Linked Account</span>
                                    </label>
                                </div>
                                 -->
                                <div class="form-check payment-type-card p-3 border rounded-3 flex-fill cursor-pointer" id="type-wallet">
                                    <input class="form-check-input d-none" type="radio" name="payment_type" id="payment_wallet" value="wallet">
                                    <label class="form-check-label d-flex align-items-center cursor-pointer" for="payment_wallet">
                                        <i class="fas fa-wallet me-2 text-warning"></i>
                                        <span>Wallet Balance</span>
                                    </label>
                                </div>
                                <!--
                                <div class="form-check payment-type-card p-3 border rounded-3 flex-fill cursor-pointer" id="type-kuickpay">
                                    <input class="form-check-input d-none" type="radio" name="payment_type" id="payment_kuickpay" value="kuickpay">
                                    <label class="form-check-label d-flex align-items-center cursor-pointer" for="payment_kuickpay">
                                        <i class="fas fa-mobile-alt me-2 text-danger"></i>
                                        <span>Kuickpay (Bank/App)</span>
                                    </label>
                                </div>
                                <div class="form-check payment-type-card p-3 border rounded-3 flex-fill cursor-pointer" id="type-stripe">
                                    <input class="form-check-input d-none" type="radio" name="payment_type" id="payment_stripe" value="stripe">
                                    <label class="form-check-label d-flex align-items-center cursor-pointer" for="payment_stripe">
                                        <i class="fab fa-stripe me-2 text-primary"></i>
                                        <span>Stripe (Credit Card)</span>
                                    </label>
                                </div>
                                 -->
                            </div>

                            <!-- Kuickpay Section -->
                            <div id="kuickpay-section" class="d-none mb-4">
                                <div class="card bg-light border-0 rounded-4 p-4 shadow-sm border-start border-danger border-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-danger text-white rounded-circle p-2 me-3">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <h5 class="fw-bold mb-0">Pay via Kuickpay</h5>
                                    </div>
                                    <p class="text-muted small">You will receive a unique consumer number after placing the order. You can pay this through any Bank App, ATM, or Retailer.</p>
                                    <ul class="text-muted small ps-3">
                                        <li>Login to your Bank App</li>
                                        <li>Go to Bill Payments -> Kuickpay</li>
                                        <li>Enter the Consumer Number (provided after clicking Place Order)</li>
                                        <li>Your order will be automatically confirmed once paid</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Stripe Section -->
                            <div id="stripe-section" class="d-none mb-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Secure Card Payment</label>
                                <div class="card border-0 bg-light rounded-4 p-4 shadow-sm">
                                    <div id="card-element" class="p-3 bg-white rounded-3 shadow-sm">
                                        <!-- Stripe Elements will be injected here -->
                                    </div>
                                    <div id="card-errors" role="alert" class="text-danger small mt-2"></div>
                                </div>
                            </div>

                            <!-- Linked Bank Section -->
                            <div id="linked-bank-section" class="d-none mb-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Choose Linked Bank Account</label>
                                <select id="linked-bank-selector" class="form-select border-0 bg-light p-3" style="border-radius: 12px;">
                                    <option value="">Select your account...</option>
                                    @foreach($banks as $lbank)
                                    <option value="{{ $lbank->id }}">{{ $lbank->bank_name }} ({{ $lbank->account_number }}) - Bal: {{ auth()->user()->currency }} {{ number_format($lbank->balance, 2) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Wallet Section -->
                            <div id="wallet-section" class="d-none mb-4">
                                <div class="card bg-light border-0 rounded-4 p-4 shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted small mb-1">Your Wallet Balance</p>
                                            <h3 class="fw-bold mb-0 text-primary">USD {{ number_format(auth()->user()->wallet_balance, 2) }}</h3>
                                        </div>
                                        <i class="fas fa-wallet fa-3x opacity-25"></i>
                                    </div>
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="text-muted small mb-1">Total Payable in USD</p>
                                                <h4 class="fw-bold mb-0 text-warning">USD <span id="total-usd">0.00</span></h4>
                                            </div>
                                            <div class="text-end">
                                                <p class="text-muted small mb-1">Live Rate</p>
                                                <p class="fw-bold mb-0 small">1 {{ $rule->currency }} = <span id="live-rate">...</span> USD</p>
                                            </div>
                                        </div>
                                        <p class="text-muted extra-small mt-1 mb-0">*Live conversion using real-time exchange rates</p>
                                    </div>
                                    <div id="wallet-insufficient" class="alert alert-danger mt-3 py-2 small d-none">
                                        Insufficient balance in your wallet. (Required: USD <span class="required-usd-text">0.00</span>)
                                    </div>
                                </div>
                            </div>

                            <!-- Card Payment Section -->
                            <div id="card-payment-section" class="d-none">
                                <label class="form-label fw-bold small text-uppercase text-muted">Card Details</label>
                                <div class="card border-0 bg-light rounded-4 p-4 shadow-sm">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label small fw-bold">Card Number</label>
                                            <input type="text" id="card-number" class="form-control border-0 p-3 shadow-sm" placeholder="0000 0000 0000 0000" style="border-radius: 10px;">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Expiry Date</label>
                                            <input type="text" id="card-expiry" class="form-control border-0 p-3 shadow-sm" placeholder="MM/YY" style="border-radius: 10px;">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">CVV</label>
                                            <input type="text" id="card-cvv" class="form-control border-0 p-3 shadow-sm" placeholder="123" style="border-radius: 10px;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Admin Banks Section -->
                            <div id="admin-banks-section" class="d-none">
                                <label class="form-label fw-bold small text-uppercase text-muted">Select Admin Bank To Pay</label>
                                <select id="admin-bank-selector" class="form-select border-0 bg-light p-3 mb-3" style="border-radius: 12px;">
                                    <option value="">Choose admin's bank account...</option>
                                    @foreach($adminBanks as $abank)
                                    <option value="{{ $abank->id }}"
                                        data-name="{{ $abank->bank_name }}"
                                        data-holder="{{ $abank->account_holder_name }}"
                                        data-number="{{ $abank->account_number }}"
                                        data-ifsc="{{ $abank->ifsc_code }}"
                                        data-swift="{{ $abank->swift_code }}"
                                        data-routing="{{ $abank->routing_number }}"
                                        data-upi="{{ $abank->upi_id }}"
                                        data-qr="{{ $abank->qr_code ? asset('storage/'.$abank->qr_code) : '' }}"
                                        data-notes="{{ $abank->notes }}">
                                        {{ $abank->bank_name }} ({{ $abank->country ?: 'Global' }})
                                    </option>
                                    @endforeach
                                </select>

                                <!-- Admin Bank Details Display -->
                                <div id="admin-bank-details" class="card bg-light border-0 rounded-4 p-3 mb-3 d-none">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <p class="small text-muted mb-0">Bank Name</p>
                                            <p class="fw-bold mb-2" id="detail-bank-name"></p>
                                            <p class="small text-muted mb-0">Account Holder</p>
                                            <p class="fw-bold mb-2" id="detail-holder"></p>
                                            <p class="small text-muted mb-0">Account Number / IBAN</p>
                                            <p class="fw-bold mb-2" id="detail-number"></p>
                                        </div>
                                        <div class="col-md-6" id="detail-codes">
                                            <!-- Codes will be injected here -->
                                        </div>
                                        <div class="col-12 d-none" id="detail-qr-container">
                                            <p class="small text-muted mb-0">QR Code</p>
                                            <img id="detail-qr" src="" class="img-fluid rounded border mt-2" style="max-height: 150px;">
                                        </div>
                                        <div class="col-12 d-none" id="detail-notes-container">
                                            <p class="small text-muted mb-1">Instructions:</p>
                                            <div class="alert alert-info py-2 px-3 small mb-0" id="detail-notes"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transaction Submission Form (Initially Hidden) -->
                                <div id="admin-transfer-form" class="mt-4 d-none">
                                    <div class="alert alert-primary border-0 shadow-sm mb-4 d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas fa-info-circle fa-2x"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 small fw-bold">REQUIRED TRANSFER AMOUNT</p>
                                            <h4 class="fw-bold mb-0">{{ $rule->currency }} <span class="required-amount-text">0.00</span></h4>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-uppercase text-muted">Upload Payment Receipt / Screenshot</label>
                                        <input type="file" id="payment-receipt" class="form-control border-0 bg-light p-3" style="border-radius: 12px;" accept="image/*">
                                        <div class="form-text text-muted">Upload the screenshot of your successful transaction.</div>

                                        <!-- OCR Progress Bar -->
                                        <div id="ocr-progress" class="mt-3 d-none">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small id="ocr-status" class="text-primary fw-bold">Processing receipt...</small>
                                                <small id="ocr-percentage" class="text-muted">0%</small>
                                            </div>
                                            <div class="progress" style="height: 10px; border-radius: 5px;">
                                                <div id="ocr-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card border-0 bg-light rounded-4 p-4 shadow-sm">
                                        <h6 class="fw-bold mb-4 small text-uppercase text-primary"><i class="fas fa-edit me-2"></i>Manual Transaction Details</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Transaction ID / Reference No.</label>
                                                <input type="text" id="transaction-id" class="form-control border-0 p-3 shadow-sm" placeholder="Enter transaction ID" style="border-radius: 10px;">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Sender Account Holder Name</label>
                                                <input type="text" id="account-holder-name" class="form-control border-0 p-3 shadow-sm" placeholder="Name on your bank account" style="border-radius: 10px;">
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label small fw-bold">Exact Amount Transferred</label>
                                                <div class="input-group">
                                                    <span class="input-group-text border-0 bg-white shadow-sm" style="border-radius: 10px 0 0 10px;">{{ $rule->currency }}</span>
                                                    <input type="number" id="transfer-amount" class="form-control border-0 p-3 shadow-sm" placeholder="0.00" style="border-radius: 0 10px 10px 0;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total & Actions -->
                    <div class="d-flex justify-content-between align-items-center mb-4 pt-4 border-top">
                        <div>
                            <p class="text-muted small mb-0">Total to pay</p>
                            <h3 class="fw-bold text-primary mb-0">{{ $rule->currency }} <span id="final-total">{{ number_format($rule->final_price, 0) }}</span></h3>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('vouchers') }}" class="btn btn-light border-0 px-4 rounded-3 fw-bold">Cancel</a>
                            <button class="btn btn-primary px-5 rounded-3 fw-bold shadow-sm" id="pay-now">Place Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script>
    $(document).ready(function() {
        let finalPrice = {{ $rule->final_price }};
        let maxQty = {{ $userPoints['max_allowed'] > 0 ? min($userPoints['max_allowed'], $rule->quantity) : $rule->quantity }};
        let capturedDetails = null;

        // Stripe Integration
        const stripe = Stripe('{{ config("services.stripe.key") }}');
        const elements = stripe.elements();
        const card = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                },
            },
        });
        card.mount('#card-element');

        card.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Tesseract OCR Logic
        $('#payment-receipt').on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function() {
                const img = new Image();
                img.src = reader.result;
                img.onload = function() {
                    $('#ocr-progress').removeClass('d-none');
                    $('#ocr-status').text('Scanning receipt...');

                    Tesseract.recognize(
                        img,
                        'eng', {
                            logger: m => {
                                if (m.status === 'recognizing text') {
                                    let prog = Math.round(m.progress * 100);
                                    $('#ocr-bar').css('width', prog + '%');
                                    $('#ocr-percentage').text(prog + '%');
                                }
                            }
                        }
                    ).then(({
                        data: {
                            text
                        }
                    }) => {
                        $('#ocr-progress').addClass('d-none');

                        // Simple regex extraction
                        const txIdMatch = text.match(/(?:Txn ID|Transaction ID|Ref No|Reference|TXN)[:\s]*([A-Z0-9]+)/i);
                        const amountMatch = text.match(/(?:Amount|Total|Paid|Sum)[:\s]*[^\d]*([\d,]+\.?\d*)/i);

                        let data = {
                            raw_text: text,
                            extracted_tx_id: txIdMatch ? txIdMatch[1] : null,
                            extracted_amount: amountMatch ? amountMatch[1] : null,
                            captured_at: new Date().toISOString()
                        };

                        capturedDetails = JSON.stringify(data);

                        if (data.extracted_tx_id) {
                            $('#transaction-id').val(data.extracted_tx_id);
                            toastr.success('Transaction ID captured!');
                        }
                        if (data.extracted_amount) {
                            $('#transfer-amount').val(data.extracted_amount.replace(/,/g, ''));
                            toastr.success('Amount captured!');
                        }
                    }).catch(err => {
                        console.error(err);
                        $('#ocr-progress').addClass('d-none');
                        toastr.warning('Could not auto-capture details. Please enter manually.');
                    });
                };
            };
            reader.readAsDataURL(file);
        });

        function updateTotal() {
            let qty = parseInt($('#voucher-quantity').val()) || 1;
            let total = finalPrice * qty;
            $('#display-qty').text(qty);
            $('#subtotal').text(total.toLocaleString());
            $('#final-total').text(total.toLocaleString());
            $('.required-amount-text').text(total.toLocaleString());

            // USD Conversion logic for wallet
            let currency = "{{ $rule->currency }}";
            if (currency !== 'USD') {
                $.get("{{ route('currency.convert') }}", {
                    amount: total,
                    from: currency,
                    to: 'USD'
                }, function(data) {
                    if (data.success) {
                        $('#total-usd').text(data.converted.toFixed(2));
                        $('.required-usd-text').text(data.converted.toFixed(2));
                        $('#live-rate').text(data.rate.toFixed(4));
                        
                        let balance = {{ auth()->user()->wallet_balance }};
                        if (data.converted > balance) {
                            $('#wallet-insufficient').removeClass('d-none');
                        } else {
                            $('#wallet-insufficient').addClass('d-none');
                        }
                    }
                });
            } else {
                $('#total-usd').text(total.toFixed(2));
                $('.required-usd-text').text(total.toFixed(2));
                $('#live-rate').text('1.0000');
                
                let balance = {{ auth()->user()->wallet_balance }};
                if (total > balance) {
                    $('#wallet-insufficient').removeClass('d-none');
                } else {
                    $('#wallet-insufficient').addClass('d-none');
                }
            }

            // Auto fill transfer amount
            $('#transfer-amount').val(total);

            // Update Points Display
            $('.referral-points-display').each(function() {
                let base = parseInt($(this).data('base')) || 0;
                $(this).text(base * qty);
            });
            $('.bonus-points-display').each(function() {
                let base = parseInt($(this).data('base')) || 0;
                $(this).text(base * qty);
            });
        }

        // Initial call
        updateTotal();
        $('.payment-type-card.active').trigger('click');

        $('#increase-qty').click(function() {
            let qty = parseInt($('#voucher-quantity').val());
            if (qty < maxQty) {
                $('#voucher-quantity').val(qty + 1);
                updateTotal();
            } else {
                if (qty >= {{ $userPoints['max_allowed'] }}) {
                    toastr.warning('Your 24-hour voucher purchase limit has been reached.');
                } else {
                    toastr.warning('Only this many vouchers are available in stock..');
                }
            }
        });

        $('#decrease-qty').click(function() {
            let qty = parseInt($('#voucher-quantity').val());
            if (qty > 1) {
                $('#voucher-quantity').val(qty - 1);
                updateTotal();
            }
        });

        $('#voucher-quantity').on('change input', function() {
            let val = parseInt($(this).val());
            if (isNaN(val) || val < 1) $(this).val(1);
            if (val > maxQty) $(this).val(maxQty);
            updateTotal();
        });

        // Payment Type Toggle
        $('.payment-type-card').on('click', function(e) {
            $('.payment-type-card').removeClass('active');
            $(this).addClass('active');

            let radio = $(this).find('input[type="radio"]');
            radio.prop('checked', true);

            let type = radio.val();

            // Reset visibility
            $('#card-payment-section').addClass('d-none');
            $('#admin-banks-section').addClass('d-none');
            $('#linked-bank-section').addClass('d-none');
            $('#wallet-section').addClass('d-none');
            $('#wallet-insufficient').addClass('d-none');
            $('#kuickpay-section').addClass('d-none');
            $('#stripe-section').addClass('d-none');

            if (type === 'card') {
                $('#card-payment-section').removeClass('d-none');
            } else if (type === 'admin_bank') {
                $('#admin-banks-section').removeClass('d-none');
                if (!$('#admin-bank-selector').val()) {
                    $('#admin-bank-selector').find('option:eq(1)').prop('selected', true).trigger('change');
                } else {
                    $('#admin-bank-selector').trigger('change');
                }
            } else if (type === 'linked_bank') {
                $('#linked-bank-section').removeClass('d-none');
            } else if (type === 'wallet') {
                $('#wallet-section').removeClass('d-none');
                updateTotal(); // Ensure conversion and check are up to date
            } else if (type === 'kuickpay') {
                $('#kuickpay-section').removeClass('d-none');
            } else if (type === 'stripe') {
                $('#stripe-section').removeClass('d-none');
            }
        });

        // Admin Bank Detail Update
        $('#admin-bank-selector').change(function() {
            let selected = $(this).find(':selected');
            if (!selected.val()) {
                $('#admin-bank-details').addClass('d-none');
                $('#admin-transfer-form').addClass('d-none');
                return;
            }

            $('#detail-bank-name').text(selected.data('name'));
            $('#detail-holder').text(selected.data('holder'));
            $('#detail-number').text(selected.data('number'));

            let codesHtml = '';
            if (selected.data('ifsc')) codesHtml += `<p class="small text-muted mb-0">IFSC (India)</p><p class="fw-bold mb-2">${selected.data('ifsc')}</p>`;
            if (selected.data('swift')) codesHtml += `<p class="small text-muted mb-0">SWIFT/BIC</p><p class="fw-bold mb-2">${selected.data('swift')}</p>`;
            if (selected.data('routing')) codesHtml += `<p class="small text-muted mb-0">Routing No.</p><p class="fw-bold mb-2">${selected.data('routing')}</p>`;
            if (selected.data('upi')) codesHtml += `<p class="small text-muted mb-0">UPI ID</p><p class="fw-bold mb-2 text-success">${selected.data('upi')}</p>`;
            $('#detail-codes').html(codesHtml);

            if (selected.data('qr')) {
                $('#detail-qr').attr('src', selected.data('qr'));
                $('#detail-qr-container').removeClass('d-none');
            } else {
                $('#detail-qr-container').addClass('d-none');
            }

            if (selected.data('notes')) {
                $('#detail-notes').text(selected.data('notes'));
                $('#detail-notes-container').removeClass('d-none');
            } else {
                $('#detail-notes-container').addClass('d-none');
            }

            $('#admin-bank-details').removeClass('d-none');
            $('#admin-transfer-form').removeClass('d-none');
        });

        $('#pay-now').click(function() {
            let paymentType = $('input[name="payment_type"]:checked').val();
            let qtyElement = $('#voucher-quantity');
            let qty = parseInt(qtyElement.val()) || 1;
            let btn = $(this);

            console.log('🔵 Pay Now clicked');
            console.log('Payment Type:', paymentType);
            console.log('Quantity Raw:', qtyElement.val());
            console.log('Quantity Parsed:', qty);
            console.log('Quantity Type:', typeof qty);

            if (!paymentType) {
                toastr.error('Please select a payment method');
                return;
            }

            if (qty < 1) {
                toastr.error('Please enter a valid quantity');
                return;
            }

            let formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('quantity', qty);
            formData.append('payment_type', paymentType);

            if (paymentType === 'card') {
                let cardNumber = $('#card-number').val();
                let cardExpiry = $('#card-expiry').val();
                let cardCvv = $('#card-cvv').val();

                if (!cardNumber || !cardExpiry || !cardCvv) {
                    toastr.error('Please enter all card details');
                    return;
                }

                let cardDetails = {
                    card_number: cardNumber,
                    card_expiry: cardExpiry,
                    card_cvv: cardCvv
                };
                formData.append('captured_details', JSON.stringify(cardDetails));
            } else if (paymentType === 'admin_bank') {
                let adminBankId = $('#admin-bank-selector').val();
                let receiptFile = $('#payment-receipt')[0].files[0];

                if (!adminBankId) {
                    toastr.error("Please select admin's bank for payment");
                    return;
                }
                if (!receiptFile) {
                    toastr.error('Please upload payment receipt screenshot');
                    return;
                }

                formData.append('admin_bank_id', adminBankId);
                formData.append('payment_receipt', receiptFile);
                formData.append('transaction_id', $('#transaction-id').val());
                formData.append('account_holder_name', $('#account-holder-name').val());
                formData.append('transfer_amount', $('#transfer-amount').val());
                if (capturedDetails) {
                    formData.append('captured_details', capturedDetails);
                }
            } else if (paymentType === 'linked_bank') {
                let bankId = $('#linked-bank-selector').val();
                if (!bankId) {
                    toastr.error('Please select a linked bank account');
                    return;
                }
                formData.append('linked_bank_id', bankId);
            } else if (paymentType === 'wallet') {
                let totalUSD = parseFloat($('#total-usd').text()) || 0;
                let balance = {{ auth()->user()->wallet_balance }};
                if (totalUSD > balance) {
                    toastr.error('Insufficient wallet balance in USD');
                    return;
                }
            } else if (paymentType === 'stripe') {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...');

                stripe.createToken(card).then(function(result) {
                    if (result.error) {
                        btn.prop('disabled', false).html('Place Order');
                        $('#card-errors').text(result.error.message);
                    } else {
                        formData.append('stripeToken', result.token.id);
                        submitOrder(formData, btn);
                    }
                });
                return; // Exit here as submitOrder will be called asynchronously
            }

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...');
            submitOrder(formData, btn);
        });

        function submitOrder(formData, btn) {
            $.ajax({
                url: "{{ route('vouchers.order.post', $rule->id) }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (formData.get('payment_type') === 'kuickpay') {
                            $('#kp-consumer-number').text(response.consumer_number);
                            $('#kuickpaySuccessModal').modal('show');
                        } else {
                            window.location.href = "{{ route('orders.history') }}";
                        }
                    }
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                    toastr.error(msg);
                    btn.prop('disabled', false).html('Place Order');
                }
            });
        }
    });
</script>
@endpush

<!-- Kuickpay Success Modal -->
<div class="modal fade" id="kuickpaySuccessModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-5 text-center">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success fa-5x"></i>
                </div>
                <h3 class="fw-bold mb-3">Order Placed!</h3>
                <p class="text-muted mb-4">Please pay the following amount using Kuickpay to confirm your order.</p>

                <div class="bg-light p-4 rounded-4 mb-4 border-dashed border-2 border-primary">
                    <p class="text-muted small text-uppercase fw-bold mb-2">Kuickpay Consumer Number</p>
                    <h2 class="fw-bold text-primary mb-0" id="kp-consumer-number">---</h2>
                </div>

                <div class="alert alert-info small text-start mb-4">
                    <strong>Instructions:</strong>
                    <ol class="mb-0 mt-2">
                        <li>Login to any Bank App or visit an ATM.</li>
                        <li>Select <strong>Bill Payments</strong> and then <strong>Kuickpay</strong>.</li>
                        <li>Enter the <strong>Consumer Number</strong> above.</li>
                        <li>Pay the total amount and your order will be activated instantly.</li>
                    </ol>
                </div>

                <button type="button" class="btn btn-primary w-100 py-3 rounded-pill fw-bold" onclick="window.location.href='{{ route('orders.history') }}'">
                    Go to Order History
                </button>
            </div>
        </div>
    </div>
</div>

@endsection