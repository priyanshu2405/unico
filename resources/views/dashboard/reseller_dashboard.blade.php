@extends('layouts.master')
@section('content')
<div class="container-fluid">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0">Reseller Agent Dashboard</h4>
      <div class="system-status">
         <span class="badge bg-success-subtle text-success p-2">
            <i class="fas fa-circle me-1 small"></i> System Operational
         </span>
      </div>
   </div>

   <div class="row g-3 mb-4">
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
            <div class="card-body d-flex flex-column justify-content-between">
               <div>
                  <div class="text-muted small mb-1 text-uppercase fw-semibold">Total Vouchers Purchased</div>
                  <div class="d-flex align-items-center">
                     <h3 class="fw-bold mb-0 text-primary">{{ number_format($totalVouchersPurchased, 0) }}</h3>
                     <span class="ms-auto text-primary small fw-bold"><i class="fas fa-ticket-alt me-1"></i>Units</span>
                  </div>
               </div>
               <div class="pt-2 mt-2 border-top small text-muted d-flex justify-content-between">
                  <span>Direct: <strong>{{ number_format($directVouchersPurchased ?? 0, 0) }}</strong></span>
                  <span>Sub-Agents: <strong>{{ number_format($subAgentVouchersPurchased ?? 0, 0) }}</strong></span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-warning border-4 h-100">
            <div class="card-body d-flex flex-column justify-content-between">
               <div>
                  <div class="text-muted small mb-1 text-uppercase fw-semibold">Total Network Sales Volume</div>
                  <div class="d-flex align-items-center">
                     <h3 class="fw-bold mb-0 text-dark">{{ $userCurrency ?? 'USD' }} {{ number_format($totalPurchaseAmount, 2) }}</h3>
                     <span class="ms-auto text-warning small fw-bold"><i class="fas fa-money-bill-wave me-1"></i>Combined</span>
                  </div>
               </div>
               <div class="pt-2 mt-2 border-top small text-muted d-flex justify-content-between">
                  <span>Own: <strong>{{ $userCurrency ?? 'USD' }} {{ number_format($directPurchaseAmount ?? 0, 2) }}</strong></span>
                  <span>Sub-Agents: <strong>{{ $userCurrency ?? 'USD' }} {{ number_format($subAgentPurchaseAmount ?? 0, 2) }}</strong></span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-info border-4 h-100">
            <div class="card-body d-flex flex-column justify-content-between">
               <div>
                  <div class="text-muted small mb-1 text-uppercase fw-semibold">Total Referral Points</div>
                  <div class="d-flex align-items-center">
                     <h3 class="fw-bold mb-0 text-info">{{ number_format($totalReferralPoints, 0) }}</h3>
                     <span class="ms-auto text-info small fw-bold"><i class="fas fa-users me-1"></i>Points</span>
                  </div>
               </div>
               <div class="pt-2 mt-2 border-top small text-muted">
                  <span>From network purchases</span>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-3">
         <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
            <div class="card-body d-flex flex-column justify-content-between">
               <div>
                  <div class="text-muted small mb-1 text-uppercase fw-semibold">Total Bonus Points</div>
                  <div class="d-flex align-items-center">
                     <h3 class="fw-bold mb-0 text-success">{{ number_format($totalBonusPoints, 0) }}</h3>
                     <span class="ms-auto text-success small fw-bold"><i class="fas fa-gift me-1"></i>Bonus</span>
                  </div>
               </div>
               <div class="pt-2 mt-2 border-top small text-muted">
                  <span>Earned bonus balance</span>
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
   <!-- Connected Sub-Agents & Students Network Section -->
   <div class="row mt-4 mb-4">
      <div class="col-12">
         <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
               <div>
                  <h5 class="mb-0 fw-bold"><i class="fas fa-network-wired text-primary me-2"></i>Connected Sub-Agents & Students</h5>
                  <small class="text-muted">Overview of all agents and students registered under your Reseller account</small>
               </div>
               <div class="d-flex gap-2 align-items-center">
                  <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-semibold">
                     <i class="fas fa-user-tie me-1"></i> {{ $connectedAgentsCount ?? 0 }} Agents
                  </span>
                  <span class="badge bg-info-subtle text-info px-3 py-2 fw-semibold">
                     <i class="fas fa-user-graduate me-1"></i> {{ $connectedStudentsCount ?? 0 }} Students
                  </span>
                  <span class="badge bg-success-subtle text-success px-3 py-2 fw-semibold">
                     <i class="fas fa-users me-1"></i> Total: {{ count($connectedUsers ?? []) }}
                  </span>
               </div>
            </div>
            <div class="card-body p-0">
               <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0" style="white-space: nowrap;">
                     <thead class="table-light">
                        <tr>
                           <th>#</th>
                           <th>User</th>
                           <th>Account Type</th>
                           <th>Country / Location</th>
                           <th>Contact</th>
                           <th class="text-center">Orders Delivered</th>
                           <th class="text-center">Vouchers Bought</th>
                           <th class="text-end">Total Purchases</th>
                           <th>Status</th>
                           <th>Joined Date</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($connectedUsers ?? [] as $index => $u)
                        <tr>
                           <td>{{ $loop->iteration }}</td>
                           <td>
                              <div class="d-flex align-items-center">
                                 <div class="avatar-sm rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                    <i class="fas fa-user text-secondary"></i>
                                 </div>
                                 <div>
                                    <div class="fw-bold text-dark">{{ $u->name ?: ($u->first_name . ' ' . $u->last_name) }}</div>
                                    <small class="text-muted">{{ $u->user_id }}</small>
                                 </div>
                              </div>
                           </td>
                           <td>
                              @if($u->account_type === 'agent')
                                 <span class="badge bg-primary-subtle text-primary px-2 py-1"><i class="fas fa-user-tie me-1"></i>Agent</span>
                              @elseif($u->account_type === 'student')
                                 <span class="badge bg-info-subtle text-info px-2 py-1"><i class="fas fa-user-graduate me-1"></i>Student</span>
                              @else
                                 <span class="badge bg-secondary-subtle text-secondary px-2 py-1">{{ ucfirst($u->account_type) }}</span>
                              @endif
                           </td>
                           <td>
                              <div>{{ $u->country ?? 'N/A' }}</div>
                              @if($u->city)<small class="text-muted">{{ $u->city }}</small>@endif
                           </td>
                           <td>
                              <div>{{ $u->email }}</div>
                              @if($u->phone)<small class="text-muted">{{ $u->phone }}</small>@endif
                           </td>
                           <td class="text-center fw-bold">{{ number_format($u->delivered_orders_count ?? 0) }}</td>
                           <td class="text-center fw-bold text-primary">{{ number_format($u->delivered_vouchers_qty ?? 0) }}</td>
                           <td class="text-end fw-bold text-success">{{ $userCurrency ?? 'USD' }} {{ number_format($u->delivered_orders_sum ?? 0, 2) }}</td>
                           <td>
                              @if($u->is_active)
                                 <span class="badge bg-success-subtle text-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                              @else
                                 <span class="badge bg-danger-subtle text-danger"><i class="fas fa-ban me-1"></i>Inactive</span>
                              @endif
                           </td>
                           <td>{{ $u->created_at ? $u->created_at->format('d M Y') : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                           <td colspan="10" class="text-center py-5">
                              <div class="text-muted">
                                 <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
                                 <h6 class="fw-bold">No Sub-Agents or Students Connected Yet</h6>
                                 <p class="small mb-3">When you register sub-agents or students, they will appear here along with their sales and orders.</p>
                                 <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary px-3 shadow-sm rounded-pill">
                                    <i class="fas fa-user-plus me-1"></i> Register New Agent / Student
                                 </a>
                              </div>
                           </td>
                        </tr>
                        @endforelse
                     </tbody>
                  </table>
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
                                    label += '{{ $userCurrency ?? 'USD' }} ' + new Intl.NumberFormat().format(context.parsed.y);
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
                            text: 'Purchases ({{ $userCurrency ?? 'USD' }})'
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
