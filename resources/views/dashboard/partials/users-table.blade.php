<div class="table-responsive">
    <table class="table table-hover align-middle" style="white-space: nowrap;">
        <thead class="table-light">
            <tr>
                  <th>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                    </div>
                </th>
                <th>User Info</th>
                <th>Role</th>
                <th>Account Status</th>
                <th>Verified Status</th>
                <th>Country</th>
                <th>State</th>
                <th>Risk Level</th>
                <th>Compliance Flags</th>

                <th>Created At</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse($users as $user)
            <tr>
                <td>
                    <div class="form-check">
                        <input class="form-check-input user-checkbox" type="checkbox" value="{{ $user->id }}">
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('images/user.png') }}"
                            class="rounded-circle me-3" width="40" height="40"
                            style="object-fit: cover; border: 1px solid #eee;">
                        <div>
                            <div class="fw-bold text-dark">
                                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                                <div class="fw-bold text-dark">
                                    {{ $user->first_name }} {{ $user->last_name }}
                                </div>
                                @endif


                                <div class="text-muted small">
                                    <div class="user_id">{{ $user->user_id }}</div>
                                    @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                                    <div class="user_id_email">{{ $user->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                </td>

                <td>
                    <span class="badge rounded-pill bg-light text-dark border px-3">
                        {{ ucfirst(str_replace('_', ' ', $user->account_type)) }}
                    </span>
                </td>



                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge px-3 py-2 {{ (auth()->user()->account_type === 'manager' && !auth()->user()->can_freeze_user) ? '' : 'user-status-toggle' }}    {{ $user->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}" data-id="{{ $user->id }}" style=" {{ (auth()->user()->account_type === 'manager' && !auth()->user()->can_freeze_user) ? 'cursor:default;' : 'cursor:pointer;' }}" title="{{ (auth()->user()->account_type === 'manager' && !auth()->user()->can_freeze_user) ? 'No permission to change status' : 'Click to ' . ($user->is_active ? 'freeze' : 'unfreeze') }}">
                            @if($user->is_active)
                            <i class="fas fa-unlock me-1"></i> Active
                            @else
                            <i class="fas fa-lock me-1"></i> Frozen
                            @endif
                        </span>

                    </div>
                </td>

                <td>
                    <span class="badge px-3 py-2 {{ (auth()->user()->account_type === 'manager' && !auth()->user()->can_approve_user) ? '' : 'verification-status-toggle' }}  {{ in_array($user->profile_verification_status, ['verified', 'approved']) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}" data-id="{{ $user->id }}" style="{{ (auth()->user()->account_type === 'manager' && !auth()->user()->can_approve_user) ? 'cursor:default;' : 'cursor:pointer;' }}" title="{{ (auth()->user()->account_type === 'manager' && !auth()->user()->can_approve_user) ? 'No permission to approve users' : 'Click to ' . (in_array($user->profile_verification_status, ['verified', 'approved']) ? 'mark as pending' : 'verify') }}">
                        @if(in_array($user->profile_verification_status, ['verified', 'approved']))
                        <i class="fas fa-check-circle me-1"></i> Approved
                        @else
                        <i class="fas fa-clock me-1"></i> Pending
                        @endif
                    </span>
                </td>
                <td>
                    <div class="fw-bold text-dark">
                        {{ $user->country }}
                    </div>
                </td>
                <td>
                    <div class="fw-bold text-dark">
                        {{ $user->state}}
                    </div>
                </td>
                <td>
                    @php
                    $risk = \App\Models\CountryRiskLevel::where('country_name', $user->country_iso)->first();
                    @endphp
                    <span class="badge {{ ($risk->risk_level ?? 'Low') === 'High' ? 'bg-danger-subtle text-danger' : (($risk->risk_level ?? 'Low') === 'Medium' ? 'bg-warning-subtle text-warning' : 'bg-info-subtle text-info') }} w-100 py-2">
                        {{ strtoupper($risk->risk_level ?? 'Low') }}
                    </span>

                </td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        @if($user->email_verified_at)
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">Email Verified</span>
                        @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">Email Unverified</span>
                        @endif


                        {{-- Shufti Verification Status --}}
                        @if($user->shufti_status === 'pending')
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.7rem;">
                            Document Verification Pending
                        </span>

                        @elseif($user->shufti_status === 'approved')
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">
                            Document Verified
                        </span>

                        @elseif(in_array($user->shufti_status, ['declined', 'failed']))
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">
                            Document Verification Failed
                        </span>

                        @else

                        @endif
                    </div>
                </td>
                <td>
                    <div class="fw-bold text-dark">
                        {{ $user->created_at ? $user->created_at->format('d M Y') : 'N/A' }}
                    </div>
                </td>

                {{-- Actions (Clean) --}}
                <td class=" text-end">
                    <div class="d-flex justify-content-end gap-1">
                        @if(auth()->user()->account_type === 'admin' || (auth()->user()->account_type === 'manager' && auth()->user()->can_impersonate_user))
                        <form action="{{ route('users.impersonate', $user->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light" title="Login as {{ $user->first_name }}">
                                <i class="fas fa-external-link-alt text-warning"></i>
                            </button>
                        </form>
                        @endif

                        <a class="btn btn-sm btn-light"
                            href="{{ route('users.show', $user->id) }}" title="View">
                            <i class="fas fa-eye text-primary"></i>
                        </a>

                        @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_edit_user)
                        <a class="btn btn-sm btn-light"
                            href="{{ route('users.edit', $user->id) }}" title="Edit">
                            <i class="fas fa-edit text-info"></i>
                        </a>
                        @endif

                        @if(auth()->user()->account_type !== 'manager')
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="ajax-action">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light" title="Delete">
                                <i class="fas fa-trash text-danger"></i>
                            </button>
                        </form>
                        @endif



                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center py-5 text-muted">
                    No users found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('dashboard.partials.custom-pagination', ['items' => $users])
