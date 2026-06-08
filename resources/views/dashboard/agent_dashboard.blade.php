@extends('layouts.master')
@section('content')
<div class="container-fluid">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0">Agent Dashboard</h4>
      <div class="system-status">
         <span class="badge bg-success-subtle text-success p-2">
            <i class="fas fa-circle me-1 small"></i> System Operational
         </span>
      </div>
   </div>

   <div class="row g-3 mb-4">
      <div class="col-md-4">
         <div class="card shadow-sm border-0 border-start border-primary border-4">
            <div class="card-body">
               <div class="text-muted small mb-1">Total Voucher Purchased</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">{{ number_format($totalVouchersPurchased, 0) }}</h3>
                  <span class="ms-auto text-success small fw-bold"><i class="fas fa-ticket-alt me-1"></i>Units</span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-4">
         <div class="card shadow-sm border-0 border-start border-warning border-4 h-100">
            <div class="card-body">
               <div class="text-muted small mb-1">Total Purchase Amount</div>
               <div class="d-flex align-items-center">
                  <h3 class="fw-bold mb-0">{{ auth()->user()->currency }} {{ number_format($totalPurchaseAmount, 2) }}</h3>
                  <span class="ms-auto text-warning small fw-bold"><i class="fas fa-money-bill-wave me-1"></i>Total Spent</span>
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
         <div class="card custom-accordion-card border-0 mb-4">
            <div class="card-body p-0">
               <div class="accordion" id="dashboardAccordion">
                  <!-- Top Selling Variant -->
                  <div class="accordion-item">
                     <h2 class="accordion-header">
                        <button class="accordion-button collapsed"
                           type="button"
                           data-bs-toggle="collapse"
                           data-bs-target="#collapseOne">
                           <i class="fas fa-chart-line me-2 text-primary"></i>
                           <span>Top Selling Variant</span>
                        </button>
                     </h2>
                     <div id="collapseOne"
                        class="accordion-collapse collapse"
                        data-bs-parent="#dashboardAccordion">
                        <div class="accordion-body fw-bold text-primary">
                           {{ $topSellingVariant ? $topSellingVariant->voucher_variant : 'N/A' }}
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
                        class="accordion-collapse collapse show"
                        data-bs-parent="#dashboardAccordion">
                        <div class="accordion-body">
                           <ul class="list-group list-group-flush p-0 m-0 border-0">
                              @forelse($topSellingBrands as $brand)
                              <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent border-0">
                                 <span class="fw-bold">{{ $brand->brand_name }}</span>
                                 <span class="badge bg-success-subtle text-success">{{ $brand->total }} Sales</span>
                              </li>
                              @empty
                              <li class="list-group-item bg-transparent border-0 px-0">No selling brands available.</li>
                              @endforelse
                           </ul>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
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
