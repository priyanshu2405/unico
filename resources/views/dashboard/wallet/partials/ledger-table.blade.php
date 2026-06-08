<div class="card border-0 shadow-sm" style="border-radius: 15px;">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <h5 class="fw-bold mb-0">Recent Activity</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">DATE</th>
                        <th>USER</th>
                        <th>TYPE</th>
                        <th>AMOUNT</th>
                        <th class="pe-4">DESCRIPTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledger as $entry)
                    <tr>
                        <td class="ps-4 small text-muted">{{ \Carbon\Carbon::parse($entry->created_at)->format('d M Y, h:i A') }}</td>
                        <td>
                            <div class="fw-bold">{{ $entry->user ? $entry->user->name : 'Unknown' }}</div>
                            <div class="small text-muted">{{ $entry->user ? $entry->user->user_id : 'ID N/A' }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $entry->type == 'credit' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} px-3" style="border-radius: 6px;">
                                {{ strtoupper($entry->type) }}
                            </span>
                        </td>
                        <td class="fw-bold {{ $entry->type == 'credit' ? 'text-success' : 'text-danger' }}">
                            {{ $entry->type == 'credit' ? '+' : '-' }}${{ number_format($entry->amount, 2) }}
                        </td>
                        <td class="small pe-4">{{ $entry->description }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No recent ledger entries found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
