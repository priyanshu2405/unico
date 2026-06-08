@extends('layouts.auth')

@section('title', 'Register - UniCou')

@section('content')
@php
$requestedType = request()->get('type');
$lockRequested = filter_var(request()->get('locked'), FILTER_VALIDATE_BOOLEAN);
$allowedTypes = ['manager', 'reseller_agent', 'support_team', 'student'];

if ($requestedType === 'freelancer' && $lockRequested) {
$allowedTypes[] = 'freelancer';
}
$defaultType = in_array($requestedType, $allowedTypes, true) ? $requestedType : 'student';
$accountLocked = $lockRequested && $defaultType === $requestedType;
@endphp
<style>
    .auth-body {
        overflow: hidden;
        height: 100vh;
        padding: 0;
        margin: 0;
    }

    .register_page_sec {
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        /* background: #f8fafc; */
    }

    .auth-wrapper {
        height: 90vh;
        max-height: 850px;
        display: flex;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        width: 95%;
        max-width: 1100px;
        overflow: hidden;
    }

    .auth-image {
        width: 45%;
        position: relative;
    }

    .auth-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .auth-image::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(35, 170, 226, 0.2), rgba(30, 64, 175, 0.4));
    }

    .auth-form {
        width: 55%;
        padding: 40px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    .role-switch {
        background: #f1f5f9;
        padding: 4px;
        border-radius: 50px;
        display: inline-flex;
        margin: 0 auto 20px;
    }

    .role-btn {
        border: none;
        padding: 8px 32px;
        border-radius: 40px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        background: transparent;
        color: #64748b;
        transition: all 0.3s ease;
    }

    .role-btn.active {
        background: #23aae2;
        color: #fff;
        box-shadow: 0 4px 12px rgba(35, 170, 226, 0.3);
    }

    .form-group-custom {
        margin-bottom: 15px;
    }

    .form-group-custom label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
    }

    .form-group-custom input {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .form-group-custom input:focus {
        border-color: #23aae2;
        box-shadow: 0 0 0 3px rgba(35, 170, 226, 0.1);
        outline: none;
    }

    .btn-register {
        background: #23aae2;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 16px;
        margin-top: 10px;
        transition: all 0.3s ease;
    }

    .btn-register:hover {
        background: #1c8bbd;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(35, 170, 226, 0.4);
    }

    .captcha-wrapper {
        transform: scale(0.9);
        transform-origin: left;
        margin: 10px 0;
    }

    @media (max-width: 992px) {
        .auth-image {
            display: none;
        }

        .auth-form {
            width: 100%;
            padding: 30px;
        }

        .auth-wrapper {
            height: auto;
            max-height: 95vh;
        }
    }
</style>
<section class="register_page_sec">
    <div class="auth-wrapper">
        <div class="auth-image">
            <img id="roleImage" src="{{asset('images/registration_image.jpg')}}" alt="Register">
        </div>
        <div class="auth-form">
            <div class="text-center mb-4">
                <h2 class="fw-bold text-dark mb-1">Join UniCou</h2>
                <p class="text-muted small">Create your account to start managing vouchers</p>
            </div>

            @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="text-center mb-3">
                @if(!$accountLocked)
                <div class="role-switch">
                    <button type="button" class="role-btn {{ $defaultType === 'agent' || $defaultType === 'reseller_agent' ? 'active' : '' }}" onclick="setActive('agent')" id="btn-agent">Agent</button>
                    <button type="button" class="role-btn {{ $defaultType === 'student' ? 'active' : '' }}" onclick="setActive('student')" id="btn-student">Student</button>
                </div>

                <div id="agent-options" class="mb-3" style="display: {{ $defaultType === 'agent' || $defaultType === 'reseller_agent' ? 'block' : 'none' }};">
                    <div class="d-flex justify-content-center gap-4">
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" name="agent_subtype" id="regularAgent" value="agent" {{ $defaultType === 'agent' ? 'checked' : '' }} onclick="setSubtype('agent')">
                            <label class="form-check-label fw-600" for="regularAgent">Regular Agent</label>
                        </div>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" name="agent_subtype" id="resellerAgent" value="reseller_agent" {{ $defaultType === 'reseller_agent' ? 'checked' : '' }} onclick="setSubtype('reseller_agent')">
                            <label class="form-check-label fw-600" for="resellerAgent">Reseller Agent</label>
                        </div>
                    </div>
                </div>
                @else
                <div class="badge bg-light text-primary border p-2 px-3 mb-3">
                    Registering as {{ ucfirst(str_replace('_', ' ', $defaultType)) }}
                </div>
                @endif
            </div>

            <form id="registerForm" method="POST" action="{{ route('register.post') }}" class="flex-grow-1">
                @csrf
                <input type="hidden" name="account_type" id="accountType" value="{{ $defaultType }}">
                <input type="hidden" id="regifeetaken" name="regifeetaken" value="no">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                <div class="form-group-custom">
                    <label>Full Name</label>
                    <input name="first_name" type="text" placeholder="Enter your full name" value="{{ old('first_name') }}" required>
                </div>

                <div class="form-group-custom">
                    <label>Email Address</label>
                    <input name="email" type="email" placeholder="Enter your email" value="{{ old('email') }}" required>
                </div>

                <div class="form-group-custom">
                    <label>Phone Number</label>
                    <input type="tel" id="phone_input" name="phone_dummy" class="intl-phone" required style="width: 100%;">
                    <input type="hidden" name="phone" id="full_phone">
                    <input type="hidden" name="country_code" id="country_code">
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group-custom">
                            <label>Password</label>
                            <input name="password" type="password" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group-custom">
                            <label>Confirm Password</label>
                            <input name="password_confirmation" type="password" placeholder="••••••••" required>
                            <small id="password-error" class="text-danger" style="display: none; font-size: 11px;">Passwords do not match</small>
                        </div>
                    </div>
                </div>

                <div class="captcha-wrapper">
                    <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE') }}"></div>
                </div>

                <button type="button" class="btn-register w-100 mt-3" id="submitBtns">
                    Create Account
                </button>
                <button hidden type="submit" id="realSubmitBtn"></button>

                <p class="text-center mt-4 small text-muted">
                    Already have an account? <a href="{{ route('login') }}" class="text-primary fw-bold text-decoration-none">Login here</a>
                </p>
            </form>
        </div>
    </div>
</section>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />

@endpush

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Real-time password validation
        const passwordInput = document.querySelector('input[name="password"]');
        const confirmInput = document.querySelector('input[name="password_confirmation"]');
        const errorSpan = document.getElementById('password-error');

        function checkPasswordMatch() {
            if (confirmInput.value.length > 0) {
                if (passwordInput.value !== confirmInput.value) {
                    errorSpan.style.display = 'block';
                    confirmInput.style.borderColor = '#dc3545';
                } else {
                    errorSpan.style.display = 'none';
                    confirmInput.style.borderColor = '#23aae2';
                }
            } else {
                errorSpan.style.display = 'none';
                confirmInput.style.borderColor = '#e2e8f0';
            }
        }

        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmInput.addEventListener('input', checkPasswordMatch);

        // Geolocation tracking
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById('latitude').value = position.coords.latitude;
                document.getElementById('longitude').value = position.coords.longitude;
            }, function(error) {
                console.error("Error obtaining location", error);
            });
        }

        document.getElementById('submitBtns').addEventListener('click', function() {
            if (grecaptcha.getResponse() === "") {
                Swal.fire({
                    title: "Verification Required",
                    text: "Please complete the reCAPTCHA.",
                    icon: "warning"
                });
                return;
            }

            // Get phone from intl-tel-input instance attached by dashboard.js
            const phoneInput = document.querySelector("#phone_input");
            if (phoneInput && phoneInput.iti) {
                document.getElementById('full_phone').value = phoneInput.iti.getNumber();
                document.getElementById('country_code').value = phoneInput.iti.getSelectedCountryData().iso2.toUpperCase();
            }

            document.getElementById('regifeetaken').value = 'no';
            const btn = document.getElementById('submitBtns');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Processing...';
            }
            document.getElementById('realSubmitBtn').click();
        });
    });

    function setActive(type) {
        // Reset all buttons
        document.querySelectorAll('.role-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Set clicked button to active
        const activeBtn = document.getElementById('btn-' + type);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }

        // Update Image based on role
        const roleImage = document.getElementById('roleImage');
        if (type === 'agent' || type === 'reseller_agent') {
            roleImage.src = "{{asset('images/agent_regis_image.jpg')}}"; // Agent Image
        } else {
            roleImage.src = "{{asset('images/registration_image.jpg')}}"; // Student Image
        }

        document.getElementById('accountType').value = type;

        // Handle Agent Subtypes Visibility
        const agentOptions = document.getElementById('agent-options');
        if (type === 'agent' || type === 'reseller_agent') {
            agentOptions.style.display = 'block';
            // If switched to agent, use the currently selected radio value
            const radioChecked = document.querySelector('input[name="agent_subtype"]:checked');
            if (radioChecked) {
                document.getElementById('accountType').value = radioChecked.value;
            }
        } else {
            agentOptions.style.display = 'none';
        }
    }

    function setSubtype(subtype) {
        document.getElementById('accountType').value = subtype;
    }
</script>
@endpush
@endsection