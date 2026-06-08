@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Support Team Dashboard</h4>
        <div class="system-status">
            <span class="badge bg-success-subtle text-success p-2">
                <i class="fas fa-circle me-1 small"></i> System Operational
            </span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">My Performance Rating</div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="fw-bold mb-0">{{ $stats['rating_percentage'] }}%</h3>
                            <small class="text-muted">From {{ $stats['rating_count'] }} ratings</small>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Users</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">{{ $stats['total_users'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Pending Approvals</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">{{ $stats['pending_approvals'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Active Students</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">{{ $stats['students'] }}</h3>
                    </div>
                </div>
            </div>
        </div> -->
    </div>

    <!-- Support Quick Actions -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Voucher & Revenue Trends</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary active">Daily</button>
                        <button class="btn btn-outline-secondary ">Weekly</button>
                        <button class="btn btn-outline-secondary">Monthly</button>
                        <button class="btn btn-outline-secondary">Yearly</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" style="height: 300px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <!-- <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Support Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold mb-2"><i class="fas fa-handshake text-primary me-2"></i> Reseller Agents</h6>
                                <p class="small text-muted mb-3">View and manage B2B reseller accounts.</p>
                                <a href="{{ route('reseller.agent') }}" class="btn btn-sm btn-outline-primary">View Resellers</a>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold mb-2"><i class="fas fa-user text-success me-2"></i> Regular Agents</h6>
                                <p class="small text-muted mb-3">Manage regular agent accounts and details.</p>
                                <a href="{{ route('regular.agent') }}" class="btn btn-sm btn-outline-success">View Agents</a>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold mb-2"><i class="fas fa-user-graduate text-info me-2"></i> Student Management</h6>
                                <p class="small text-muted mb-3">View student profiles and admission details.</p>
                                <a href="{{ route('student.page') }}" class="btn btn-sm btn-outline-info">View Students</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        let currentChart;

        function fetchTrendData(type) {
            $.ajax({
                url: "{{ route('dashboard.trendData') }}",
                type: 'GET',
                data: {
                    type: type
                },
                success: function(data) {
                    const labels = data.map(d => d.label);
                    const revenue = data.map(d => d.revenue);
                    const vouchers = data.map(d => d.vouchers);
                    renderChart(labels, revenue, vouchers, type + ' Trends');
                },
                error: function(err) {
                    console.error('Error fetching trend data:', err);
                }
            });
        }

        function renderChart(labels, revenueData, voucherData, title) {
            if (currentChart) {
                currentChart.destroy();
            }

            currentChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Revenue',
                            data: revenueData,
                            borderColor: '#23AAE2',
                            backgroundColor: 'rgba(35, 170, 226, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Vouchers',
                            data: voucherData,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.dataset.yAxisID === 'y') {
                                        label += 'RS ' + new Intl.NumberFormat().format(context.parsed.y);
                                    } else {
                                        label += new Intl.NumberFormat().format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue (RS)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false,
                            },
                            title: {
                                display: true,
                                text: 'Vouchers'
                            }
                        }
                    }
                }
            });
        }

        // Initial load
        fetchTrendData('Daily');

        // Button Toggles
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.btn-group button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const type = this.textContent.trim();
                fetchTrendData(type);
            });
        });
    });
</script>
@endpush
@endsection