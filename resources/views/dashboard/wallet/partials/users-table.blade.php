<div class="card border-0 shadow-sm" style="border-radius: 15px;">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <h5 class="fw-bold mb-0">Active Wallets</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small fw-bold text-muted">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Risk Level</th>
                        <th>Wallet Balance</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center py-2">
                                <div class="avatar-sm me-3 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; font-size: 0.8rem;">
                                    {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $user->name }}</div>
                                    <div class="text-muted small">{{ $user->user_id }} ({{ $user->country_iso }})</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $risk = $user->riskLevel;
                                $badgeClass = 'bg-secondary-subtle text-secondary';
                                if($risk) {
                                    if($risk->risk_level == 'Low') $badgeClass = 'bg-success-subtle text-success';
                                    elseif($risk->risk_level == 'Medium') $badgeClass = 'bg-warning-subtle text-warning';
                                    elseif($risk->risk_level == 'High') $badgeClass = 'bg-danger-subtle text-danger';
                                }
                            @endphp
                            <span class="badge rounded-pill px-3 py-2 {{ $badgeClass }}" style="font-weight: 500;">
                                {{ $risk ? $risk->risk_level : 'Not Set' }}
                            </span>
                        </td>
                        <td class="fw-bold text-dark">
                            ${{ number_format($user->wallet_balance, 2) }}
                        </td>
                        <td>
                            <span class="badge rounded-pill px-3 py-2" style="background-color: #e8f7f0; color: #198754; font-weight: 500;">
                                <i class="fas fa-check-circle me-1 small"></i> Active
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-2">
                                <button class="btn btn-sm btn-light text-primary fw-bold px-3 adjust-balance-btn"
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}"
                                    data-user-balance="{{ number_format($user->wallet_balance, 2) }}"
                                    style="border-radius: 8px; background-color: #f0f4ff; border: none;">
                                    Adjust
                                </button>
                                <!-- <button class="btn btn-sm btn-outline-danger border-0" title="Deactivate Wallet">
                                    <i class="fas fa-power-off"></i>
                                </button> -->
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>