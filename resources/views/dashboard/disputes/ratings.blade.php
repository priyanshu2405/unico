@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Staff Ratings & Feedback</h2>
        <div class="badge bg-primary px-3 py-2">
            Average Rating: {{ number_format(auth()->user()->rating, 1) }} ★
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small fw-bold text-muted">
                    <tr>
                        <th class="ps-4 py-3">User</th>
                        <th class="py-3">Dispute ID</th>
                        <th class="py-3">Subject</th>
                        <th class="py-3">Rating</th>
                        <th class="py-3">Feedback</th>
                        <th class="py-3">Staff</th>
                        <th class="text-end pe-4 py-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ratings as $dispute)
                    <tr style="cursor: pointer;" onclick="window.location='{{ route('disputes.show', $dispute->id) }}'">
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $dispute->user->name }}</div>
                                    <div class="text-muted small">ID: {{ $dispute->user->user_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $dispute->dispute_id }}</span></td>
                        <td><div class="text-dark fw-medium">{{ Str::limit($dispute->subject, 30) }}</div></td>
                        <td>
                            <div class="text-warning">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $dispute->rating ? '' : 'text-muted' }} opacity-50"></i>
                                @endfor
                                <span class="ms-1 fw-bold text-dark">{{ $dispute->rating }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="text-muted small text-truncate" style="max-width: 200px;" title="{{ $dispute->feedback }}">
                                {{ $dispute->feedback ?: 'No feedback provided' }}
                            </div>
                        </td>
                        <td>
                            @if($dispute->assignedStaff)
                            <div class="d-flex align-items-center">
                                <span class="small fw-semibold text-primary">{{ $dispute->assignedStaff->name }}</span>
                            </div>
                            @else
                            <span class="text-muted small">N/A</span>
                            @endif
                        </td>
                        <td class="text-end pe-4 text-muted small">
                            {{ $dispute->updated_at->format('M d, Y') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-star fa-3x text-light mb-3 d-block"></i>
                            <span class="text-muted">No ratings received yet.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        @include('dashboard.partials.custom-pagination', ['items' => $ratings])
    </div>
</div>

<style>
    .avatar-sm {
        min-width: 35px;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.02);
    }
</style>
@endsection
