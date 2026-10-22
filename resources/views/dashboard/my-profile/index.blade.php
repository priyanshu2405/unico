@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">My Profile</h4>
            <p class="text-muted small mb-0">Manage your personal information and documents.</p>
        </div>
        <div>
            <span class="badge {{ $user->profile_verification_status == 'verified' ? 'bg-success' : 'bg-warning' }} px-3 py-2 rounded-pill">
                <i class="fas {{ $user->profile_verification_status == 'verified' ? 'fa-check-circle' : 'fa-clock' }} me-1"></i>
                {{ ucfirst($user->profile_verification_status) }}
            </span>
        </div>
    </div>

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
            <!-- Personal Info -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                    <div class="card-body text-center py-4">
                        <div class="mb-3 position-relative d-inline-block">
                            <img id="profile-preview" src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : '/images/default-avatar.png' }}" 
                                 class="rounded-circle shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                            <label for="profile_photo" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 cursor-pointer" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border: 2px solid #fff;">
                                <i class="fas fa-camera"></i>
                                <input type="file" id="profile_photo" name="profile_photo" class="d-none" onchange="previewImage(this)">
                            </label>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="name" class="form-control text-center fw-bold border-0 bg-light" value="{{ $user->name }}" placeholder="Your Name">
                        </div>
                        <p class="text-muted small mb-3">{{ $user->email }}</p>
                        <div class="badge bg-light text-primary px-3 py-2 rounded-pill border border-primary">
                            {{ ucfirst(str_replace('_', ' ', $user->account_type)) }}
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 pb-4 px-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">User ID:</span>
                            <span class="fw-bold small">{{ $user->user_id }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 align-items-center">
                            <span class="text-muted small">Phone:</span>
                            <input type="text" name="phone" class="form-control form-control-sm border-0 bg-light fw-bold text-end" style="width: 150px;" value="{{ $user->phone ?? '' }}">
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Joined:</span>
                            <span class="fw-bold small">{{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Wallet/Points Stats -->
                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h6 class="fw-bold mb-0">Balance & Points</h6>
                    </div>
                    <div class="card-body px-4 pb-4">
                        @php
                            $uCurr = $user->currency ?? 'USD';
                            $uBalLocal = \App\Services\CurrencyService::convert((float)($user->wallet_balance ?? 0), 'USD', $uCurr);
                        @endphp
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <div class="text-muted small mb-1">Wallet Balance</div>
                            <div class="h5 fw-bold mb-0 text-primary">{{ $uCurr }} {{ number_format($uBalLocal, 2) }}</div>
                            @if($uCurr !== 'USD')
                                <small class="text-muted">($ {{ number_format($user->wallet_balance, 2) }} USD)</small>
                            @endif
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <div class="text-muted small mb-1">Referral Points</div>
                                    <div class="h5 fw-bold mb-0 text-success">{{ number_format(\App\Models\Order::where('user_id', $user->id)->sum('referral_points')) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <div class="text-muted small mb-1">Bonus Points</div>
                                    <div class="h5 fw-bold mb-0 text-info">{{ number_format(\App\Models\Order::where('user_id', $user->id)->sum('bonus_amount')) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Tabs -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <ul class="nav nav-pills" id="profileTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active rounded-pill px-4 me-2" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">Details</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-pill px-4 me-2" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">Documents</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-4">
                        <div class="tab-content" id="profileTabContent">
                            <!-- Details Tab -->
                            <div class="tab-pane fade show active" id="details" role="tabpanel">
                                @if($user->isStudent())
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Date of Birth</label>
                                            <input type="date" name="dob" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->dob }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">ID Document Type</label>
                                            <select name="id_type" class="form-select bg-light border-0 p-3 rounded-3">
                                                <option value="">Select document type</option>
                                                <option {{ $user->id_type == 'National ID Card' ? 'selected' : '' }}>National ID Card</option>
                                                <option {{ $user->id_type == 'Passport' ? 'selected' : '' }}>Passport</option>
                                                <option {{ $user->id_type == 'Driving License' ? 'selected' : '' }}>Driving License</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">ID Document No</label>
                                            <input type="text" name="id_number" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->id_number }}" placeholder="e.g. CNIC / Passport Number">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Highest Education</label>
                                            <input type="text" name="highest_education" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->highest_education }}" placeholder="e.g. Bachelor of Science">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Passing Year</label>
                                            <input type="number" name="passing_year" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->passing_year }}" placeholder="e.g. 2022">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">WhatsApp No</label>
                                            <input type="tel" name="whatsapp_number" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->whatsapp_number }}" placeholder="+92 3XX XXX XXXX">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Exam Purpose</label>
                                            <select name="exam_purpose" class="form-select bg-light border-0 p-3 rounded-3">
                                                <option value="">Select exam purpose</option>
                                                <option {{ $user->exam_purpose == 'Education' ? 'selected' : '' }}>Education</option>
                                                <option {{ $user->exam_purpose == 'Migration' ? 'selected' : '' }}>Migration</option>
                                                <option {{ $user->exam_purpose == 'Other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Country</label>
                                            <input type="text" name="country" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->country }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">State / Province</label>
                                            <input type="text" name="state" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->state }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">City</label>
                                            <input type="text" name="city" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->city }}">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Address</label>
                                            <textarea name="address" class="form-control bg-light border-0 p-3 rounded-3" rows="2">{{ $user->address }}</textarea>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <h6 class="fw-bold mt-3 mb-2">Preferred Countries</h6>
                                            <div class="d-flex flex-wrap gap-3">
                                                @php
                                                    $preferred = is_array($user->preferred_countries) ? $user->preferred_countries : [];
                                                @endphp
                                                @foreach(['United Kingdom', 'United States', 'Canada', 'Australia'] as $country)
                                                    <label class="d-flex align-items-center gap-2 p-2 px-3 border rounded-pill cursor-pointer {{ in_array($country, $preferred) ? 'bg-primary text-white border-primary' : 'bg-light' }}">
                                                        <input type="checkbox" name="preferred_countries[]" value="{{ $country }}" class="d-none" {{ in_array($country, $preferred) ? 'checked' : '' }} onchange="this.parentElement.classList.toggle('bg-primary'); this.parentElement.classList.toggle('text-white'); this.parentElement.classList.toggle('border-primary'); this.parentElement.classList.toggle('bg-light');">
                                                        <span>{{ $country }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                                <h6 class="fw-bold mb-0">Linked Bank Accounts</h6>
                                                <a href="{{ route('bank.link') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Manage Banks</a>
                                            </div>
                                            @php
                                                $linkedBanks = \App\Models\BankAccountModel::where('user_id', auth()->id())->get();
                                            @endphp
                                            @if($linkedBanks->count() > 0)
                                                <div class="row g-3">
                                                    @foreach($linkedBanks as $bank)
                                                    <div class="col-md-6">
                                                        <div class="p-3 bg-light border rounded-3">
                                                            <div class="fw-bold">{{ $bank->bank_name }}</div>
                                                            <div class="text-muted small">{{ $bank->account_holder_name }}</div>
                                                            <div class="text-muted font-monospace small">•••• {{ substr($bank->account_number, -4) }}</div>
                                                            <div class="text-muted small">{{ $bank->account_type }}</div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="p-3 bg-light border rounded-3 text-center text-muted">
                                                    <p class="mb-0 small">No bank accounts linked yet.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($user->isAgent() || $user->account_type === 'reseller_agent')
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Business Name</label>
                                            <input type="text" name="business_name" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->business_name }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Business Type</label>
                                            <select name="business_type" class="form-select bg-light border-0 p-3 rounded-3">
                                                <option value="">Select business type</option>
                                                <option {{ $user->business_type == 'Company' ? 'selected' : '' }}>Company</option>
                                                <option {{ $user->business_type == 'AOP' ? 'selected' : '' }}>AOP</option>
                                                <option {{ $user->business_type == 'Firm' ? 'selected' : '' }}>Firm</option>
                                                <option {{ $user->business_type == 'Proprietorship' ? 'selected' : '' }}>Proprietorship</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Registration Number</label>
                                            <input type="text" name="registration_number" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->registration_number }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Representative</label>
                                            <input type="text" name="representative_name" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->representative_name }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Designation</label>
                                            <input type="text" name="designation" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->designation }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Website</label>
                                            <input type="url" name="website" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->website }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">WhatsApp No</label>
                                            <input type="tel" name="whatsapp_number" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->whatsapp_number }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Country</label>
                                            <input type="text" name="country" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->country }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">State / Province</label>
                                            <input type="text" name="state" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->state }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">City</label>
                                            <input type="text" name="city" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->city }}">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Address</label>
                                            <textarea name="address" class="form-control bg-light border-0 p-3 rounded-3" rows="2">{{ $user->address }}</textarea>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                                <h6 class="fw-bold mb-0">Linked Bank Accounts</h6>
                                                <a href="{{ route('bank.link') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Manage Banks</a>
                                            </div>
                                            @php
                                                $linkedBanks = \App\Models\BankAccountModel::where('user_id', auth()->id())->get();
                                            @endphp
                                            @if($linkedBanks->count() > 0)
                                                <div class="row g-3">
                                                    @foreach($linkedBanks as $bank)
                                                    <div class="col-md-6">
                                                        <div class="p-3 bg-light border rounded-3">
                                                            <div class="fw-bold">{{ $bank->bank_name }}</div>
                                                            <div class="text-muted small">{{ $bank->account_holder_name }}</div>
                                                            <div class="text-muted font-monospace small">•••• {{ substr($bank->account_number, -4) }}</div>
                                                            <div class="text-muted small">{{ $bank->account_type }}</div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="p-3 bg-light border rounded-3 text-center text-muted">
                                                    <p class="mb-0 small">No bank accounts linked yet.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($user->account_type === 'support_team' || $user->account_type === 'manager')
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Full Name</label>
                                            <input type="text" name="name" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->name }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Date of Birth</label>
                                            <input type="date" name="dob" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->dob }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Designation</label>
                                            <input type="text" name="designation" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->designation }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">WhatsApp No</label>
                                            <input type="tel" name="whatsapp_number" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->whatsapp_number }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Social Media Link</label>
                                            <input type="url" name="social_link" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->social_link }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Country</label>
                                            <input type="text" name="country" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->country }}">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="text-muted small mb-1 d-block text-uppercase fw-bold">Address</label>
                                            <textarea name="address" class="form-control bg-light border-0 p-3 rounded-3" rows="2">{{ $user->address }}</textarea>
                                        </div>

                                        <div class="col-md-12">
                                            <h6 class="fw-bold mt-3 mb-2">Reference Information</h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="text-muted small mb-1">Reference Name</label>
                                                    <input type="text" name="reference_name" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->reference_name }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small mb-1">Organization</label>
                                                    <input type="text" name="organization_name" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->organization_name }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small mb-1">Reference Email</label>
                                                    <input type="email" name="reference_email" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->reference_email }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small mb-1">Reference Contact</label>
                                                    <input type="text" name="reference_phone" class="form-control bg-light border-0 p-3 rounded-3" value="{{ $user->reference_phone }}">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                                <h6 class="fw-bold mb-0">Linked Bank Accounts</h6>
                                                <a href="{{ route('bank.link') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Manage Banks</a>
                                            </div>
                                            @php
                                                $linkedBanks = \App\Models\BankAccountModel::where('user_id', auth()->id())->get();
                                            @endphp
                                            @if($linkedBanks->count() > 0)
                                                <div class="row g-3">
                                                    @foreach($linkedBanks as $bank)
                                                    <div class="col-md-6">
                                                        <div class="p-3 bg-light border rounded-3">
                                                            <div class="fw-bold">{{ $bank->bank_name }}</div>
                                                            <div class="text-muted small">{{ $bank->account_holder_name }}</div>
                                                            <div class="text-muted font-monospace small">•••• {{ substr($bank->account_number, -4) }}</div>
                                                            <div class="text-muted small">{{ $bank->account_type }}</div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="p-3 bg-light border rounded-3 text-center text-muted">
                                                    <p class="mb-0 small">No bank accounts linked yet.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-5">
                                        <i class="fas fa-info-circle text-muted fa-3x mb-3"></i>
                                        <p class="text-muted">Standard profile details available in Account Settings.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Documents Tab -->
                            <div class="tab-pane fade" id="documents" role="tabpanel">
                                <div class="row g-4">
                                    @php
                                        $docs = [];
                                        if($user->isStudent()) {
                                            $docs['ID Document'] = ['path' => $user->id_doc, 'field' => 'id_doc'];
                                            $docs['Final ID Document'] = ['path' => $user->id_doc_final, 'field' => 'id_doc_final'];
                                        } elseif($user->isAgent() || $user->account_type === 'reseller_agent') {
                                            $docs['Business Registration'] = ['path' => $user->registration_doc, 'field' => 'registration_doc'];
                                            $docs['Identity Document'] = ['path' => $user->id_doc, 'field' => 'id_doc'];
                                        } elseif($user->account_type === 'support_team' || $user->account_type === 'manager') {
                                            $docs['ID Document'] = ['path' => $user->id_document, 'field' => 'id_document'];
                                            $docs['Reference Letter'] = ['path' => $user->reference_letter, 'field' => 'reference_letter'];
                                        }
                                    @endphp

                                    @foreach($docs as $name => $data)
                                        <div class="col-md-6">
                                            <div class="p-3 border rounded-3 bg-light">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                                                        <div>
                                                            <div class="fw-bold small">{{ $name }}</div>
                                                            <div class="text-muted extra-small">Uploaded Document</div>
                                                        </div>
                                                    </div>
                                                    @if($data['path'])
                                                    <a href="{{ asset('storage/' . $data['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @endif
                                                </div>
                                                <div class="mt-2">
                                                    <label class="btn btn-sm btn-light border w-100 rounded-pill d-flex align-items-center justify-content-center gap-1">
                                                        <i class="fas fa-upload"></i> 
                                                        <span class="file-label-text">Update File</span>
                                                        <input type="file" name="{{ $data['field'] }}" class="d-none" onchange="updateFileLabel(this)">
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 pb-4 px-4 text-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 shadow-sm">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@if ($errors->any())
    @push('scripts')
    <script>
        @foreach ($errors->all() as $error)
            toastr.error("{{ $error }}");
        @endforeach
    </script>
    @endpush
@endif

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile-preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function updateFileLabel(input) {
        if (input.files && input.files[0]) {
            const fileName = input.files[0].name;
            const labelText = input.parentElement.querySelector('.file-label-text');
            if (labelText) {
                labelText.textContent = fileName.length > 25 ? fileName.substring(0, 22) + '...' : fileName;
            }
            const icon = input.parentElement.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-check text-success';
            }
        }
    }
</script>

<style>
    .nav-pills .nav-link.active {
        background-color: #5e5ce6;
    }
    .nav-pills .nav-link {
        color: #666;
        font-weight: 500;
        background-color: #f8f9fa;
    }
    .extra-small {
        font-size: 0.75rem;
    }
</style>
@endsection
