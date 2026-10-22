@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fas fa-coins text-warning me-2"></i>Currency Exchange Rates (Base Currency: {{ $baseCurrency }})</h5>
                <small class="text-muted">Manage manual exchange rates to USD. All Admin/Management reports and multi-currency conversions use these fixed rates.</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#rateModal" onclick="openCreateModal()">
                    <i class="fas fa-plus me-1"></i> Add / Update Currency
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="p-3 bg-light border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-shield-alt text-primary fs-5"></i>
                    <span class="small text-secondary">
                        <strong>Deterministic Accounting Protection:</strong> No live third-party FX API is called. Rates remain frozen until you manually update them here, guaranteeing 100% audit accuracy for past orders.
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">#</th>
                            <th class="py-3 text-start">Source Currency</th>
                            <th class="py-3 text-start">Target (Base)</th>
                            <th class="py-3 text-end">Exchange Rate (1 Unit = USD)</th>
                            <th class="py-3 text-end">Inverse Rate (1 USD =)</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Last Updated</th>
                            <th class="py-3 text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rates as $index => $r)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-start">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-6 fw-bold">
                                    {{ $r->from_currency }}
                                </span>
                            </td>
                            <td class="text-start">
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1 fs-6">
                                    {{ $r->to_currency }}
                                </span>
                            </td>
                            <td class="text-end fw-bold font-monospace text-dark">
                                ${{ number_format($r->rate, 6) }}
                            </td>
                            <td class="text-end text-muted font-monospace">
                                @if($r->rate > 0)
                                    {{ number_format(1 / $r->rate, 4) }} {{ $r->from_currency }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($r->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Inactive</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $r->updated_at ? $r->updated_at->format('d M Y, h:i A') : 'Default' }}
                            </td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="openEditModal('{{ $r->from_currency }}', '{{ $r->to_currency }}', '{{ $r->rate }}', {{ $r->is_active ? 1 : 0 }})">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-4 text-muted">No exchange rates defined yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Add / Edit Exchange Rate -->
<div class="modal fade" id="rateModal" tabindex="-1" aria-labelledby="rateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="rateModalLabel"><i class="fas fa-coins me-2"></i>Set Currency Exchange Rate</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="exchangeRateForm" action="{{ route('settings.exchange-rates.update') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Source Currency <span class="text-danger">*</span></label>
                        <input type="text" id="from_currency" name="from_currency" class="form-control text-uppercase font-monospace" placeholder="e.g. INR, PKR, GBP, EUR" required maxlength="10">
                        <small class="text-muted">Enter the 3-letter currency code.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Base Currency</label>
                        <input type="text" id="to_currency" name="to_currency" class="form-control bg-light text-uppercase font-monospace" value="{{ $baseCurrency }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Exchange Rate to 1 USD <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="rate" name="rate" step="0.000001" min="0.000001" class="form-control font-monospace" placeholder="0.012000" required>
                        </div>
                        <small class="text-muted" id="rateHint">Example: 1 INR = $0.012 USD</small>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label fw-semibold" for="is_active">Active for Conversions</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Rate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('rateModalLabel').innerHTML = '<i class="fas fa-coins me-2"></i>Add Currency Rate';
    document.getElementById('from_currency').value = '';
    document.getElementById('from_currency').removeAttribute('readonly');
    document.getElementById('rate').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('rateHint').innerText = 'Enter conversion value to 1 USD';
}

function openEditModal(from, to, rate, isActive) {
    document.getElementById('rateModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Update ' + from + ' Rate';
    document.getElementById('from_currency').value = from;
    document.getElementById('from_currency').setAttribute('readonly', true);
    document.getElementById('to_currency').value = to;
    document.getElementById('rate').value = parseFloat(rate).toFixed(6);
    document.getElementById('is_active').checked = (isActive == 1);
    document.getElementById('rateHint').innerText = '1 ' + from + ' = $' + parseFloat(rate).toFixed(6) + ' USD';
    
    var modal = new bootstrap.Modal(document.getElementById('rateModal'));
    modal.show();
}

document.getElementById('rate').addEventListener('input', function() {
    var cur = document.getElementById('from_currency').value.toUpperCase() || 'UNIT';
    var val = parseFloat(this.value);
    if (!isNaN(val) && val > 0) {
        document.getElementById('rateHint').innerText = '1 ' + cur + ' = $' + val.toFixed(6) + ' USD (or 1 USD = ' + (1/val).toFixed(2) + ' ' + cur + ')';
    }
});
</script>
@endsection
