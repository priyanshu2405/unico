@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold">Edit User</h4>
            <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <div class="cls_user_edit col-md-8 ">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="{{ route('users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">First Name</label>
                                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $user->first_name) }}" required>
                                @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="text" id="phone" name="phone_dummy" class="form-control intl-phone @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
                                <input type="hidden" name="phone" id="full_phone">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Country</label>
                                <select name="country" id="country" class="form-select @error('country') is-invalid @enderror" required>
                                    <option value="" disabled>Select Country</option>
                                </select>
                                @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">State</label>
                                <select name="state" id="state" class="form-select @error('state') is-invalid @enderror" required>
                                    <option value="" disabled>Select State</option>
                                </select>
                                @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">City</label>
                                <select name="city" id="city" class="form-select @error('city') is-invalid @enderror" required>
                                    <option value="" disabled>Select City</option>
                                </select>
                                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Account Type</label>
                                <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                                    <option value="manager" {{ old('account_type', $user->account_type) == 'manager' ? 'selected' : '' }}>Manager</option>
                                    <option value="reseller_agent" {{ old('account_type', $user->account_type) == 'reseller_agent' ? 'selected' : '' }}>Reseller Agent</option>
                                    <option value="support_team" {{ old('account_type', $user->account_type) == 'support_team' ? 'selected' : '' }}>Support Team</option>
                                    <option value="student" {{ old('account_type', $user->account_type) == 'student' ? 'selected' : '' }}>Student</option>
                                    <option value="agent" {{ old('account_type', $user->account_type) == 'agent' ? 'selected' : '' }}>Agent</option>
                                </select>
                                @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Verification Status</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="pending" {{ old('status', $user->profile_verification_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="verified" {{ old('status', $user->profile_verification_status) == 'verified' ? 'selected' : '' }}>Verified</option>
                                    <option value="suspended" {{ old('status', $user->profile_verification_status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> Update User
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

        const initialCountry = "{{ old('country', $user->country) }}";
        const initialState = "{{ old('state', $user->state) }}";
        const initialCity = "{{ old('city', $user->city) }}";

        // Set default options
        countrySelect.innerHTML = '<option value="">Select Country</option>';
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';

        // Populate Countries
        const countries = Country.getAllCountries();
        countries.forEach(country => {
            const option = document.createElement('option');
            option.value = country.name;
            option.textContent = country.name;
            option.dataset.code = country.isoCode;
            if (country.name === initialCountry) {
                option.selected = true;
            }
            countrySelect.appendChild(option);
        });

        function updateStates(countryCode, selectedState = null) {
            stateSelect.innerHTML = '<option value="">Select State</option>';
            citySelect.innerHTML = '<option value="">Select City</option>';

            if (countryCode) {
                const states = State.getStatesOfCountry(countryCode);
                states.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state.name;
                    option.textContent = state.name;
                    option.dataset.code = state.isoCode;
                    if (state.name === selectedState) {
                        option.selected = true;
                    }
                    stateSelect.appendChild(option);
                });

                if (selectedState) {
                    const stateCode = stateSelect.options[stateSelect.selectedIndex]?.dataset.code;
                    if (stateCode) {
                        updateCities(countryCode, stateCode, initialCity);
                    }
                }
            }
        }

        function updateCities(countryCode, stateCode, selectedCity = null) {
            citySelect.innerHTML = '<option value="">Select City</option>';

            if (countryCode && stateCode) {
                const cities = City.getCitiesOfState(countryCode, stateCode);
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.name;
                    option.textContent = city.name;
                    if (city.name === selectedCity) {
                        option.selected = true;
                    }
                    citySelect.appendChild(option);
                });
            }
        }

        // Handle initial load
        if (initialCountry) {
            const countryCode = countrySelect.options[countrySelect.selectedIndex]?.dataset.code;
            if (countryCode) {
                updateStates(countryCode, initialState);
            }
        }

        // Country Change Event
        countrySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const countryCode = selectedOption.dataset.code;
            updateStates(countryCode);
        });

        // State Change Event
        stateSelect.addEventListener('change', function() {
            const countryCode = countrySelect.options[countrySelect.selectedIndex].dataset.code;
            const stateCode = this.options[this.selectedIndex].dataset.code;
            updateCities(countryCode, stateCode);
        });
    });
</script>
@endpush