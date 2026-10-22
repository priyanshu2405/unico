@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="fw-bold text-dark">System Control Center</h2>
            <p class="text-muted">Manage global settings and emergency system status.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm mb-4">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    </div>
    @endif

    <div class="row">
        <!-- System Settings -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Voucher Sales Controls</h5>
                    <form action="{{ route('system.control.update') }}" method="POST">
                        @csrf
                        
                        <!-- 1. Stop All Sales -->
                        <div class="form-check form-switch mb-4 p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="form-check-label fw-bold text-danger h6 mb-0" for="stop_all_sales">
                                        <i class="fas fa-hand-paper me-2"></i> STOP ALL SALES GLOBALLY
                                    </label>
                                    <p class="small text-muted mb-0">Immediately hide all vouchers from all users (Students, Agents, Resellers).</p>
                                </div>
                                <input class="form-check-input" type="checkbox" name="settings[stop_all_sales]" id="stop_all_sales" value="on" {{ ($settings['stop_all_sales'] ?? 'off') == 'on' ? 'checked' : '' }}>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- 2. Stop Brand Sale -->
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="fas fa-tags me-2 text-primary"></i> Stop Brand-wise Sale</label>
                            <p class="small text-muted">Select brands to stop their sales. Vouchers of these brands will be hidden.</p>
                            @php
                                $stoppedBrands = json_decode($settings['stopped_brands'] ?? '[]', true) ?: [];
                                $allBrands = $vouchers->pluck('brand_name')->unique()->sort();
                            @endphp
                            <select name="settings[stopped_brands][]" class="form-select select2" multiple data-placeholder="Choose brands to stop...">
                                @foreach($allBrands as $brand)
                                    <option value="{{ $brand }}" {{ in_array($brand, $stoppedBrands) ? 'selected' : '' }}>{{ $brand }}</option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="my-4">

                        <!-- 3. Stop Country-wise Sale -->
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="fas fa-globe me-2 text-success"></i> Stop Country-wise Sale</label>
                            <p class="small text-muted">Select countries where you want to disable voucher visibility.</p>
                            @php
                                $stoppedCountries = json_decode($settings['stopped_countries'] ?? '[]', true) ?: [];
                            @endphp
                            <select name="settings[stopped_countries][]" class="form-select select2" multiple data-placeholder="Choose countries to stop...">
                                @foreach($countries as $country)
                                    <option value="{{ $country->country }}" {{ in_array($country->country, $stoppedCountries) ? 'selected' : '' }}>{{ $country->country }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-5 border-top pt-4">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                                <i class="fas fa-save me-2"></i> Save Sales Controls
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Emergency Actions -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-top border-danger border-4 rounded-4 mb-4">
                <div class="card-body py-4">
                    <h5 class="fw-bold text-danger mb-3">Emergency Actions</h5>
                    <p class="small text-muted mb-4">These actions have immediate global impact. Use with caution.</p>

                    <form action="{{ route('system.toggle') }}" method="POST" class="d-grid gap-3">
                        @csrf
                        <input type="hidden" name="reason" value="Emergency System Halt">
                        @php
                        $isFrozen = ($settings['emergency_freeze'] ?? 'off') == 'on';
                        $canToggle = auth()->user()->account_type !== 'manager' || auth()->user()->can_stop_system_sales;
                        @endphp
                        <button type="submit" class="btn {{ $isFrozen ? 'btn-danger' : 'btn-outline-danger' }} fw-bold py-3" {{ !$canToggle ? 'disabled' : '' }}>
                            <i class="fas fa-power-off me-2"></i> {{ $isFrozen ? 'SYSTEM FROZEN - UNFREEZE' : 'HOLD ALL TRANSACTIONS' }}
                        </button>
                    </form>

                    <hr class="my-4">

                    <div class="d-grid gap-3">
                        <form id="clear-cache-form" action="{{ route('system.control.clear-cache') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                        <button type="button" class="btn btn-light border btn-sm text-start py-3 px-3" onclick="document.getElementById('clear-cache-form').submit();">
                            <i class="fas fa-broom me-2 text-warning"></i> Clear System Cache
                        </button>

                        <form id="backup-db-form" action="{{ route('system.control.backup-db') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                        <button type="button" class="btn btn-light border btn-sm text-start py-3 px-3" onclick="document.getElementById('backup-db-form').submit();">
                            <i class="fas fa-database me-2 text-info"></i> Backup Database
                        </button>

                        @if(auth()->user()->account_type === 'admin')
                        <hr class="my-3">
                        <p class="small text-danger fw-bold mb-2"><i class="fas fa-skull-crossbones me-1"></i> Danger Zone</p>
                        <button type="button" class="btn btn-outline-danger btn-sm text-start py-3 px-3 w-100" data-bs-toggle="modal" data-bs-target="#purgeDataModal">
                            <i class="fas fa-trash-alt me-2"></i> Purge All Transaction Data
                        </button>
                        <p class="small text-muted mt-1">Deletes all data except user accounts.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Audit Logs Summary -->
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Recent Control Activity</h6>
                    <div class="timeline small">
                        @forelse($logs as $log)
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold text-dark">{{ $log->action }}</span>
                                <span class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="text-muted">{{ $log->description }}</div>
                        </div>
                        @empty
                        <p class="text-muted italic">No recent logs</p>
                        @endforelse
                    </div>
                    <a href="{{ route('audit.index') }}" class="btn btn-link btn-sm p-0 mt-2">View Full Audit Log</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if(auth()->user()->account_type === 'admin')
<!-- Purge Data Confirmation Modal -->
<div class="modal fade" id="purgeDataModal" tabindex="-1" aria-labelledby="purgeDataModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger border-2">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="purgeDataModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i> Confirm Data Purge
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-3">
                    <strong><i class="fas fa-skull-crossbones me-1"></i> WARNING:</strong> This action is <strong>IRREVERSIBLE</strong>. It will permanently delete ALL transaction data including:
                </div>
                <ul class="small mb-3">
                    <li>All orders (sales & purchases)</li>
                    <li>Inventory/stock data</li>
                    <li>Wallet ledger & balances (reset to 0)</li>
                    <li>Refund requests & chats</li>
                    <li>Disputes & support tickets</li>
                    <li>Agent & student details</li>
                    <li>Pricing rules, exchange rates</li>
                    <li>Audit logs, notifications</li>
                </ul>
                <p class="small text-muted mb-3"><strong>User accounts will be preserved</strong> but wallet balances will be reset to ₹0.</p>

                <hr>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Type <code>PURGE</code> to confirm:</label>
                    <input type="text" class="form-control" id="purge-confirm-text" placeholder="Type PURGE here" autocomplete="off">
                </div>

                <form id="purge-data-form" action="{{ route('system.control.purge-data') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Enter your admin password:</label>
                        <input type="password" class="form-control" name="admin_password" id="purge-admin-password" placeholder="Your login password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger fw-bold py-2" id="purge-submit-btn" disabled>
                            <i class="fas fa-trash-alt me-2"></i> Permanently Purge All Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<!-- Select2 CSS/JS for beautiful multi-select -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            theme: 'bootstrap-5',
            closeOnSelect: false
        });

        // Purge confirmation logic
        var confirmText = document.getElementById('purge-confirm-text');
        var adminPassword = document.getElementById('purge-admin-password');
        var submitBtn = document.getElementById('purge-submit-btn');

        function checkPurgeReady() {
            if (!confirmText || !adminPassword || !submitBtn) return;
            var textOk = confirmText.value.trim() === 'PURGE';
            var passOk = adminPassword.value.length > 0;
            submitBtn.disabled = !(textOk && passOk);
        }

        if (confirmText) confirmText.addEventListener('input', checkPurgeReady);
        if (adminPassword) adminPassword.addEventListener('input', checkPurgeReady);

        // Reset modal on close
        var purgeModal = document.getElementById('purgeDataModal');
        if (purgeModal) {
            purgeModal.addEventListener('hidden.bs.modal', function() {
                if (confirmText) confirmText.value = '';
                if (adminPassword) adminPassword.value = '';
                if (submitBtn) submitBtn.disabled = true;
            });
        }
    });
</script>
<style>
    .form-switch .form-check-input {
        width: 3.5em;
        height: 1.75em;
        cursor: pointer;
    }
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 50px;
        padding: 5px;
    }
</style>
@endpush