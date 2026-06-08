@extends('manager.layout.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">User Details & Approval</h4>
        <div class="d-flex gap-2">
            @if($user->profile_verification_status === 'pending')
                <form action="{{ route('manager.users.approve', $user->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success shadow-sm" onclick="return confirm('Approve this user?')">
                        <i class="fas fa-check me-2"></i> Approve User
                    </button>
                </form>
            @endif
            <a href="{{ route('manager.users') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left me-2"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-body text-center py-4">
                    <div class="position-relative d-inline-block mb-3">
                        <img src="{{ asset('images/user.png') }}" class="rounded-circle border border-4 border-light shadow-sm" width="110" height="110" alt="Avatar">
                        @if($user->profile_verification_status === 'verified')
                            <span class="position-absolute bottom-0 end-0 bg-success text-white rounded-circle p-2 border border-2 border-white">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        @endif
                    </div>
                    <h5 class="fw-bold mb-1">{{ $user->first_name }} {{ $user->last_name }}</h5>
                    <p class="text-muted small mb-3">{{ $user->email }}</p>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">{{ strtoupper(str_replace('_', ' ', $user->account_type)) }}</span>
                </div>
                <ul class="list-group list-group-flush border-top small">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">User ID</span>
                        <span class="fw-bold">{{ $user->user_id }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Country</span>
                        <span class="fw-bold">{{ $user->country_iso ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Status</span>
                        @if($user->profile_verification_status === 'verified')
                            <span class="badge bg-success">Verified</span>
                        @elseif($user->profile_verification_status === 'pending')
                            <span class="badge bg-warning text-dark">Pending Approval</span>
                        @else
                            <span class="badge bg-secondary">Incomplete</span>
                        @endif
                    </li>
                </ul>
            </div>

            <!-- Manager Action Card -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Manager Actions</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('manager.users.add_credit', $user->id) }}" method="POST">
                        @csrf
                        <label class="form-label small text-muted">Add/Adjust Store Credit (USD)</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-light border-0">$</span>
                            <input type="number" name="amount" class="form-control bg-light border-0" placeholder="0.00" step="0.01">
                            <button class="btn btn-primary" type="submit">Update</button>
                        </div>
                        <p class="x-small text-muted">Max limit for managers: $300.00 per transaction.</p>
                    </form>
                    <hr>
                    <button class="btn btn-outline-danger w-100" onclick="alert('Account frozen successfully')">
                        <i class="fas fa-snowflake me-2"></i> Freeze Account
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Full Profile Information</h5>
                    <span class="text-muted small">Joined {{ $user->created_at->format('M Y') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="small text-muted d-block">Full Name</label>
                            <span class="fw-bold">{{ $user->first_name }} {{ $user->last_name }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted d-block">Email Address</label>
                            <span class="fw-bold">{{ $user->email }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted d-block">Phone Number</label>
                            <span class="fw-bold">{{ $user->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted d-block">Company</label>
                            <span class="fw-bold">{{ $user->company_name ?? 'Individual' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($user->account_type === 'reseller_agent' && $user->business_name)
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-briefcase me-2 text-primary"></i> Business Verification</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Business Name</p>
                            <p class="fw-bold">{{ $user->business_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Registration Number</p>
                            <p class="fw-bold">{{ $user->registration_number }}</p>
                        </div>
                        <div class="col-12">
                            <p class="mb-1 text-muted small">Business Address</p>
                            <p class="fw-bold">{{ $user->address }}, {{ $user->city }}, {{ $user->country }}</p>
                        </div>
                    </div>
                    <hr>
                    <h6 class="fw-bold mb-3 small text-uppercase text-muted">Verification Documents</h6>
                    <div class="row g-2">
                        @if($user->registration_doc)
                            <div class="col-md-6">
                                <a href="{{ asset('storage/' . $user->registration_doc) }}" target="_blank" class="btn btn-light w-100 text-start border shadow-sm">
                                    <i class="fas fa-file-pdf text-danger me-2"></i> Business Registration
                                </a>
                            </div>
                        @endif
                        @if($user->id_doc)
                            <div class="col-md-6">
                                <a href="{{ asset('storage/' . $user->id_doc) }}" target="_blank" class="btn btn-light w-100 text-start border shadow-sm">
                                    <i class="fas fa-id-card text-primary me-2"></i> Representative ID
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            @if($user->account_type === 'student' && $user->exam_purpose)
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user-graduate me-2 text-primary"></i> Academic Profile</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Academic Level</p>
                            <p class="fw-bold">{{ $user->highest_education }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Identity Type</p>
                            <p class="fw-bold">{{ $user->id_type }} ({{ $user->id_number }})</p>
                        </div>
                        <div class="col-12">
                            <p class="mb-1 text-muted small">Target Countries</p>
                            <p class="fw-bold">
                                @php 
                                    $countries = $user->preferred_countries;
                                    if (is_string($countries)) {
                                        $decoded = json_decode($countries, true);
                                        if (json_last_error() === JSON_ERROR_NONE) {
                                            $countries = $decoded;
                                        }
                                    }
                                @endphp
                                {{ is_array($countries) ? implode(', ', $countries) : $countries }}
                            </p>
                        </div>
                    </div>
                    <hr>
                    <h6 class="fw-bold mb-3 small text-uppercase text-muted">Identity Documents</h6>
                    @if($user->id_doc)
                        <a href="{{ asset('storage/' . $user->id_doc) }}" target="_blank" class="btn btn-light border shadow-sm">
                            <i class="fas fa-file-image text-info me-2"></i> View ID Document
                        </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
