<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">Sr. No.</th>
                <th class="text-nowrap">Manager ID</th>
                <th class="text-nowrap">Date of Reg.</th>
                <th class="text-nowrap">Last Login</th>
                <th class="text-nowrap">Last Logout</th>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <th class="text-nowrap">Full Name</th>
                @endif
                <th class="text-nowrap">Country</th>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_view_user_email_name)
                <th class="text-nowrap">Email</th>
                @endif
                <th class="text-nowrap">Contact</th>
                <th class="text-nowrap">Status</th>
                <!-- <th class="text-end">Actions</th> -->
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                <td><span class="fw-bold text-primary">{{ $user->user_id }}</span></td>
                <td>
                    <div class="small">{{ $user->created_at->format('d M Y') }}</div>
                    <div class="text-muted extra-small">{{ $user->created_at->format('H:i') }}</div>
                </td>
                <td class="small text-nowrap">{{ $user->last_login_at ? $user->last_login_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Never' }}</td>
                <td class="small text-nowrap">{{ $user->last_logout_at ? $user->last_logout_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Never' }}</td>

                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                <td><span class="badge bg-light text-dark border">{{ $user->country ?: 'N/A' }}</span></td>
                <td>
                    <div class="small">{{ $user->email }}</div>
                </td>
                <td>
                    <div class="small">{{ $user->phone }}</div>
                </td>
                <td>
                    <div class="status-toggle" data-user-id="{{ $user->id }}">
                        <span class="badge px-3 py-2 cursor-pointer status-badge {{ $user->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}"
                            onclick="toggleUserStatus({{ $user->id }})">
                            @if($user->is_active)
                            <i class="fas fa-check-circle me-1"></i> Active
                            @else
                            <i class="fas fa-lock me-1"></i> Frozen
                            @endif
                        </span>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center py-5 text-muted">No managers found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('dashboard.partials.custom-pagination', ['items' => $users])