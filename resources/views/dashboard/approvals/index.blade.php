@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Approval Requests</h4>
        <span class="badge bg-warning text-dark">{{ $pendingUsers->count() }} Pending</span>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User Info</th>
                            <th>Role</th>
                            <th>Registration Details</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingUsers as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="fw-bold text-dark">{{ $user->first_name }} {{ $user->last_name }}</div>
                                </div>
                                <div class="text-muted small">{{ $user->email }}</div>
                                <div class="text-muted small">{{ $user->user_id }}</div>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle px-3">
                                    {{ ucfirst(str_replace('_', ' ', $user->account_type)) }}
                                </span>
                            </td>
                            <td>
                                @if($user->account_type === 'reseller_agent' && $user->agentDetail)
                                <div class="small"><strong>Business:</strong> {{ $user->agentDetail->business_name }}</div>
                                <div class="small text-muted">Type: {{ $user->agentDetail->business_type }}</div>
                                @elseif($user->account_type === 'student' && $user->studentDetail)
                                <div class="small"><strong>Student:</strong> {{ $user->studentDetail->full_name }}</div>
                                <div class="small text-muted">Education: {{ $user->studentDetail->highest_education }}</div>
                                @else
                                <span class="text-muted small">No details provided</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                    <form action="{{ route('approvals.approve', $user->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this user?')">
                                            <i class="fas fa-check me-1"></i> Approve
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-user-clock fa-3x mb-3"></i>
                                    <p>No pending approval requests found.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection