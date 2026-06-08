@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="fw-bold text-dark">System Audit Logs</h2>
            <p class="text-muted">Track all administrative actions and system events.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Recent Activities</h5>
            <span class="badge bg-primary">{{ $logs->total() }} Total Logs</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>
                                @if($log->user)
                                <div class="fw-bold">{{ $log->user->name }}</div>
                                <div class="small text-muted">{{ $log->user->email }}</div>
                                @else
                                <span class="text-muted">System / Unknown</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-info px-3 py-2">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td>
                                <div class="text-wrap" style="max-width: 400px;">
                                    {{ $log->description }}
                                </div>
                            </td>
                            <td>
                                <code class="small">{{ $log->ip_address }}</code>
                            </td>
                            <td>
                                <div class="small text-muted">
                                    {{ $log->created_at ? $log->created_at->format('d M Y, H:i:s') : 'N/A' }}
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">No audit logs found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                @include('dashboard.partials.custom-pagination', ['items' => $logs])
            </div>
        </div>
    </div>
</div>
@endsection