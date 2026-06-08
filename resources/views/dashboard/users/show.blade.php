@extends('layouts.master')

@push('styles')
<style>
    .avatar-wrapper {
        position: relative;
        display: inline-block;
    }

    .status-dot {
        position: absolute;
        bottom: 10px;
        right: 15px;
        width: 15px;
        height: 15px;
        background-color: #22c55e;
        border: 2px solid #fff;
        border-radius: 50%;
    }

    .badge-soft-blue {
        background-color: #eff6ff;
        color: #3b82f6;
        border: 1px solid #dbeafe;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 5px 15px;
    }

    .badge-soft-success {
        background-color: #f0fdf4;
        color: #16a34a;
        font-weight: 600;
    }

    .text-xs {
        font-size: 0.75rem;
    }

    .text-sm {
        font-size: 0.875rem;
    }

    .nav-tabs {
        border-bottom: 1px solid #e5e7eb;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #6b7280;
        font-weight: 500;
        padding: 1rem 1.5rem;
        position: relative;
    }

    .nav-tabs .nav-link.active {
        color: #3b82f6;
        background: transparent;
    }

    .nav-tabs .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #3b82f6;
    }

    .progress-thin {
        height: 6px;
    }

    .doc-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }

    .doc-header {
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e5e7eb;
    }

    .doc-body {
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f9fafb;
        position: relative;
    }

    .doc-footer {
        padding: 10px 15px;
        background-color: #fff;
        border-top: 1px solid #e5e7eb;
    }

    .doc-status-missing {
        background-color: #fef2f2;
        color: #ef4444;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
    }

    .doc-status-uploaded {
        background-color: #f0fdf4;
        color: #22c55e;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
    }

    .btn-upload {
        background-color: #1e293b;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 16px;
    }

    .btn-upload:hover {
        background-color: #0f172a;
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-dark">User Command Center</h4>
            <p class="text-muted mb-0">Manage user access, documents and risk profiles</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if((auth()->user()->account_type !== 'manager' || auth()->user()->can_approve_user) && $user->profile_verification_status === 'pending')
            <form action="{{ route('approvals.approve', $user->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-t-g shadow-sm" onclick="return confirm('Approve this user?')">
                    <i class="fas fa-check me-2"></i> Approve User
                </button>
            </form>
            @endif

            @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_freeze_user)
            <form action="{{ route('users.toggle', $user->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn {{ $user->is_active ? 'btn-t-r' : 'btn-t-g' }} shadow-sm">
                    <i class="fas {{ $user->is_active ? 'fa-snowflake' : 'fa-user-check' }} me-2"></i>
                    {{ $user->is_active ? 'Freeze Login' : 'Unfreeze Login' }}
                </button>
            </form>

            <form action="{{ route('users.suspend', $user->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn {{ $user->profile_verification_status === 'suspended' ? 'btn-t-g' : 'btn-t-y' }} shadow-sm">
                    <i class="fas {{ $user->profile_verification_status === 'suspended' ? 'fa-user-check' : 'fa-user-minus' }} me-2"></i>
                    {{ $user->profile_verification_status === 'suspended' ? 'Unsuspend KYC' : 'Suspend KYC' }}
                </button>
            </form>
            @endif

            <a href="{{ route('users.management') }}" class="btn btn-dark shadow-sm" style="background-color: #5c6c84; border-color: #5c6c84;">
                <i class="fas fa-chevron-left me-2"></i> Back to List
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <strong>Validation Errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <!-- Sidebar Summary -->
        <div class="col-xl-3 col-lg-4">
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                <div class="card-body text-center pt-5 pb-4">
                    <div class="avatar-wrapper mb-3">
                        <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('images/user.png') }}"
                            class="rounded-circle border border-4 border-light shadow-sm" width="120" height="120" alt="Avatar" style="object-fit: cover;">
                        <span class="status-dot"></span>
                    </div>
                    <h5 class="fw-bold mb-1">
                        @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                            {{ $user->first_name }} {{ $user->last_name }}
                        @else
                            {{ $user->user_id }}
                        @endif
                    </h5>
                    <p class="text-muted small mb-3">
                        @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                            {{ $user->email }}
                        @else
                            Email Hidden
                        @endif
                    </p>
                    <div class="d-flex justify-content-center mb-4">
                        <span class="badge rounded-pill badge-soft-blue text-uppercase">
                            {{ str_replace('_', ' ', $user->account_type) }}
                        </span>
                    </div>

                    <div class="px-2">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted text-sm">User ID</span>
                            <span class="fw-bold text-sm text-dark">{{ $user->user_id }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted text-sm">Status</span>
                            @if($user->profile_verification_status === 'verified')
                            <span class="badge badge-soft-success text-sm px-3 py-1">VERIFIED</span>
                            @elseif($user->profile_verification_status === 'pending')
                            <span class="badge bg-warning-soft text-warning text-sm px-3 py-1">PENDING</span>
                            @elseif($user->profile_verification_status === 'suspended')
                            <span class="badge bg-danger-soft text-danger text-sm px-3 py-1">SUSPENDED</span>
                            @else
                            <span class="badge bg-secondary-soft text-secondary text-sm px-3 py-1">{{ strtoupper($user->profile_verification_status) }}</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted text-sm">Last Seen</span>
                            <span class="text-dark fw-medium text-sm">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Metrics Card -->
            <div class="card shadow-sm border-0 mb-4 p-3">
                <h6 class="text-uppercase text-muted text-xs fw-bold mb-3">Quick Metrics</h6>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-3 border rounded bg-light bg-opacity-10 h-100">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-wallet text-muted text-xs"></i>
                                <span class="text-muted text-xs">Wallet</span>
                            </div>
                            <div class="fw-bold text-dark">₹{{ number_format($user->wallet_balance, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded bg-light bg-opacity-10 h-100">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-star text-muted text-xs"></i>
                                <span class="text-muted text-xs">Points</span>
                            </div>
                            <div class="fw-bold text-dark">{{ number_format(\App\Models\Order::where('user_id', $user->id)->sum('points_earned')) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Risk & Compliance Card -->
            <div class="card shadow-sm border-0 mb-4 p-3">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    <h6 class="text-uppercase text-muted text-xs fw-bold mb-0">Risk & Compliance</h6>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted text-xs">Risk Level</span>
                        @php
                        $risk = \App\Models\CountryRiskLevel::where('country_name', $user->country_iso)->first();
                        $riskLevel = strtoupper($risk->risk_level ?? 'Low');
                        $riskColor = ($riskLevel === 'HIGH') ? 'danger' : (($riskLevel === 'MEDIUM') ? 'warning' : 'success');
                        $riskWidth = ($riskLevel === 'HIGH') ? '100' : (($riskLevel === 'MEDIUM') ? '60' : '30');
                        @endphp
                        <span class="text-{{ $riskColor }} text-xs fw-bold">{{ $riskLevel }}</span>
                    </div>
                    <div class="progress progress-thin">
                        <div class="progress-bar bg-{{ $riskColor }}" role="progressbar" style="width: {{ $riskWidth }}%" aria-valuenow="{{ $riskWidth }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted text-xs">Complaints</span>
                    <span class="fw-bold text-dark text-xs">0</span>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted text-xs">KYC Status</span>
                    <span class="text-success text-xs fw-bold">Verified</span>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-xl-9 col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white p-0 border-bottom">
                    <ul class="nav nav-tabs border-0 px-3" id="userTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active py-3 px-4" id="overview-tab" data-bs-toggle="tab" href="#overview" role="tab">Overview</a>
                        </li>
                           @if(!Auth::user()->isSupport())
                        <li class="nav-item">
                            <a class="nav-link py-3 px-4" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab">Edit Profile</a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link py-3 px-4" id="kyc-tab" data-bs-toggle="tab" href="#kyc" role="tab">KYC & Documents</a>
                        </li>
                        @if(!Auth::user()->isSupport())
                        <li class="nav-item">
                            <a class="nav-link py-3 px-4" id="security-tab" data-bs-toggle="tab" href="#security" role="tab">Security</a>
                        </li>
                        @endif
                        @if(in_array($user->account_type, ['manager', 'support_team', 'reseller_agent']))
                        <li class="nav-item">
                            <a class="nav-link py-3 px-4" id="permissions-tab" data-bs-toggle="tab" href="#permissions" role="tab">Permissions</a>
                        </li>
                        @endif
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="userTabsContent">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Personal Information</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="text-muted" width="40%">Full Name</td>
                                            <td class="fw-bold">
                                                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                                                    {{ $user->first_name }} {{ $user->last_name }}
                                                @else
                                                    {{ $user->user_id }}
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Email</td>
                                            <td class="fw-bold">
                                                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                                                    {{ $user->email }}
                                                @else
                                                    Hidden
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Primary Phone</td>
                                            <td class="fw-bold">{{ $user->phone ?? $user->primary_contact ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">WhatsApp</td>
                                            <td class="fw-bold">{{ $user->whatsapp_number ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Date of Birth</td>
                                            <td class="fw-bold">{{ $user->dob ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">ID Type / No.</td>
                                            <td class="fw-bold">{{ $user->id_type ?? 'N/A' }} ({{ $user->id_number ?? 'N/A' }})</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Address</td>
                                            <td class="fw-bold">
                                                {{ $user->address ?? 'N/A' }}<br>
                                                {{ $user->city ?? '' }}, {{ $user->state ?? '' }} {{ $user->post_code ?? $user->zip_code ?? '' }}<br>
                                                {{ $user->country ?? '' }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Account Activity</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="text-muted" width="40%">Joined Date</td>
                                            <td class="fw-bold">{{ $user->created_at->format('d M Y, h:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Last Login</td>
                                            <td class="fw-bold">{{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Email Verified</td>
                                            <td class="fw-bold">
                                                @if($user->email_verified_at)
                                                <span class="text-success"><i class="fas fa-check-circle me-1"></i> Verified</span>
                                                @else
                                                <span class="text-danger"><i class="fas fa-times-circle me-1"></i> Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Total Orders</td>
                                            <td class="fw-bold">{{ \App\Models\Order::where('user_id', $user->id)->count() }}</td>
                                        </tr>
                                    </table>
                                </div>

                                @if($user->account_type === 'reseller_agent' || $user->account_type === 'agent')
                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Business Information</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light rounded h-100 shadow-none border">
                                                <small class="text-muted d-block">Business Name</small>
                                                <span class="fw-bold">{{ $user->business_name ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light rounded h-100 shadow-none border">
                                                <small class="text-muted d-block">Registration No.</small>
                                                <span class="fw-bold">{{ $user->registration_number ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light rounded h-100 shadow-none border">
                                                <small class="text-muted d-block">Website</small>
                                                <span class="fw-bold">{{ $user->website ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($user->account_type === 'student')
                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Education & Admission Details</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light rounded h-100 shadow-none border">
                                                <small class="text-muted d-block">Exam Purpose</small>
                                                <span class="fw-bold">{{ $user->exam_purpose ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light rounded h-100 shadow-none border">
                                                <small class="text-muted d-block">Highest Education</small>
                                                <span class="fw-bold">{{ $user->highest_education ?? 'N/A' }} ({{ $user->passing_year ?? 'N/A' }})</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light rounded h-100 shadow-none border">
                                                <small class="text-muted d-block">Preferred Countries</small>
                                                <span class="fw-bold">
                                                    @if(is_array($user->preferred_countries))
                                                    {{ implode(', ', $user->preferred_countries) }}
                                                    @else
                                                    {{ $user->preferred_countries ?? 'N/A' }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light rounded h-100 shadow-none border">
                                                <small class="text-muted d-block">Bank Account</small>
                                                <span class="fw-bold">{{ $user->bank_name ?? 'N/A' }}</span><br>
                                                <small class="text-muted">{{ $user->account_number ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light rounded h-100 shadow-none border">
                                                <small class="text-muted d-block">Bank Country</small>
                                                <span class="fw-bold">{{ $user->bank_country ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Edit Profile Tab -->
                        <div class="tab-pane fade" id="profile" role="tabpanel">
                            @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_edit_user)
                            <form action="{{ route('users.update', $user->id) }}#profile" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">First Name</label>
                                        <input type="text" name="first_name" class="form-control" value="{{ $user->first_name }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Last Name</label>
                                        <input type="text" name="last_name" class="form-control" value="{{ $user->last_name }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Email Address</label>
                                        <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="{{ $user->phone ?? $user->primary_contact }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">WhatsApp Number</label>
                                        <input type="text" name="whatsapp_number" class="form-control" value="{{ $user->whatsapp_number }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Date of Birth</label>
                                        <input type="date" name="dob" class="form-control" value="{{ $user->dob }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">ID Type</label>
                                        <select name="id_type" class="form-select">
                                            <option value="">Select document type</option>
                                            <option {{ $user->id_type == 'National ID Card' ? 'selected' : '' }}>National ID Card</option>
                                            <option {{ $user->id_type == 'Passport' ? 'selected' : '' }}>Passport</option>
                                            <option {{ $user->id_type == 'Driving License' ? 'selected' : '' }}>Driving License</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">ID Number</label>
                                        <input type="text" name="id_number" class="form-control" value="{{ $user->id_number }}">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold small">Full Address</label>
                                        <textarea name="address" class="form-control" rows="2">{{ $user->address }}</textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">City</label>
                                        <input type="text" name="city" class="form-control" value="{{ $user->city }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">State</label>
                                        <input type="text" name="state" class="form-control" value="{{ $user->state }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold small">Zip/Post Code</label>
                                        <input type="text" name="post_code" class="form-control" value="{{ $user->post_code ?? $user->zip_code }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold small">Country</label>
                                        <input type="text" name="country" class="form-control" value="{{ $user->country }}">
                                    </div>

                                    @if($user->account_type === 'reseller_agent' || $user->account_type === 'agent')
                                    <div class="col-12 mt-4">
                                        <h6 class="fw-bold border-bottom pb-2">Agent Business Data</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Business Name</label>
                                        <input type="text" name="business_name" class="form-control" value="{{ $user->business_name }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Registration Number</label>
                                        <input type="text" name="registration_number" class="form-control" value="{{ $user->registration_number }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Website</label>
                                        <input type="url" name="website" class="form-control" value="{{ $user->website }}">
                                    </div>
                                    @endif

                                    @if($user->account_type === 'student')
                                    <div class="col-12 mt-4">
                                        <h6 class="fw-bold border-bottom pb-2">Student Education & Bank Data</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Exam Purpose</label>
                                        <select name="exam_purpose" class="form-select">
                                            <option value="">Select purpose</option>
                                            <option {{ $user->exam_purpose == 'Education' ? 'selected' : '' }}>Education</option>
                                            <option {{ $user->exam_purpose == 'Migration' ? 'selected' : '' }}>Migration</option>
                                            <option {{ $user->exam_purpose == 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small">Highest Edu.</label>
                                        <input type="text" name="highest_education" class="form-control" value="{{ $user->highest_education }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small">Passing Year</label>
                                        <input type="text" name="passing_year" class="form-control" value="{{ $user->passing_year }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Bank Name</label>
                                        <input type="text" name="bank_name" class="form-control" value="{{ $user->bank_name }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Bank Country</label>
                                        <input type="text" name="bank_country" class="form-control" value="{{ $user->bank_country }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">IBAN/Account No</label>
                                        <input type="text" name="account_number" class="form-control" value="{{ $user->account_number }}">
                                    </div>
                                    <div class="col-12 mt-3">
                                        <label class="form-label fw-bold small d-block">Preferred Countries</label>
                                        <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded border">
                                            @php
                                            $selectedCountries = is_array($user->preferred_countries) ? $user->preferred_countries : explode(', ', $user->preferred_countries ?? '');
                                            $options = ['United Kingdom', 'United States', 'Canada', 'Australia'];
                                            @endphp
                                            @foreach($options as $option)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="preferred_countries[]" value="{{ $option }}" id="pc_{{ $loop->index }}" {{ in_array($option, $selectedCountries) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="pc_{{ $loop->index }}">{{ $option }}</label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-12 mt-4 text-end">
                                        <button type="submit" class="btn btn-primary px-5 btn-lg shadow-sm">
                                            <i class="fas fa-save me-2"></i> Update User Profile
                                        </button>
                                    </div>
                                </div>
                            </form>
                            @else
                            <div class="alert alert-info border-0 shadow-sm">
                                <i class="fas fa-info-circle me-2 text-primary"></i> You do not have permission to edit user profiles.
                            </div>
                            @endif
                        </div>

                        <!-- KYC Tab -->
                        <div class="tab-pane fade" id="kyc" role="tabpanel">
                            <div class="row g-4">
                                @php
                                $docs = [
                                'Profile Photo' => ['path' => $user->profile_photo, 'field' => 'profile_photo'],
                                'Business Reg' => ['path' => $user->registration_doc, 'field' => 'registration_doc'],
                                'ID Document' => ['path' => $user->id_doc, 'field' => 'id_doc'],
                                'Final ID Doc' => ['path' => $user->id_doc_final, 'field' => 'id_doc_final'],
                                ];
                                @endphp

                                @foreach($docs as $label => $data)
                                <div class="col-md-6 col-lg-4">
                                    <div class="doc-card h-100">
                                        <div class="doc-header">
                                            <span class="text-muted text-xs fw-bold text-uppercase">{{ $label }}</span>
                                            @if($data['path'])
                                            <span class="doc-status-uploaded">UPLOADED</span>
                                            @else
                                            <span class="doc-status-missing">MISSING</span>
                                            @endif
                                        </div>
                                        <div class="doc-body">
                                            @if($data['path'])
                                            @php
                                            $extension = strtolower(pathinfo($data['path'], PATHINFO_EXTENSION));
                                            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                            @endphp
                                            @if($isImage)
                                            <img src="{{ asset('storage/' . $data['path']) }}" class="img-fluid" style="max-height: 100%; width: 100%; object-fit: cover;">
                                            @if($user->profile_verification_status === 'verified' && $label === 'Final ID Doc')
                                            <div class="position-absolute bottom-0 start-0 m-3">
                                                <span class="badge bg-white text-success border px-2 py-1 text-xs">
                                                    <i class="fas fa-check-circle me-1"></i> Verified
                                                </span>
                                            </div>
                                            @endif
                                            @else
                                            <div class="text-center">
                                                <i class="fas fa-file-alt fa-3x text-muted mb-2"></i>
                                                <div class="text-xs text-muted px-2 text-truncate" style="max-width: 200px;">{{ basename($data['path']) }}</div>
                                            </div>
                                            @endif
                                            @else
                                            <div class="text-center">
                                                <i class="fas fa-file-alt fa-3x text-muted mb-2" style="opacity: 0.2;"></i>
                                                <div class="text-muted text-sm">No file uploaded</div>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="doc-footer">
                                            @if(!in_array(auth()->user()->account_type, ['manager', 'support_team']) || auth()->user()->can_edit_user)
                                            <form action="{{ route('users.update', $user->id) }}#kyc" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')
                                                <div class="d-flex gap-2">
                                                    <div class="flex-grow-1">
                                                        <input type="file" name="{{ $data['field'] }}" class="form-control form-control-sm" style="font-size: 11px;" required>
                                                    </div>
                                                    <button type="submit" class="btn btn-upload">
                                                        <i class="fas fa-upload"></i> Upload
                                                    </button>
                                                </div>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_reset_password)
                            <div class="card border-0 bg-light mb-4 shadow-sm border">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold mb-3 text-dark">Reset User Password</h6>
                                    <form action="{{ route('users.password.update', $user->id) }}#security" method="POST">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <label class="form-label small text-muted fw-bold">New Password</label>
                                                <input type="password" name="password" class="form-control shadow-sm" placeholder="Min 8 characters" required>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small text-muted fw-bold">Confirm Password</label>
                                                <input type="password" name="password_confirmation" class="form-control shadow-sm" placeholder="Repeat password" required>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary w-100 shadow-sm">Update</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif

                            @if(auth()->user()->account_type === 'admin')
                            <div class="card border-0 bg-danger-soft shadow-sm border border-danger border-opacity-10">
                                <div class="card-body p-4 text-center">
                                    <h6 class="fw-bold text-danger mb-2">Danger Zone</h6>
                                    <p class="small text-muted mb-3">Deleting this account will permanently remove all associated data, including orders and transaction history. This action cannot be undone.</p>
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('ARE YOU ABSOLUTELY SURE? This cannot be undone!')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger px-4 shadow-sm">
                                            <i class="fas fa-trash-alt me-2"></i> Delete Permanently
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Permissions Tab -->
                        @if(in_array($user->account_type, ['manager', 'support_team', 'reseller_agent']))
                        <div class="tab-pane fade" id="permissions" role="tabpanel">
                            <h6 class="fw-bold mb-3 text-dark">{{ ucfirst(str_replace('_', ' ', $user->account_type)) }} Capabilities</h6>
                            <p class="text-muted small mb-4">Define what administrative actions this {{ str_replace('_', ' ', $user->account_type) }} is authorized to perform across the platform.</p>

                            <form action="{{ route('users.permissions.update', $user->id) }}#permissions" method="POST">
                                @csrf
                            
                                <div class="row g-4">
                                    @php
                                    $perms = [
                                    ['name' => 'can_view_users', 'label' => 'View User Management', 'desc' => 'Access the user lists and view user details'],
                                    ['name' => 'can_create_user', 'label' => 'Create New Users', 'desc' => 'Register new agents, resellers, or students'],
                                    ['name' => 'can_edit_user', 'label' => 'Edit User Profiles', 'desc' => 'Modify user information and documents'],
                                    ['name' => 'can_approve_user', 'label' => 'Approve Verifications', 'desc' => 'Approve pending KYC and profile requests'],
                                    ['name' => 'can_freeze_user', 'label' => 'Freeze Accounts', 'desc' => 'Suspend or reactivate user access'],
                                    ['name' => 'can_reset_password', 'label' => 'Reset Passwords', 'desc' => 'Change passwords for managed users'],
                                    ['name' => 'can_impersonate_user', 'label' => 'Access User Dashboards', 'desc' => 'Login as any student or agent to assist them'],
                                    ['name' => 'can_stop_system_sales', 'label' => 'Stop System Sales', 'desc' => 'Allow manager to toggle global system sales status'],
                                    ['name' => 'can_stop_country_sales', 'label' => 'Stop Country Sales', 'desc' => 'Allow manager to restrict sales for specific countries'],
                                    ['name' => 'can_stop_voucher_sales', 'label' => 'Stop Voucher Sales', 'desc' => 'Allow manager to restrict sales for specific vouchers'],
                                    ['name' => 'can_view_user_email_name', 'label' => 'View Name & Email', 'desc' => 'Allow manager to see real names and emails of users'],
                                    ['name' => 'has_job_permission', 'label' => 'Job Applications Access', 'desc' => 'Allow manager to view and manage job applications'],
                                    ['name' => 'can_view_voucher_code', 'label' => 'View Voucher Codes', 'desc' => 'Allow viewing actual voucher codes in orders'],
                                    ['name' => 'can_view_orders', 'label' => 'View Orders', 'desc' => 'Allow access and view order history'],
                                    ['name' => 'can_view_sales_report', 'label' => 'View Sales Report', 'desc' => 'Allow view detailed sales and payment reports'],
                                    ['name' => 'can_view_disputes', 'label' => 'Manage Disputes', 'desc' => 'Allow access and manage customer disputes'],
                                    ['name' => 'can_view_voucher_stock', 'label' => 'View Voucher Stock', 'desc' => 'Allow manager to view voucher inventory/stock'],
                                    ['name' => 'can_edit_voucher_stock', 'label' => 'Edit Voucher Stock', 'desc' => 'Allow manager to edit/update voucher inventory/stock'],
                                    ['name' => 'can_view_reports_page', 'label' => 'View Reports Page', 'desc' => 'Allow manager to access the reports dashboard page'],
                                    ];

                                    if ($user->account_type === 'support_team') {
                                        $supportPerms = ['can_view_users','can_view_voucher_code', 'can_view_orders', 'can_view_sales_report', 'can_view_disputes'];
                                        $perms = array_filter($perms, function($p) use ($supportPerms) {
                                            return in_array($p['name'], $supportPerms);
                                        });
                                    }

                                    if ($user->account_type === 'reseller_agent') {
                                        $resellerPerms = [ ];
                                        $perms = array_filter($perms, function($p) use ($resellerPerms) {
                                            return in_array($p['name'], $resellerPerms);
                                        });
                                    }
                                    @endphp

                                    @foreach($perms as $p)
                                    <div class="col-md-6">
                                        <div class="form-check form-switch p-3 border rounded h-100 bg-white shadow-sm transition hover-shadow">
                                            <input class="form-check-input ms-0 me-3 mt-1" type="checkbox" name="{{ $p['name'] }}" id="{{ $p['name'] }}" {{ $user->{$p['name']} ? 'checked' : '' }}>
                                            <label class="form-check-label w-100" for="{{ $p['name'] }}">
                                                <div class="fw-bold text-dark">{{ $p['label'] }}</div>
                                                <div class="small text-muted">{{ $p['desc'] }}</div>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                          
                                <div class="mt-5 pt-4 border-top">
                                    <h6 class="fw-bold mb-3 text-dark">Country Visibility Permission</h6>
                                    <p class="text-muted small mb-4">Control which countries this {{ str_replace('_', ' ', $user->account_type) }} can monitor. If restricted, they will only see data from selected countries.</p>
                                    
                                    <div class="card bg-light border-0 shadow-none mb-4">
                                        <div class="card-body p-3">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="can_view_all_countries" id="can_view_all_countries" {{ $user->can_view_all_countries ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark" for="can_view_all_countries">
                                                    Allow Access to All Countries
                                                </label>
                                                <div class="small text-muted mt-1">When enabled, the user can see data from every available country regardless of the list below.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="country-selection-container" style="{{ $user->can_view_all_countries ? 'display: none;' : '' }}">
                                        <label class="form-label fw-bold small text-dark mb-3">Permitted Countries</label>
                                        <div class="row g-2">
                                            @php
                                                $allCountries = \App\Models\User::whereNotNull('country')->where('country', '!=', '')->distinct()->orderBy('country')->pluck('country');
                                                $permitted = $user->permitted_countries ?? [];
                                            @endphp
                                            @foreach($allCountries as $country)
                                                <div class="col-md-3 col-sm-6">
                                                    <div class="form-check p-2 border rounded bg-white">
                                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="permitted_countries[]" value="{{ $country }}" id="country_{{ Str::slug($country) }}" {{ in_array($country, $permitted) ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="country_{{ Str::slug($country) }}">
                                                            {{ $country }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary px-5 btn-lg shadow-sm">Apply Permissions</button>
                                </div>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-primary-soft {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .bg-success-soft {
        background-color: rgba(25, 135, 84, 0.1);
    }

    .bg-warning-soft {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .bg-danger-soft {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .bg-secondary-soft {
        background-color: rgba(108, 117, 125, 0.1);
    }

    .nav-tabs .nav-link {
        color: #6c757d;
        border-radius: 0;
        border: none;
        font-size: 0.9rem;
        letter-spacing: 0.5px;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom: 3px solid #0d6efd !important;
        background: none;
    }

    .border-dashed {
        border-style: dashed !important;
        border-width: 2px !important;
    }

    .hover-shadow:hover {
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08) !important;
    }

    .transition {
        transition: all 0.3s ease;
    }

    .btn-xs {
        padding: .125rem .25rem;
        font-size: .75rem;
        border-radius: .2rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab activation based on hash
        var hash = window.location.hash;
        if (hash) {
            var triggerEl = document.querySelector('ul.nav-tabs a[href="' + hash + '"]');
            if (triggerEl) {
                var tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }

        // Update hash on tab change
        var tabLinks = document.querySelectorAll('ul.nav-tabs a');
        tabLinks.forEach(function(link) {
            link.addEventListener('shown.bs.tab', function(e) {
                // Remove the hash if it exists to avoid scrolling
                history.replaceState(null, null, ' ' + e.target.hash);
            });
        });
        // Country Selection Toggle
        const toggleAllCountries = document.getElementById('can_view_all_countries');
        const countrySelectionContainer = document.getElementById('country-selection-container');
        
        if (toggleAllCountries && countrySelectionContainer) {
            toggleAllCountries.addEventListener('change', function() {
                if (this.checked) {
                    countrySelectionContainer.style.display = 'none';
                } else {
                    countrySelectionContainer.style.display = 'block';
                }
            });
        }
    });
</script>
@endsection