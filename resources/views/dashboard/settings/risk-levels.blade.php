@extends('layouts.master')

@section('content')
<div class="container-fluid">

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Risk Levels
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('settings.risk-levels') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Country Name</label>
                    <input type="text" name="country_name" class="form-control" placeholder="Search country..." value="{{ request('country_name') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Country Code</label>
                    <input type="text" name="country_code" class="form-control" placeholder="PK, IN, US..." value="{{ request('country_code') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Risk Level</label>
                    <select name="risk_level" class="form-select">
                        <option value="all" {{ request('risk_level') == 'all' ? 'selected' : '' }}>All Levels</option>
                        <option value="Low" {{ request('risk_level') == 'Low' ? 'selected' : '' }}>Low</option>
                        <option value="Medium" {{ request('risk_level') == 'Medium' ? 'selected' : '' }}>Medium</option>
                        <option value="High" {{ request('risk_level') == 'High' ? 'selected' : '' }}>High</option>
                    </select>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('settings.risk-levels') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Country Risk Levels</h5>
                <small class="text-muted">Configure risk levels for specific countries.</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#setRiskModal">
                    <i class="fas fa-plus me-1"></i> Set Country Risk
                </button>
                <a href="#" id="csv-export-link" class="btn btn-success btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-csv me-1"></i> CSV
                </a>
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="risk-levels-table">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3 border-0">Country</th>
                            <th class="py-3 border-0">Code</th>
                            <th class="py-3 border-0">Risk Level</th>
                            <th class="px-4 py-3 border-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riskLevels as $risk)
                        @php
                            $badgeClass = match($risk->risk_level) {
                                'High' => 'bg-danger',
                                'Medium' => 'bg-warning text-dark',
                                default => 'bg-success'
                            };
                            $flagCode = strtolower($risk->country_code);
                        @endphp
                        <tr id="risk-row-{{ $risk->id }}">
                            <td class="px-4 py-3 font-weight-bold text-dark">
                                <span class="fi fi-{{ $flagCode }} me-2"></span>
                                {{ $risk->country_name }}
                            </td>
                            <td class="py-3 text-muted">{{ $risk->country_code }}</td>
                            <td class="py-3">
                                <span class="badge {{ $badgeClass }} rounded-pill px-3 py-2">
                                    {{ $risk->risk_level }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <button class="btn btn-sm btn-light rounded-circle delete-risk" data-id="{{ $risk->id }}">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">No country risk levels set yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Set Risk Modal -->
<div class="modal fade" id="setRiskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px;">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Set Country Risk Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="setRiskForm">
                @csrf
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Select Country</label>
                        <select name="country" id="countrySelect" class="form-select select2" required>
                            <option value="">Choose a country...</option>
                            @foreach($allCountries as $code => $name)
                            <option value="{{ $code }}" data-name="{{ $name }}">{{ $name }} ({{ $code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Country Code</label>
                        <input type="text" id="countryCodeDisplay" class="form-control bg-light" readonly>
                        <input type="hidden" name="country_code" id="countryCodeInput">
                        <input type="hidden" name="country_name" id="countryNameInput">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Risk Level</label>
                        <select name="risk_level" class="form-select" required>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@6.6.6/css/flag-icons.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 for country search
    $('#countrySelect').select2({
        dropdownParent: $('#setRiskModal'),
        width: '100%'
    });

    // Auto-fill country code and name
    $('#countrySelect').on('change', function() {
        const code = $(this).val();
        const name = $(this).find(':selected').data('name');
        $('#countryCodeDisplay').val(code);
        $('#countryCodeInput').val(code);
        $('#countryNameInput').val(name);
    });

    // Handle Form Submit
    $('#setRiskForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...');

        $.ajax({
            url: "{{ route('settings.update-risk-level') }}",
            method: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload(); 
                }
            },
            error: function(xhr) {
                toastr.error('Something went wrong. Please try again.');
                btn.prop('disabled', false).html('Save Configuration');
            }
        });
    });

    // Handle Delete
    $(document).on('click', '.delete-risk', function() {
        if(!confirm('Are you sure you want to remove this risk configuration?')) return;
        
        const id = $(this).data('id');
        const row = $(`#risk-row-${id}`);

        $.ajax({
            url: "{{ route('settings.risk-level.delete', ':id') }}".replace(':id', id),
            method: "DELETE",
            data: { _token: "{{ csrf_token() }}" },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() { $(this).remove(); });
                    toastr.success(response.message);
                }
            }
        });
    });

    // Handle Export CSV
    $('#csv-export-link').on('click', function(e) {
        e.preventDefault();
        const countryName = $('input[name="country_name"]').val();
        const countryCode = $('input[name="country_code"]').val();
        const riskLevel = $('select[name="risk_level"]').val();
        
        let url = "{{ route('settings.risk-levels.export') }}?";
        if(countryName) url += `country_name=${countryName}&`;
        if(countryCode) url += `country_code=${countryCode}&`;
        if(riskLevel && riskLevel !== 'all') url += `risk_level=${riskLevel}&`;
        
        window.location.href = url;
    });
});
</script>

<style>
.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 5px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
</style>
@endpush
@endsection
