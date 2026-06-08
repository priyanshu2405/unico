<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-4">User Info</th>
                <th>Role</th>
                <th>Status</th>
                <th>Risk Level</th>
                <th>Compliance Flags</th>
                <th class="text-end pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pendingUsers as $user)
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-3 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                            {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                        </div>
                        <div>
                            <div class="fw-bold text-dark">{{ $user->first_name }} {{ $user->last_name }}</div>
                            <div class="text-muted small">{{ $user->user_id }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark border px-2">{{ ucfirst(str_replace('_', ' ', $user->account_type)) }}</span>
                </td>
                <td>
                    <select class="form-select form-select-sm status-update-dropdown"
                        data-user-id="{{ $user->id }}"
                        data-action="{{ route('approvals.update-status', $user->id) }}"
                        style="width: 130px;">
                        <option value="pending" {{ $user->profile_verification_status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="verified" {{ $user->profile_verification_status == 'verified' ? 'selected' : '' }}>Verified</option>
                        <option value="rejected" {{ $user->profile_verification_status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="suspended" {{ $user->profile_verification_status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </td>
                <td>
                    <span class="badge bg-info-subtle text-info">Low</span>
                </td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        @if($user->email_verified_at)
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">Email Verified</span>
                        @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">Email Unverified</span>
                        @endif

                        @if($user->aadhar_card || $user->pan_card || ($user->agentDetail && $user->agentDetail->id_doc))
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">Docs Uploaded</span>
                        @endif
                    </div>
                </td>
                <td class="text-end pe-4">
                    <a href="{{ route('kyc.show', $user->id) }}" class="btn btn-light btn-sm" title="View Detail">
                        <i class="fas fa-eye text-primary"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                        <p>No applications found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('dashboard.partials.custom-pagination', ['items' => $pendingUsers])