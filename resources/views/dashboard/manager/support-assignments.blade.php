@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0 fw-bold">Support Assignments</h4>
            <p class="text-muted">Manage which support executives handle which topics across countries and regions.</p>
        </div>
    </div>

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

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold">Assign New Topic</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('manager.support.assignments.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Support Executive</label>
                            <select name="support_user_id" class="form-select" required>
                                <option value="" selected disabled>Select an executive...</option>
                                @foreach($supportUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Country</label>
                            <select name="country" class="form-select" required>
                                <option value="" selected disabled>Select a country...</option>
                                @foreach(\App\Helpers\CountryHelper::getAllCountries() as $iso => $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Region (State) <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="text" name="region" class="form-control" placeholder="e.g. California, Delhi">
                            <div class="form-text">Leave blank to assign all regions in the country.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Topics</label>
                            <select name="topics[]" class="form-select" multiple required style="height: 150px;">
                                @foreach($topics as $topic)
                                <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Hold Ctrl/Cmd to select multiple topics.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Assign Topics</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold">Current Assignments</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-uppercase small fw-bold text-muted">
                                <tr>
                                    <th class="px-4">Executive</th>
                                    <th>Location</th>
                                    <th>Topic</th>
                                    <th class="text-end px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $assignment)
                                <tr id="assignment-row-{{ $assignment->id }}">
                                    <td class="px-4">
                                        <div class="fw-bold">{{ $assignment->supportUser->name ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $assignment->supportUser->user_id ?? '' }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $assignment->country }}</div>
                                        <small class="text-muted">{{ $assignment->region ?: 'All Regions' }}</small>
                                    </td>
                                    <td><span class="badge bg-soft-primary text-primary">{{ $assignment->topic->name ?? 'N/A' }}</span></td>
                                    <td class="text-end px-4">
                                        <button class="btn btn-link text-danger p-0 delete-assignment" data-id="{{ $assignment->id }}">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No assignments found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
</style>
<script>
    $(document).ready(function() {
        $('.delete-assignment').on('click', function() {
            if (!confirm('Are you sure you want to remove this assignment?')) return;
            const id = $(this).data('id');
            const row = $(`#assignment-row-${id}`);

            $.ajax({
                url: "{{ route('manager.support.assignments.destroy', '') }}/" + id,
                method: "DELETE",
                data: { _token: "{{ csrf_token() }}" },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        row.fadeOut(300, function() { $(this).remove(); });
                    }
                },
                error: function(xhr) {
                    toastr.error('Failed to remove assignment');
                }
            });
        });
    });
</script>
@endpush
@endsection
