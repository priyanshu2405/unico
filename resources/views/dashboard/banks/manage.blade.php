@extends('layouts.master')

@section('content')
<div class="container-fluid py-4" style="background-color: #f8fafc; min-height: 100vh;">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Admin Payment Methods</h2>
            <p class="text-muted">Manage bank accounts and QR codes for user payments.</p>
        </div>
        <button class="btn btn-primary px-4 py-2 fw-bold" style="border-radius: 10px; background-color: #2563eb;" data-bs-toggle="modal" data-bs-target="#addMethodModal">
            <i class="fas fa-plus me-2"></i> Add Payment Method
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 12px;">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Payment Methods Grid -->
    <div class="row g-4">
        @forelse($methods as $method)
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm p-4 position-relative h-100" style="border-radius: 20px; border-left: 5px solid {{ $method->method_type == 'qr' ? '#8b5cf6' : ($method->method_type == 'upi' ? '#10b981' : '#2563eb') }} !important;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 45px; height: 45px; background-color: {{ $method->method_type == 'qr' ? '#8b5cf6' : ($method->method_type == 'upi' ? '#10b981' : '#2563eb') }};">
                            @if($method->method_type == 'bank')
                            <i class="fas fa-university"></i>
                            @elseif($method->method_type == 'upi')
                            <i class="fas fa-mobile-alt"></i>
                            @else
                            <i class="fas fa-qrcode"></i>
                            @endif
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-capitalize">{{ $method->method_type }}</h5>
                            <span class="small text-muted">{{ $method->bank_name ?: 'Global' }}</span>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input status-toggle" type="checkbox" data-id="{{ $method->id }}" {{ $method->status ? 'checked' : '' }}>
                    </div>
                </div>

                <div class="mb-4">
                    @if($method->method_type == 'bank')
                    <div class="mb-2">
                        <p class="small text-muted mb-0">Account Holder</p>
                        <p class="fw-bold mb-0">{{ $method->account_holder_name }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="small text-muted mb-0">Account Number</p>
                        <p class="fw-bold mb-0">{{ $method->account_number }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="small text-muted mb-0">Bank Code Type</p>
                        <p class="fw-bold mb-0">{{ $method->bank_code_type }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="small text-muted mb-0">Bank Code Value</p>
                        <p class="fw-bold mb-0">{{ $method->bank_code_value }}</p>
                    </div>
                    @elseif($method->method_type == 'upi')
                    <div class="mb-2">
                        <p class="small text-muted mb-0">UPI ID</p>
                        <p class="fw-bold mb-0">{{ $method->upi_id }}</p>
                    </div>
                    @endif

                    @if($method->qr_code)
                    <div class="text-center mt-3">
                        <img src="{{ asset('storage/' . $method->qr_code) }}" alt="QR Code" class="img-fluid rounded border p-1" style="max-height: 120px;">
                    </div>
                    @endif

                    @if($method->notes)
                    <p class="small text-muted mt-3 italic">
                        <i class="fas fa-info-circle me-1"></i> {{ $method->notes }}
                    </p>
                    @endif
                </div>

                <div class="d-flex justify-content-end gap-2 mt-auto">
                    <button class="btn btn-light btn-sm rounded-3 edit-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#editMethodModal"
                        data-id="{{ $method->id }}"
                        data-type="{{ $method->method_type }}"
                        data-bank="{{ $method->bank_name }}"
                        data-holder="{{ $method->account_holder_name }}"
                        data-number="{{ $method->account_number }}"
                        data-code-type="{{ $method->bank_code_type }}"
                        data-code-value="{{ $method->bank_code_value }}"
                        data-upi="{{ $method->upi_id }}"
                        data-notes="{{ $method->notes }}">
                        <i class="fas fa-edit text-primary"></i>
                    </button>
                    <form action="{{ route('payment-methods.destroy', $method->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-light btn-sm rounded-3">
                            <i class="fas fa-trash text-danger"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm p-5 text-center" style="border-radius: 20px;">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-credit-card fa-2x text-muted"></i>
                </div>
                <h5 class="fw-bold">No Payment Methods Found</h5>
                <p class="text-muted">Start by adding a bank account or QR code.</p>
                <button class="btn btn-primary px-4 py-2 mt-2" style="border-radius: 10px;" data-bs-toggle="modal" data-bs-target="#addMethodModal">
                    Add Now
                </button>
            </div>
        </div>
        @endforelse
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 24px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h4 class="fw-bold mb-0">Add Payment Method</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('payment-methods.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Method Type</label>
                        <select name="method_type" class="form-select rounded-3 p-2 type-selector" required>
                            <option value="bank">Bank Account</option>
                            <option value="upi">UPI ID</option>
                            <option value="qr">QR Code Only</option>
                        </select>
                    </div>

                    <div class="bank-fields">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Country</label>
                                <input type="text" name="country" class="form-control rounded-3 p-2" placeholder="e.g. USA, UK, India">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control rounded-3 p-2" placeholder="e.g. Chase Bank">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Account Holder Name</label>
                            <input type="text" name="account_holder_name" class="form-control rounded-3 p-2" placeholder="Name as per bank">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Account Number / IBAN</label>
                            <input type="text" name="account_number" class="form-control rounded-3 p-2" placeholder="Account No. or IBAN">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">IFSC Code</label>
                                <input type="text" name="ifsc_code" class="form-control rounded-3 p-2" placeholder="IFSC (India)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">SWIFT/BIC Code</label>
                                <input type="text" name="swift_code" class="form-control rounded-3 p-2" placeholder="SWIFT Code">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Routing Number</label>
                                <input type="text" name="routing_number" class="form-control rounded-3 p-2" placeholder="Routing No.">
                            </div>
                        </div>
                    </div>

                    <div class="upi-fields d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">UPI ID</label>
                            <input type="text" name="upi_id" class="form-control rounded-3 p-2" placeholder="username@upi">
                        </div>
                    </div>

                    <div class="qr-fields d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">QR Code Image</label>
                            <input type="file" name="qr_code" class="form-control rounded-3 p-2">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small">Notes (Visible to user)</label>
                        <textarea name="notes" class="form-control rounded-3 p-2" rows="2" placeholder="Important instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold" style="border-radius: 16px;">Save Payment Method</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 24px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h4 class="fw-bold mb-0">Edit Payment Method</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Method Type</label>
                        <select name="method_type" id="edit_method_type" class="form-select rounded-3 p-2 type-selector" required>
                            <option value="bank">Bank Account</option>
                            <option value="upi">UPI ID</option>
                            <option value="qr">QR Code Only</option>
                        </select>
                    </div>

                    <div class="bank-fields">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Bank Name</label>
                            <input type="text" name="bank_name" id="edit_bank_name" class="form-control rounded-3 p-2">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Account Holder Name</label>
                            <input type="text" name="account_holder_name" id="edit_account_holder_name" class="form-control rounded-3 p-2">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Account Number</label>
                                <input type="text" name="account_number" id="edit_account_number" class="form-control rounded-3 p-2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">IFSC Code</label>
                                <input type="text" name="ifsc_code" id="edit_ifsc_code" class="form-control rounded-3 p-2">
                            </div>
                        </div>
                    </div>

                    <div class="upi-fields d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">UPI ID</label>
                            <input type="text" name="upi_id" id="edit_upi_id" class="form-control rounded-3 p-2">
                        </div>
                    </div>

                    <div class="qr-fields d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">QR Code Image (Leave blank to keep current)</label>
                            <input type="file" name="qr_code" class="form-control rounded-3 p-2">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small">Notes (Visible to user)</label>
                        <textarea name="notes" id="edit_notes" class="form-control rounded-3 p-2" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold" style="border-radius: 16px;">Update Payment Method</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        function toggleFields(selector) {
            let type = $(selector).val();
            let parent = $(selector).closest('.modal-content');

            if (type === 'bank') {
                parent.find('.bank-fields').removeClass('d-none');
                parent.find('.upi-fields').addClass('d-none');
                parent.find('.qr-fields').addClass('d-none');
            } else if (type === 'upi') {
                parent.find('.bank-fields').addClass('d-none');
                parent.find('.upi-fields').removeClass('d-none');
                parent.find('.qr-fields').removeClass('d-none');
            } else if (type === 'qr') {
                parent.find('.bank-fields').addClass('d-none');
                parent.find('.upi-fields').addClass('d-none');
                parent.find('.qr-fields').removeClass('d-none');
            }
        }

        $('.type-selector').on('change', function() {
            toggleFields(this);
        });

        $('.edit-btn').on('click', function() {
            let id = $(this).data('id');
            let type = $(this).data('type');

            $('#editForm').attr('action', '/dashboard/payment-methods/' + id);
            $('#edit_method_type').val(type);
            $('#edit_bank_name').val($(this).data('bank'));
            $('#edit_account_holder_name').val($(this).data('holder'));
            $('#edit_account_number').val($(this).data('number'));
            $('#edit_ifsc_code').val($(this).data('ifsc'));
            $('#edit_upi_id').val($(this).data('upi'));
            $('#edit_notes').val($(this).data('notes'));

            toggleFields('#edit_method_type');
        });

        $('.status-toggle').on('change', function() {
            let id = $(this).data('id');
            $.ajax({
                url: '/dashboard/payment-methods/' + id + '/toggle',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Status updated successfully');
                    }
                }
            });
        });
    });
</script>
@endpush
@endsection