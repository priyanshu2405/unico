<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-4">Event ID</th>
                <th>Source</th>
                <th>Event Type</th>
                <th>Status</th>
                <th>Amount</th>
                <th class="text-end pe-4">Date</th>
            </tr>
        </thead>
        <tbody>
            @php
                $mockWebhooks = [
                    [
                        'id' => 'evt_'.Str::random(10),
                        'source' => 'Stripe',
                        'type' => 'payment_intent.succeeded',
                        'status' => 'processed',
                        'amount' => '$120.00',
                        'date' => now()->subMinutes(15)->format('M d, H:i')
                    ],
                    [
                        'id' => 'evt_'.Str::random(10),
                        'source' => 'PayPal',
                        'type' => 'checkout.order.completed',
                        'status' => 'processed',
                        'amount' => '$45.50',
                        'date' => now()->subHours(2)->format('M d, H:i')
                    ],
                    [
                        'id' => 'evt_'.Str::random(10),
                        'source' => 'Manual API',
                        'type' => 'wallet.credit',
                        'status' => 'completed',
                        'amount' => '$500.00',
                        'date' => now()->subDays(1)->format('M d, H:i')
                    ],
                    [
                        'id' => 'evt_'.Str::random(10),
                        'source' => 'Razorpay',
                        'type' => 'payment.captured',
                        'status' => 'pending',
                        'amount' => '$15.00',
                        'date' => now()->subDays(2)->format('M d, H:i')
                    ]
                ];
            @endphp
            
            @foreach($mockWebhooks as $hook)
            <tr>
                <td class="ps-4">
                    <code class="small text-primary">{{ $hook['id'] }}</code>
                </td>
                <td>
                    <span class="badge bg-light text-dark border px-2">{{ $hook['source'] }}</span>
                </td>
                <td>
                    <span class="small fw-bold">{{ $hook['type'] }}</span>
                </td>
                <td>
                    @if($hook['status'] == 'processed' || $hook['status'] == 'completed')
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.75rem;">
                            <i class="fas fa-check-circle me-1"></i> {{ ucfirst($hook['status']) }}
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.75rem;">
                            <i class="fas fa-clock me-1"></i> {{ ucfirst($hook['status']) }}
                        </span>
                    @endif
                </td>
                <td>
                    <span class="fw-bold">{{ $hook['amount'] }}</span>
                </td>
                <td class="text-end pe-4 text-muted small">
                    {{ $hook['date'] }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="p-4 text-center">
    <button class="btn btn-outline-primary btn-sm px-4" style="border-radius: 10px;">
        View All Logs <i class="fas fa-arrow-right ms-1 small"></i>
    </button>
</div>
