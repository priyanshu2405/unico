@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Broadcast Center</h4>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Send Notification or Alert</h5>
            <p class="text-muted mb-4">Send a system alert or promotional notification to a specific group of users or everyone.</p>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('broadcast.send') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="target" class="form-label fw-bold">Target Audience <span class="text-danger">*</span></label>
                        <select class="form-select bg-light border-0 @error('target') is-invalid @enderror" id="target" name="target" required>
                            <option value="all" {{ old('target') == 'all' ? 'selected' : '' }}>All Users</option>
                            <option value="manager" {{ old('target') == 'manager' ? 'selected' : '' }}>Managers</option>
                            <option value="support_team" {{ old('target') == 'support_team' ? 'selected' : '' }}>Support Team</option>
                            <option value="student" {{ old('target') == 'student' ? 'selected' : '' }}>Students</option>
                            <option value="reseller_agent" {{ old('target') == 'reseller_agent' ? 'selected' : '' }}>Reseller Agents</option>
                            <option value="agent" {{ old('target') == 'agent' ? 'selected' : '' }}>Agents</option>
                        </select>
                        @error('target')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label fw-bold">Message Type <span class="text-danger">*</span></label>
                        <select class="form-select bg-light border-0 @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="notification" {{ old('type') == 'notification' ? 'selected' : '' }}>Promotional Notification</option>
                            <option value="alert" {{ old('type') == 'alert' ? 'selected' : '' }}>System Alert</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light border-0 @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required maxlength="150" placeholder="e.g. System Maintenance Tomorrow">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="message" class="form-label fw-bold">Message <span class="text-danger">*</span></label>
                    <textarea class="form-control bg-light border-0 @error('message') is-invalid @enderror" id="message" name="message" rows="5" required maxlength="1000" placeholder="Type your message here...">{{ old('message') }}</textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="fas fa-paper-plane me-2"></i> Send Broadcast
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
