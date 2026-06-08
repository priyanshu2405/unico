<div class="table-responsive">
    <table class="table table-hover align-middle" style="white-space: nowrap;">
        <thead class="table-light">
            <tr>
                <th>Sr. No.</th>
                <th>User ID</th>
                <th>Date of Reg.</th>
                <th>Last Login</th>
                <th>Last Logout</th>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <th>Full Name</th>
                @endif
                <th>Country</th>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <th>Email ID</th>
                @endif
                <th>Limit</th>
                <th>Highest Education</th>
                <th>Contact No.</th>
                <th>Vouchers Purchased</th>
                <th>Revenue Paid</th>
                <th>Disputed Payments</th>
                <th>Available Referral Points</th>
                <th>Available Bonus Points</th>
                <!-- <th>Status</th> -->
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                <td>{{ $user->user_id }}</td>
                <td>{{ $user->created_at ? $user->created_at->timezone('Asia/Kolkata')->format('d M Y') : 'N/A' }}</td>
                <td>{{ $user->last_login_at ? $user->last_login_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Never' }}</td>
                <td>{{ $user->last_logout_at ? $user->last_logout_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Never' }}</td>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                @endif
                <td>{{ $user->country }}</td>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <td>{{ $user->email }}</td>
                @endif
                <td>
                    @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_edit_user)
                    <input type="number" class="form-control form-control-sm update-limit" 
                           data-user-id="{{ $user->id }}" 
                           value="{{ $user->voucher_limit }}" 
                           min="0" style="width: 70px;">
                    @else
                    {{ $user->voucher_limit }}
                    @endif
                </td>
                <td>{{ $user->highest_education ?? 'N/A' }}</td>
                <td>{{ $user->phone }}</td>
                <td>{{ $user->orders_count ?? 0 }}</td>
                <td>{{ number_format($user->total_revenue ?? 0, 2) }}</td>
                <td>{{ $user->disputed_payments ?? 0 }}</td>
                <td>{{ $user->orders->sum('referral_points') }}</td>
                <td>{{ number_format($user->orders->sum('bonus_amount'), 2) }}</td>
                <!-- <td>
                    @php $canFreeze = auth()->user()->account_type !== 'manager' || auth()->user()->can_freeze_user; @endphp
                    <span class="badge px-3 py-2 {{ $canFreeze ? 'user-status-toggle' : '' }} {{ $user->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}"
                        @if($canFreeze) data-id="{{ $user->id }}" style="cursor:pointer;" title="Click to {{ $user->is_active ? 'freeze' : 'unfreeze' }}" @endif>
                        @if($user->is_active) <i class="fas fa-unlock me-1"></i> Active @else <i class="fas fa-lock me-1"></i> Frozen @endif
                    </span>
                </td> -->
            </tr>
            @empty
            <tr>
                <td colspan="16" class="text-center py-5 text-muted">No students found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('dashboard.partials.custom-pagination', ['items' => $users])