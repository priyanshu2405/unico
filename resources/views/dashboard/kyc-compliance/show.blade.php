@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">User Details</h4>
        <div class="d-flex gap-2">
            @if($user->profile_verification_status === 'pending')
            <form action="{{ route('approvals.approve', $user->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this user?')">
                    <i class="fas fa-check me-2"></i> Approve User
                </button>
            </form>
            @endif

            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center py-4">
                    <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('images/user.png') }}"
                        class="rounded-circle mb-3" width="100" height="100" alt="Avatar" style="object-fit: cover;">
                    <h5 class="fw-bold mb-1">{{ $user->first_name }} {{ $user->last_name }}</h5>
                    <p class="text-muted mb-3">{{ $user->email }}</p>
                    <span class="badge bg-primary px-3 py-2">{{ strtoupper(str_replace('_', ' ', $user->account_type)) }}</span>
                </div>
                <ul class="list-group list-group-flush border-top">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">User ID</span>
                        <span class="fw-bold">{{ $user->user_id }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Status</span>
                        @if($user->profile_verification_status === 'verified')
                        <span class="badge bg-success">Verified</span>
                        @elseif($user->profile_verification_status === 'pending')
                        <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($user->profile_verification_status === 'suspended')
                        <span class="badge bg-danger">Suspended</span>
                        @else
                        <span class="badge bg-secondary">{{ ucfirst($user->profile_verification_status) }}</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Joined</span>
                        <span class="fw-bold">{{ $user->created_at->format('d M Y') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Information</h5>
                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Info
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-4 text-muted">Full Name</div>
                        <div class="col-sm-8 fw-bold">{{ $user->first_name }} {{ $user->last_name }}</div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-sm-4 text-muted">Email</div>
                        <div class="col-sm-8 fw-bold">{{ $user->email }}</div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-sm-4 text-muted">Phone</div>
                        <div class="col-sm-8 fw-bold">{{ $user->phone ?? 'N/A' }}</div>
                    </div>
                    @if($user->company_name)
                    <div class="row mb-4">
                        <div class="col-sm-4 text-muted">Company</div>
                        <div class="col-sm-8 fw-bold">{{ $user->company_name }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">KYC Documents</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($user->aadhar_card)
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center">
                                <i class="fas fa-address-card fa-2x mb-2 text-primary"></i>
                                <div class="small fw-bold mb-2">Aadhar Card</div>
                                <a href="{{ asset('storage/' . $user->aadhar_card) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Document</a>
                            </div>
                        </div>
                        @endif

                        @if($user->pan_card)
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center">
                                <i class="fas fa-credit-card fa-2x mb-2 text-primary"></i>
                                <div class="small fw-bold mb-2">PAN Card</div>
                                <a href="{{ asset('storage/' . $user->pan_card) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Document</a>
                            </div>
                        </div>
                        @endif

                        @if($user->account_type === 'reseller_agent')
                        @if($user->registration_doc)
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center">
                                <i class="fas fa-file-invoice fa-2x mb-2 text-info"></i>
                                <div class="small fw-bold mb-2">Business Registration</div>
                                <a href="{{ asset('storage/' . $user->registration_doc) }}" target="_blank" class="btn btn-sm btn-outline-info">View Document</a>
                            </div>
                        </div>
                        @endif
                        @if($user->id_doc)
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center">
                                <i class="fas fa-id-card fa-2x mb-2 text-info"></i>
                                <div class="small fw-bold mb-2">Identity Document</div>
                                <a href="{{ asset('storage/' . $user->id_doc) }}" target="_blank" class="btn btn-sm btn-outline-info">View Document</a>
                            </div>
                        </div>
                        @endif
                        @elseif($user->account_type === 'student')
                        @if($user->id_doc)
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center">
                                <i class="fas fa-id-card fa-2x mb-2 text-info"></i>
                                <div class="small fw-bold mb-2">Student ID</div>
                                <a href="{{ asset('storage/' . $user->id_doc) }}" target="_blank" class="btn btn-sm btn-outline-info">View Document</a>
                            </div>
                        </div>
                        @endif
                        @endif

                        @if(!$user->aadhar_card && !$user->pan_card && !$user->registration_doc && !$user->id_doc)
                        <div class="col-12 text-center py-4 text-muted">
                            <i class="fas fa-info-circle mb-2"></i>
                            <p>No KYC documents uploaded yet.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($user->account_type === 'reseller_agent' && $user->business_name)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Agent Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Business Name</div>
                        <div class="col-sm-8 fw-bold">{{ $user->business_name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Business Type</div>
                        <div class="col-sm-8 fw-bold">{{ $user->business_type }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Registration No.</div>
                        <div class="col-sm-8 fw-bold">{{ $user->registration_number }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection