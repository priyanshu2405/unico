@extends('manager.layout.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Voucher Stock Management</h4>
            <p class="text-muted small mb-0">Monitor inventory and replenish supply</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#addVoucherModal">
            <i class="fas fa-plus me-2"></i> Add Vouchers Stocks
        </button>
    </div>

    <!-- Voucher Table -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">SKU / ID</th>
                            <th>Voucher Name</th>
                            <th>Unit Price</th>
                            <th>Current Quantity</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Row 1 -->
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-danger-subtle text-danger p-2 rounded me-3">
                                        <i class="fas fa-cube"></i>
                                    </div>
                                    <span class="fw-bold">EXAM-STD-01</span>
                                </div>
                            </td>
                            <td>Standard Exam</td>
                            <td>$45.00</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold text-danger me-2">15</span>
                                    <div class="progress w-50" style="height: 6px;">
                                        <div class="progress-bar bg-danger" style="width: 15%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
                                    <i class="fas fa-chart-line me-1"></i> Low Stock Alert
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="#" class="text-primary text-decoration-none fw-bold small">Edit / Restock</a>
                            </td>
                        </tr>
                        <!-- Row 2 -->
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-danger-subtle text-danger p-2 rounded me-3">
                                        <i class="fas fa-cube"></i>
                                    </div>
                                    <span class="fw-bold">PREM-PREP-55</span>
                                </div>
                            </td>
                            <td>Premium Prep</td>
                            <td>$99.00</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold text-danger me-2">4</span>
                                    <div class="progress w-50" style="height: 6px;">
                                        <div class="progress-bar bg-danger" style="width: 4%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
                                    <i class="fas fa-chart-line me-1"></i> Low Stock Alert
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="#" class="text-primary text-decoration-none fw-bold small">Edit / Restock</a>
                            </td>
                        </tr>
                        <!-- Row 3 -->
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary-subtle text-primary p-2 rounded me-3">
                                        <i class="fas fa-cube"></i>
                                    </div>
                                    <span class="fw-bold">RETAKE-BUN</span>
                                </div>
                            </td>
                            <td>Retake Bundle</td>
                            <td>$30.00</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold text-dark me-2">120</span>
                                    <div class="progress w-50" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: 80%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                    Healthy
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="#" class="text-primary text-decoration-none fw-bold small">Edit / Restock</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card bg-dark text-white border-0 p-4" style="border-radius: 15px; background: #0f172a !important;">
                <div class="d-flex align-items-center">
                    <div class="bg-warning-subtle text-warning p-3 rounded-circle me-3" style="font-size: 1.5rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Inventory Warning</h5>
                        <p class="text-muted small mb-0">You have 2 items below safety thresholds. Consider restocking now to avoid service interruption.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-primary text-white border-0 p-4" style="border-radius: 15px; background: linear-gradient(135deg, #23AAE2 0%, #1a7fa9 100%) !important;">
                <div class="d-flex align-items-center">
                    <div class="bg-white-subtle p-3 rounded me-3" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-box-open fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Total Assets</h5>
                        <p class="small mb-0 opacity-75">Managing $4,671 worth of digital vouchers in system storage.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Voucher Modal -->
<div class="modal fade" id="addVoucherModal" tabindex="-1" aria-labelledby="addVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="addVoucherModalLabel">Add New Voucher Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">SKU CODE</label>
                            <input type="text" class="form-control bg-light border-0 py-2" placeholder="EX: VOUCH-2024" style="border-radius: 10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">VOUCHER TYPE</label>
                            <select class="form-select bg-light border-0 py-2" style="border-radius: 10px;">
                                <option selected>Exam Voucher</option>
                                <option>Course Voucher</option>
                                <option>Bundle</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">DISPLAY NAME</label>
                            <input type="text" class="form-control bg-light border-0 py-2" placeholder="e.g. Oracle SQL Certification 2024" style="border-radius: 10px;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">PRICE ($)</label>
                            <input type="text" class="form-control bg-light border-0 py-2" placeholder="0.00" style="border-radius: 10px;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">QUANTITY</label>
                            <input type="number" class="form-control bg-light border-0 py-2" placeholder="100" style="border-radius: 10px;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">ALERT AT</label>
                            <input type="number" class="form-control bg-light border-0 py-2" placeholder="10" style="border-radius: 10px;">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pb-4 px-4">
                <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: 12px;">Cancel</button>
                <button type="button" class="btn btn-primary px-4 py-2" style="border-radius: 12px;">
                    <i class="fas fa-save me-2"></i> Add to Inventory
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
