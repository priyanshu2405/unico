@extends('layouts.master')
@section('content')
<div class="container-fluid">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0">Main Dashboard</h4>
      <div class="system-status">
         <span class="badge bg-success-subtle text-success p-2">
            <i class="fas fa-circle me-1 small"></i> System Running
         </span>
      </div>
   </div>
   <!-- Stats Cards -->
   <div class="row g-3 mb-4">
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-primary border-4">
            <div class="card-body">
               <div class="text-muted small mb-1">Total Users</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">{{ $stats['total_users'] }}</h3>
                  <span class="ms-auto text-success small fw-bold"><i class="fas fa-users me-1"></i>Active</span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-warning border-4 h-100">
            <div class="card-body">
               <div class="text-muted small mb-1">Total Revenue</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">RS {{ number_format($stats['total_revenue'], 2) }}</h3>
                  <span class="ms-auto text-warning small fw-bold"><i class="fas fa-money-bill-wave me-1"></i>Total</span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-primary border-4">
            <div class="card-body">
               <div class="text-muted small mb-1">Total Voucher Stock</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">{{ number_format($stats['total_vouchers'], 0) }}</h3>
                  <span class="ms-auto text-success small fw-bold"><i class="fas fa-ticket-alt me-1"></i>Units</span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-info border-4 h-100">
            <div class="card-body">
               <div class="text-muted small mb-1">Total Sales (Delivered)</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">{{ $stats['total_sales'] }}</h3>
                  <span class="ms-auto text-info small fw-bold"><i class="fas fa-shopping-cart me-1"></i>Orders</span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
            <div class="card-body">
               <div class="text-muted small mb-1">Reseller Agents</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">{{ $stats['agents'] }}</h3>
                  <span class="ms-auto text-success small fw-bold"><i class="fas fa-user-tie me-1"></i>B2B</span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
            <div class="card-body">
               <div class="text-muted small mb-1">Regular Agents</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">{{ $stats['agents'] }}</h3>
                  <span class="ms-auto text-success small fw-bold"><i class="fas fa-user-tie me-1"></i>B2B</span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-info border-4 h-100">
            <div class="card-body">
               <div class="text-muted small mb-1">Total Students</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">{{ $stats['students'] }}</h3>
                  <span class="ms-auto text-info small fw-bold"><i class="fas fa-user-graduate me-1"></i>B2C</span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
            <div class="card-body">
               <div class="text-muted small mb-1">Total Referral Points</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">{{ number_format($stats['total_referral_points'], 0) }}</h3>
                  <span class="ms-auto text-success small fw-bold"><i class="fas fa-star me-1"></i>Total</span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-info border-4 h-100">
            <div class="card-body">
               <div class="text-muted small mb-1">Total Bonus Points</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">{{ number_format($stats['total_bonus_points'], 2) }}</h3>
                  <span class="ms-auto text-info small fw-bold"><i class="fas fa-gift me-1"></i>Total</span>
               </div>
            </div>
         </div>
      </div>
   </div>
   <!-- Charts & Alerts -->
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
      <div class="col-lg-4">
         <!-- Low Stock Alerts -->
         <!-- <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-exclamation-circle me-1"></i> Low Stock Alerts</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold small">University Voucher (UK)</div>
                                <small class="text-muted">Only 5 remaining</small>
                            </div>
                            <span class="badge bg-danger">Critical</span>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold small">IELTS Booking Code</div>
                                <small class="text-muted">Only 12 remaining</small>
                            </div>
                            <span class="badge bg-warning text-dark">Warning</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white text-center py-2">
                <a href="{{ route('stock.alerts') }}" class="small text-decoration-none text-primary fw-bold">View All Alerts</a>
            </div>
            </div> -->
         <div class="card custom-accordion-card border-0 mb-4">
            <div class="card-body p-0">
               <div class="accordion" id="dashboardAccordion">
                  <!-- Top -->
                  <div class="accordion-item">
                     <h2 class="accordion-header">
                        <button class="accordion-button collapsed"
                           type="button"
                           data-bs-toggle="collapse"
                           data-bs-target="#collapseOne">
                           <i class="fas fa-chart-line me-2 text-primary"></i>
                           <span>Top Brand</span>
                        </button>
                     </h2>
                     <div id="collapseOne"
                        class="accordion-collapse collapse"
                        data-bs-parent="#dashboardAccordion">
                        <div class="accordion-body fw-bold text-primary">
                           {{ $stats['top_brand'] }}
                        </div>
                     </div>
                  </div>
                  <!-- Selling Brand -->
                  <div class="accordion-item">
                     <h2 class="accordion-header">
                        <button class="accordion-button collapsed"
                           type="button"
                           data-bs-toggle="collapse"
                           data-bs-target="#collapseTwo">
                           <i class="fas fa-tags me-2 text-success"></i>
                           <span>Selling Brand</span>
                        </button>
                     </h2>
                     <div id="collapseTwo"
                        class="accordion-collapse collapse"
                        data-bs-parent="#dashboardAccordion">
                        <div class="accordion-body fw-bold text-success">
                           {{ $stats['top_brand'] }}
                        </div>
                     </div>
                  </div>
                  <!-- Selling Varient -->
                  <div class="accordion-item">
                     <h2 class="accordion-header">
                        <button class="accordion-button collapsed"
                           type="button"
                           data-bs-toggle="collapse"
                           data-bs-target="#collapseThree">
                           <i class="fas fa-layer-group me-2 text-warning"></i>
                           <span>Selling Varient</span>
                        </button>
                     </h2>
                     <div id="collapseThree"
                        class="accordion-collapse collapse"
                        data-bs-parent="#dashboardAccordion">
                        <div class="accordion-body fw-bold text-warning">
                           {{ $stats['top_variant'] }}
                        </div>
                     </div>
                  </div>
                  <!-- Buyer -->
                  <div class="accordion-item">
                     <h2 class="accordion-header">
                        <button class="accordion-button collapsed"
                           type="button"
                           data-bs-toggle="collapse"
                           data-bs-target="#collapseFour">
                           <i class="fas fa-user me-2 text-danger"></i>
                           <span>Top Buyer</span>
                        </button>
                     </h2>
                     <div id="collapseFour"
                        class="accordion-collapse collapse"
                        data-bs-parent="#dashboardAccordion">
                        <div class="accordion-body fw-bold text-danger">
                           {{ $stats['top_buyer'] }}
                        </div>
                     </div>
                  </div>
                  <!-- Country -->
                  <div class="accordion-item">
                     <h2 class="accordion-header">
                        <button class="accordion-button collapsed"
                           type="button"
                           data-bs-toggle="collapse"
                           data-bs-target="#collapseFive">
                           <i class="fas fa-globe me-2 text-info"></i>
                           <span>Top Country</span>
                        </button>
                     </h2>
                     <div id="collapseFive"
                        class="accordion-collapse collapse"
                        data-bs-parent="#dashboardAccordion">
                        <div class="accordion-body d-flex align-items-center gap-2">

                           <span class="fw-bold text-info">{{ $stats['top_country'] }}</span>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <!-- System Stats -->
         <!-- <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
               <h6 class="mb-0 fw-bold">Revenue Overview</h6>
            </div>
            <div class="card-body">
               <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted small">Daily Revenue</span>
                  <span class="fw-bold small">€1,240.00</span>
               </div>
               <div class="progress mb-3" style="height: 8px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: 75%"></div>
               </div>
               <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted small">Monthly Revenue</span>
                  <span class="fw-bold small">€28,450.00</span>
               </div>
               <div class="progress" style="height: 8px;">
                  <div class="progress-bar bg-primary" role="progressbar" style="width: 60%"></div>
               </div>
            </div>
         </div> -->
      </div>
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
