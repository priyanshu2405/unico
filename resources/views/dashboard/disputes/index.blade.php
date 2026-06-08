@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">User Disputes</h2>
        @if(!auth()->user()->isAdmin() && !auth()->user()->isManager() && !auth()->user()->isSupport())
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDisputeModal">
            <i class="fas fa-plus me-2"></i>New Dispute
        </button>
        @endif
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row g-3">
        @forelse($disputes as $dispute)
        <div class="col-12">
            <div class="card border-0 shadow-sm dispute-card" onclick="window.location='{{ route('disputes.show', $dispute->id) }}'" style="cursor: pointer; transition: transform 0.2s;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            @php
                            $statusClass = [
                            'open' => 'bg-warning-subtle text-warning',
                            'pending' => 'bg-primary-subtle text-primary',
                            'resolved' => 'bg-success-subtle text-success',
                            'closed' => 'bg-secondary-subtle text-secondary'
                            ][$dispute->status];
                            @endphp
                            <span class="badge {{ $statusClass }} text-uppercase px-3 py-2 mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                {{ $dispute->status }}
                            </span>
                            <h5 class="fw-bold mb-1">{{ $dispute->subject }}</h5>
                            <p class="text-muted mb-3 text-truncate-2" style="max-width: 800px;">{{ $dispute->description }}</p>

                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('images/user.png') }}" class="rounded-circle me-2" width="32" height="32">
                                    <span class="small fw-semibold text-dark">{{ $dispute->user->name }}</span>
                                </div>
                                <span class="text-muted small">ID: {{ $dispute->dispute_id }}</span>
                                @if($dispute->assignedStaff)
                                <div class="badge bg-info-subtle text-info small">
                                    <i class="fas fa-user-shield me-1"></i> {{ $dispute->assignedStaff->name }}
                                </div>
                                @else
                                <div class="badge bg-secondary-subtle text-secondary small">
                                    <i class="fas fa-user-clock me-1"></i> Unassigned
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small mb-3">{{ $dispute->created_at->format('n/j/Y') }}</div>
                            @php
                            $unreadCount = $dispute->messages()->where('user_id', '!=', auth()->id())->where('is_read', false)->count();
                            @endphp
                            @if($unreadCount > 0)
                            <span class="badge bg-primary rounded-pill px-2 py-1">
                                <i class="fas fa-comment me-1"></i> {{ $unreadCount }}
                            </span>
                            @else
                            <div class="bg-light rounded-pill px-2 py-1 d-inline-block text-muted small">
                                <i class="fas fa-comment me-1"></i> {{ $dispute->messages->count() }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <div class="mb-3">
                <i class="fas fa-comments fa-4x text-light"></i>
            </div>
            <h5 class="text-muted">No disputes found</h5>
        </div>
        @endforelse
    </div>

    <div class="mt-4">
        @include('dashboard.partials.custom-pagination', ['items' => $disputes])
    </div>
</div>

<!-- Create Dispute Modal -->
<div class="modal fade" id="createDisputeModal" tabindex="-1" aria-labelledby="createDisputeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold" id="createDisputeModalLabel">Open New Dispute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('disputes.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Brief summary of the issue" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="Detailed explanation of the problem..." required></textarea>
                    </div>

                </div>
                <div class="modal-footer border-top p-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Submit Dispute</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .dispute-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08) !important;
    }

    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endsection