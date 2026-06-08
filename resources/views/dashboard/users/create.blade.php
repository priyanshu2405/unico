@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold">Add New User</h4>
            <a href="{{ route('users.management') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="cls_user_edit col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name</label>
                                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                                @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <!-- <div class="col-md-6">
                                <label class="form-label fw-semibold">Last Name</label>
                                <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                                @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div> -->
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="text" id="phone" name="phone_dummy" class="form-control intl-phone @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                                <input type="hidden" name="phone" id="full_phone">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Country</label>
                                <select name="country" id="country" class="form-select @error('country') is-invalid @enderror" required>
                                    <option value="" selected disabled>Select Country</option>
                                </select>
                                @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">State</label>
                                <select name="state" id="state" class="form-select @error('state') is-invalid @enderror" required>
                                    <option value="" selected disabled>Select State</option>
                                </select>
                                @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">City</label>
                                <select name="city" id="city" class="form-select @error('city') is-invalid @enderror" required>
                                    <option value="" selected disabled>Select City</option>
                                </select>
                                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Account Type</label>
                                <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                                    @if(auth()->user()->account_type === 'reseller_agent')
                                     <option value="" selected disabled>Select Role</option>
                                    <option value="agent" >Agent</option>
                                    <option value="student" >Student</option>
                                    @else
                                    <option value="" selected disabled>Select Role</option>
                                    <option value="manager" {{ (old('account_type') == 'manager' || request('role') == 'manager') ? 'selected' : '' }}>Manager</option>
                                    <option value="reseller_agent" {{ (old('account_type') == 'reseller_agent' || request('role') == 'reseller_agent') ? 'selected' : '' }}>Reseller Agent</option>
                                    <option value="support_team" {{ (old('account_type') == 'support_team' || request('role') == 'support_team') ? 'selected' : '' }}>Support Team</option>
                                    <option value="student" {{ (old('account_type') == 'student' || request('role') == 'student') ? 'selected' : '' }}>Student</option>
                                    <option value="agent" {{ (old('account_type') == 'agent' || request('role') == 'agent') ? 'selected' : '' }}>Agent</option>
                                    @endif
                                </select>
                                @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>



                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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

    document.addEventListener("DOMContentLoaded", function() {
        const countrySelect = document.getElementById("country");
        const stateSelect = document.getElementById("state");
        const citySelect = document.getElementById("city");

        // Set default options
        countrySelect.innerHTML = '<option value="">Select Country</option>';
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';

        // Populate Countries
        const countries = Country.getAllCountries();
        const userPermittedCountries = @json(auth()->user()->permitted_countries ?? []);
        const canViewAllCountries = @json(auth()->user()->can_view_all_countries || auth()->user()->isAdmin() || auth()->user()->isManager());

        countries.forEach(country => {
            if (canViewAllCountries || userPermittedCountries.includes(country.name)) {
                const option = document.createElement('option');
                option.value = country.name;
                option.textContent = country.name;
                option.dataset.code = country.isoCode;
                countrySelect.appendChild(option);
            }
        });

        // Country Change Event
        countrySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const countryCode = selectedOption.dataset.code;

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
        });

        // State Change Event
        stateSelect.addEventListener('change', function() {
            const countryCode = countrySelect.options[countrySelect.selectedIndex].dataset.code;
            const stateCode = this.options[this.selectedIndex].dataset.code;

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
        });
    });
</script>
@endpush