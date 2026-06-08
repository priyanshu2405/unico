@extends('layouts.master')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Low Stock Alerts</h4>
        <button class="btn btn-primary btn-sm">
            <i class="fas fa-bell me-1"></i> Notification Settings
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Voucher Type</th>
                            <th>Remaining Quantity</th>
                            <th>Threshold</th>
                            <th>Status</th>
                            <th>Auto Notification</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                        <tr>
                            <td class="fw-bold text-dark">{{ $alert['type'] }}</td>
                            <td>
                                <span class="badge {{ $alert['remaining'] <= $alert['threshold'] ? 'bg-danger' : 'bg-success' }}">
                                    {{ $alert['remaining'] }}
                                </span>
                            </td>
                            <td>{{ $alert['threshold'] }}</td>
                            <td>
                                @if($alert['remaining'] <= $alert['threshold'])
                                    <span class="text-danger small fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Low Stock</span>
                                    @else
                                    <span class="text-success small fw-bold">Good</span>
                                    @endif
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" checked>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary">Add Stock</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection