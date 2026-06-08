<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ ucfirst(auth()->user()->account_type) }} Dashboard - UniCou</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Parkinsans:wght@300..800&display=swap" rel="stylesheet">
    <!-- Toastr CSS -->

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <style>
        #toast-container>.toast {
            opacity: 1 !important;
        }

        .iti {
            width: 100%;
        }
    </style>
    @stack('styles')
</head>

<body>
    <div class="container-fluid">
        <div class="row koa-sidebar-wrapper">
            <!-- Overlay -->
            <div id="overlay" class="overlay"></div>
            @php
            $role = auth()->user()->account_type ?? null;
            @endphp
            @php
            use App\Helpers\LocationHelper;


            if (!session()->has('geo')) {
            LocationHelper::geo();
            }

            $geo = session('geo');
            @endphp
            @include('layouts.sidebar')
            <!-- Sidebar -->



            <div class="mobile-header text-end d-lg-none">
                <a class="text-start" href="/">
                    <img src="/images/company_logo.png" class="mobile-logo" alt=""> </a>
                <button id="open-sidebar" class="btn navbtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <!-- Main content -->
            <main class="main-content">

                @include('layouts.header')

                @yield('content')

            </main>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Toastr Configuration
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "3000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        @if(session('success'))
        toastr.success("{{ session('success') }}");
        @endif

        @if(session('error'))
        toastr.error("{{ session('error') }}");
        @endif

        const sidebar = document.getElementById('sidebar');
        const openSidebarBtn = document.getElementById('open-sidebar');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const overlay = document.getElementById('overlay');

        openSidebarBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.remove('sidebar-hidden');
            overlay.classList.add('overlay-active');
        });

        closeSidebarBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.add('sidebar-hidden');
            overlay.classList.remove('overlay-active');
        });

        overlay?.addEventListener('click', () => {
            sidebar.classList.add('sidebar-hidden');
            overlay.classList.remove('overlay-active');
        });
    </script>
    @stack('scripts')

    @push('scripts')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let iti;
        document.addEventListener('DOMContentLoaded', function() {
            // Geolocation tracking
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                }, function(error) {
                    console.error("Error obtaining location", error);
                });
            }

            const input = document.querySelector("#phone_input");
            iti = window.intlTelInput(input, {
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                separateDialCode: true,
                initialCountry: "auto",
                geoIpLookup: function(success, failure) {
                    fetch("https://ipapi.co/json")
                        .then(res => res.json())
                        .then(data => {
                            success(data.country_code);
                            if (typeof updateHeaderFlagUI === 'function') {
                                updateHeaderFlagUI(data.country_code, data.country_name);
                            }
                        })
                        .catch(() => {
                            success("in");
                            if (typeof updateHeaderFlagUI === 'function') {
                                updateHeaderFlagUI("in", "India");
                            }
                        });
                }
            });

            input.addEventListener('countrychange', function() {
                if (typeof updateHeaderFlagUI === 'function') {
                    const countryData = iti.getSelectedCountryData();
                    updateHeaderFlagUI(countryData.iso2, countryData.name);
                }
            });

            document.getElementById('submitBtns').addEventListener('click', function() {
                if (grecaptcha.getResponse() === "") {
                    Swal.fire({
                        title: "Verification Required",
                        text: "Please complete the reCAPTCHA.",
                        icon: "warning"
                    });
                    return;
                }

                document.getElementById('full_phone').value = iti.getNumber();
                document.getElementById('country_code').value = iti.getSelectedCountryData().iso2.toUpperCase();

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
            document.getElementById('accountType').value = type;
            document.querySelectorAll('.role-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            const activeBtn = document.getElementById('btn-' + type);
            if (activeBtn) {
                activeBtn.classList.add('active');
            }
        }
    </script>

    @endpush
</body>

</html>