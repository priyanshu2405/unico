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
                <th>email ID(official)</th>
                @endif
                <th>contact No.</th>
                <th>Customer Support</th>
                <th>Response Time</th>
                <th>Rating By Customer</th>
                <!-- <th>Status</th> -->
                <!-- <th class="text-end">Actions</th> -->
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                <td>{{ $user->user_id }}</td>
                <td>{{ $user->created_at ? $user->created_at->format('d M Y') : 'N/A' }}</td>
                <td>{{ $user->last_login_at ? $user->last_login_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Never' }}</td>
                <td>{{ $user->last_logout_at ? $user->last_logout_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Never' }}</td>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                @endif
                <td>{{ $user->country}}</td>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <td>{{ $user->email }}</td>
                @endif
                <td>{{ $user->phone }}</td>
                <td><span class="badge bg-info-subtle text-info">N/A</span></td>
                <td><span class="badge bg-info-subtle text-info">N/A</span></td>
                <td>
                    <div class="d-flex align-items-center">
                        @php $rating = number_format($user->rating, 1); @endphp
                        <span class="fw-bold text-warning me-1">{{ $rating }}</span>
                        <div class="text-warning small">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= round($user->rating) ? '' : 'text-muted opacity-25' }}"></i>
                            @endfor
                        </div>
                    </div>
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
                <td colspan="13" class="text-center py-5 text-muted">No users found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('dashboard.partials.custom-pagination', ['items' => $users])