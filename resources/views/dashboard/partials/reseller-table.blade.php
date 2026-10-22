<div class="table-responsive">
    <table class="table table-hover align-middle" style="white-space: nowrap;">
        <thead class="table-light">
            <tr>
                <th>Sr. No.</th>
                <th>User ID</th>
                <th>Date of Reg.</th>
                <th>Last Login</th>
                <th>Last Logout</th>
                <th>Category</th>
                <th>Limit</th>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <th>Full Name</th>
                @endif
                <!-- <th>Last Name</th> -->
                <th>Country</th>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <th>email ID(official)</th>
                @endif
                <th>contact No.</th>
                <th>Connected Users</th>
                <th>Total Vouchers (Network)</th>
                <th>Total Revenue (Network)</th>
                <th>Disputed Payments</th>
                <th>Available Referral Points</th>
                <th>Available Bonus Points</th>
                <th class="text-center">Sales Action</th>
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
                <td>
                    @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_edit_user)
                    <select class="form-select form-select-sm category-select" data-user-id="{{ $user->id }}" style="min-width:140px;">
                        <option value="silver" {{ $user->category == 'silver' ? 'selected' : '' }}>🥈 Silver</option>
                        <option value="gold" {{ $user->category == 'gold' ? 'selected' : '' }}>🥇 Gold</option>
                        <option value="diamond" {{ $user->category == 'diamond' ? 'selected' : '' }}>💎 Diamond</option>
                    </select>
                    @else
                    <span class="badge bg-secondary">{{ ucfirst($user->category) }}</span>
                    @endif
                </td>
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
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                @endif
                <!-- <td>{{ $user->last_name }}</td> -->
                <td>{{ $user->country }}</td>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <td>{{ $user->email }}</td>
                @endif
                <td>{{ $user->phone }}</td>
                <td>
                    <div class="d-flex flex-column gap-1">
                        <span class="fw-bold text-dark">{{ $user->subAgents->count() }} Total</span>
                        <div class="d-flex gap-1" style="font-size: 11px;">
                            <span class="badge bg-primary-subtle text-primary" title="Connected Agents">{{ $user->connected_agents_count }} Agents</span>
                            <span class="badge bg-info-subtle text-info" title="Connected Students">{{ $user->connected_students_count }} Students</span>
                        </div>
                    </div>
                </td>
                <td>
                    <a href="{{ route('sales.index', ['reseller_id' => $user->id]) }}" class="text-decoration-none fw-bold text-primary" title="View all vouchers purchased by Reseller and Sub-Agents">
                        {{ number_format($user->total_network_vouchers_count) }}
                    </a>
                    @if($user->total_subagent_vouchers_count > 0)
                    <div class="text-muted" style="font-size: 11px;">
                        Direct: {{ number_format($user->total_direct_vouchers_count) }} | Sub: {{ number_format($user->total_subagent_vouchers_count) }}
                    </div>
                    @endif
                </td>
                <td>
                    <a href="{{ route('sales.index', ['reseller_id' => $user->id]) }}" class="text-decoration-none fw-bold text-success" title="View total revenue by Reseller and Sub-Agents">
                        {{ $user->currency }} {{ number_format($user->total_network_sales_amount, 2) }}
                    </a>
                    @if($user->total_subagent_sales_amount > 0)
                    <div class="text-muted" style="font-size: 11px;">
                        Direct: {{ number_format($user->total_direct_sales_amount, 2) }} | Sub: {{ number_format($user->total_subagent_sales_amount, 2) }}
                    </div>
                    @endif
                </td>
                <td>{{ $user->orders->where('status', 'disputed')->count() }}</td>
                <td>{{ number_format($user->total_referral_points, 0) }}</td>
                <td>{{ number_format($user->total_bonus_points, 2) }}</td>
                <td class="text-center">
                    <a href="{{ route('sales.index', ['reseller_id' => $user->id]) }}" class="btn btn-sm btn-outline-primary px-3 shadow-sm rounded-pill" title="View Sales by Agent, Student, Country, Brand">
                        <i class="fas fa-chart-line me-1"></i> View Sales
                    </a>
                </td>
                <!-- <td>
                    @php $canFreeze = auth()->user()->account_type !== 'manager' || auth()->user()->can_freeze_user; @endphp
                    <span class="badge px-3 py-2 {{ $canFreeze ? 'user-status-toggle' : '' }} {{ $user->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}"
                        @if($canFreeze) data-id="{{ $user->id }}" style="cursor:pointer;" title="Click to {{ $user->is_active ? 'freeze' : 'unfreeze' }}" @endif>
                        @if($user->is_active) <i class="fas fa-unlock me-1"></i> Active @else <i class="fas fa-lock me-1"></i> Frozen @endif
                    </span>
                </td> -->
                <!-- <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                        @if(auth()->user()->account_type === 'admin')
                        <form action="{{ route('users.impersonate', $user->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light" title="Login as {{ $user->first_name }}">
                                <i class="fas fa-external-link-alt text-warning"></i>
                            </button>
                        </form>
                        @endif
                        <a class="btn btn-sm btn-light" href="{{ route('users.show', $user->id) }}" title="View"><i class="fas fa-eye text-primary"></i></a>
                        @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_edit_user)
                        <a class="btn btn-sm btn-light" href="{{ route('users.edit', $user->id) }}" title="Edit"><i class="fas fa-edit text-info"></i></a>
                        @endif
                        @if(auth()->user()->account_type === 'admin')
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="ajax-action">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light" title="Delete"><i class="fas fa-trash text-danger"></i></button>
                        </form>
                        @endif
                    </div>
                </td> -->
            </tr>
            @empty
            <tr>
                <td colspan="19" class="text-center py-5 text-muted">No users found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('dashboard.partials.custom-pagination', ['items' => $users])