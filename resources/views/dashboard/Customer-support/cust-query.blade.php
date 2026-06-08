@extends('layouts.master')

@section('content')
<div class="container-fluid">


    <div class="card shadow-sm border-0">
        <div class="card-header ">


            <h5 class="mb-0 fw-bold">Support Center</h5>
            <small class="text-muted"> Browse through our frequently asked questions or search a query and describe <br>
                your problem in detail. Our team will get back to you as soon as possible.</small>


        </div>
        <div class="card shadow-sm border-0 p-4" style="border-radius: 15px;">
            <form action="{{ route('customer.support.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <select name="topic" id="topicSelect" class="form-select form-select-lg border-0 shadow-sm p-3 custom-select" style="border-radius: 10px; background-color: #f8f9fa;" required>
                        <option value="" selected disabled>Select Your Query</option>
                        @foreach($topics as $topic)
                        <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                        @endforeach
                    </select>
                    @error('topic')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <select name="issue" id="issueSelect" class="form-select form-select-lg border-0 shadow-sm p-3 custom-select" style="border-radius: 10px; background-color: #f8f9fa;" required disabled>
                        <option value="" selected disabled>Select Subcategory</option>
                    </select>
                    @error('issue')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Description :</label>
                    <textarea name="description" class="form-control border-0 shadow-sm p-4" rows="6" placeholder="Describe your problem in details .." style="border-radius: 10px; background-color: #f8f9fa;" required></textarea>
                    @error('description')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-lg px-5 py-3 text-white fw-bold submit-btn" style="border-radius: 10px; background-color: #d63384;">
                        Submit Query
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>



@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#topicSelect').on('change', function() {
        const topicId = $(this).val();
        const issueSelect = $('#issueSelect');
        
        issueSelect.prop('disabled', true).html('<option value="" selected disabled>Loading...</option>');
        
        if (topicId) {
            $.ajax({
                url: "{{ route('support.options.issues', ':id') }}".replace(':id', topicId),
                method: "GET",
                success: function(data) {
                    issueSelect.html('<option value="" selected disabled>Select Subcategory</option>');
                    data.forEach(function(issue) {
                        issueSelect.append(`<option value="${issue.id}">${issue.name}</option>`);
                    });
                    issueSelect.prop('disabled', false);
                },
                error: function() {
                    toastr.error('Failed to load subcategories');
                    issueSelect.html('<option value="" selected disabled>Select Subcategory</option>');
                }
            });
        }
    });
});
</script>
@endpush