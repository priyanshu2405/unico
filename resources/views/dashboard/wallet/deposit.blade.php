@extends('agent.layout.app')

@section('content')
<style>
    .deposit-container {
        max-width: 800px;
        margin: auto;
        padding: 20px;
    }

    .deposit-card {
        background: #fff;
        padding: 35px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: 1px solid #f0f0f0;
    }

    .method-box {
        background: #fff;
        border: 2px solid #d23fa0;
        border-radius: 12px;
        padding: 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        max-width: 350px;
        margin-bottom: 30px;
    }

    .method-box span {
        font-weight: 600;
        color: #333;
    }

    .form-label {
        font-weight: 700;
        color: #444;
        margin-bottom: 10px;
        display: block;
        font-size: 0.95rem;
    }

    .amount-input-wrapper {
        position: relative;
        margin-bottom: 25px;
    }

    .amount-input {
        width: 100%;
        padding: 15px 15px 15px 50px;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        transition: border-color 0.2s;
    }

    .amount-input:focus {
        border-color: #d23fa0;
        outline: none;
    }

    .currency-prefix {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-weight: 700;
        color: #888;
        font-size: 1.1rem;
    }

    .amount-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 12px;
        margin-bottom: 30px;
    }

    .amount-btn {
        padding: 12px;
        border: 1px solid #eee;
        border-radius: 10px;
        background: #fcfcfc;
        font-weight: 600;
        color: #555;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s;
    }

    .amount-btn:hover {
        border-color: #d23fa0;
        background: #fff;
        color: #d23fa0;
    }

    .btn-add-credit {
        background: linear-gradient(90deg, #d23fa0 0%, #a0317d 100%);
        color: #fff;
        border: none;
        padding: 15px;
        border-radius: 10px;
        font-weight: 700;
        width: 100%;
        font-size: 1rem;
        box-shadow: 0 4px 15px rgba(210, 63, 160, 0.3);
    }

    .btn-cancel {
        display: block;
        text-align: center;
        margin-top: 15px;
        color: #888;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
    }
</style>

<div class="deposit-container">
    <div class="d-flex align-items-center gap-3 mb-4">
        <h2 class="fw-bold mb-0">Deposit Store Credit</h2>
        <img src="{{ session('user_flag_url') }}" width="30" class="shadow-sm rounded-sm">
    </div>

    <div class="deposit-card">
        <div class="method-box">
            <i class="fas fa-university text-primary"></i>
            <span>Linked Bank Account</span>
        </div>

        @if($banks->count() > 0)
        <div class="mb-4">
            <label class="form-label">Select Your Bank</label>
            <select class="form-select amount-input" style="padding-left: 15px; height: 55px;">
                @foreach($banks as $bank)
                <option value="{{ $bank->id }}">
                    {{ $bank->bank_name }} (****{{ substr($bank->account_number, -4) }})
                </option>
                @endforeach
            </select>
        </div>
        @else
        <div class="text-center p-4 border rounded-4 mb-4 bg-light">
            <p class="text-muted mb-3">No bank account linked to your account.</p>
            <a href="{{ route('agent.bank.link') }}" class="btn btn-outline-primary px-4 fw-bold">
                <i class="fas fa-plus me-2"></i>Link Bank
            </a>
        </div>
        @endif

        <label class="form-label">Amount to Deposit</label>
        <div class="amount-input-wrapper">
            <span class="currency-prefix">{{ session('user_currency_symbol', 'Rs.') }}</span>
            <input type="number" class="amount-input" placeholder="0.00" id="amount-field">
        </div>

        <div class="amount-grid">
            <div class="amount-btn" onclick="setAmount('5000')">5,000</div>
            <div class="amount-btn" onclick="setAmount('10000')">10,000</div>
            <div class="amount-btn" onclick="setAmount('25000')">25,000</div>
            <div class="amount-btn" onclick="setAmount('50000')">50,000</div>
        </div>

        <button class="btn-add-credit">
            <i class="fas fa-wallet me-2"></i>Add Store Credit
        </button>
        
        <a href="{{ route('agent.dashboard') }}" class="btn-cancel">Return to Dashboard</a>
    </div>
</div>

<script>
    function setAmount(val) {
        document.getElementById('amount-field').value = val;
    }
</script>
@endsection
