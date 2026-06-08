@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Notifications</h4>
        <div class="d-flex gap-2">
            <form action="{{ route('notifications.markAllRead') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">Mark all as read</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label small text-muted" for="selectAll">Select All</label>
                </div>
                <div id="bulkActions" class="d-none">
                    <form action="{{ route('notifications.bulkAction') }}" method="POST" class="d-inline" id="bulkActionForm">
                        @csrf
                        <input type="hidden" name="action" id="bulkActionType" value="">
                        <div id="selectedIdsContainer"></div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="submitBulkAction('read')">
                                <i class="fas fa-envelope-open me-1"></i> Mark as read
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="submitBulkAction('delete')">
                                <i class="fas fa-trash-alt me-1"></i> Delete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-muted small">
                Showing {{ $notifications->firstItem() }}-{{ $notifications->lastItem() }} of {{ $notifications->total() }}
                <span id="selectedCountDisplay" class="ms-2 d-none fw-bold text-primary">(<span id="count">0</span> Selected)</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse($notifications as $notification)
                <div class="list-group-item p-4 notification-item">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="d-flex gap-3">
                            <div class="form-check mt-2">
                                <input class="form-check-input notification-checkbox" type="checkbox" data-id="{{ $notification->id }}">
                            </div>
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                @php
                                    $type = $notification->data['type'] ?? '';
                                    $icon = 'fa-bell';
                                    $title = 'Notification';
                                    if ($type == 'user_created') {
                                        $icon = 'fa-user-plus';
                                        $title = 'New User Created';
                                    } elseif ($type == 'order_placed') {
                                        $icon = 'fa-shopping-cart';
                                        $title = 'Voucher Order';
                                    }
                                @endphp
                                <i class="fas {{ $icon }}"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark">{{ $title }}</h6>
                                <p class="mb-1 text-muted">{{ $notification->data['message'] ?? 'New notification received' }}</p>
                                <small class="text-secondary">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-5 text-center">
                    <div class="text-muted">
                        <i class="fas fa-bell-slash fa-3x mb-3"></i>
                        <p>No notifications found.</p>
                    </div>
                </div>
                @endforelse
            </div>
            <div class="card-footer bg-white py-4 d-flex justify-content-center border-top">
                @include('dashboard.partials.custom-pagination', ['items' => $notifications])
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.notification-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const bulkActionType = document.getElementById('bulkActionType');
        const bulkActionForm = document.getElementById('bulkActionForm');
        const selectedIdsContainer = document.getElementById('selectedIdsContainer');
        const selectedCountDisplay = document.getElementById('selectedCountDisplay');
        const countSpan = document.getElementById('count');

        // Initialize state from sessionStorage
        let selectedIds = JSON.parse(sessionStorage.getItem('selectedNotifications')) || [];

        function updateUI() {
            // Re-check boxes that are in our state
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectedIds.includes(checkbox.dataset.id);
            });

            // Update bulk actions visibility
            if (selectedIds.length > 0) {
                bulkActions.classList.remove('d-none');
                selectedCountDisplay.classList.remove('d-none');
                countSpan.innerText = selectedIds.length;
            } else {
                bulkActions.classList.add('d-none');
                selectedCountDisplay.classList.add('d-none');
                selectAll.checked = false;
            }

            // Update selectAll checkbox state for CURRENT page
            const allOnPageChecked = Array.from(checkboxes).every(cb => cb.checked);
            selectAll.checked = checkboxes.length > 0 && allOnPageChecked;
        }

        function saveState() {
            sessionStorage.setItem('selectedNotifications', JSON.stringify(selectedIds));
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const id = this.dataset.id;
                if (this.checked) {
                    if (!selectedIds.includes(id)) selectedIds.push(id);
                } else {
                    selectedIds = selectedIds.filter(item => item !== id);
                }
                saveState();
                updateUI();
            });
        });

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                const id = checkbox.dataset.id;
                checkbox.checked = this.checked;
                if (this.checked) {
                    if (!selectedIds.includes(id)) selectedIds.push(id);
                } else {
                    selectedIds = selectedIds.filter(item => item !== id);
                }
            });
            saveState();
            updateUI();
        });

        window.submitBulkAction = function(type) {
            if (selectedIds.length === 0) return;

            if (type === 'delete') {
                if (!confirm(`Are you sure you want to delete ${selectedIds.length} selected notifications?`)) {
                    return;
                }
            }

            // Populate hidden inputs for form submission
            selectedIdsContainer.innerHTML = '';
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                selectedIdsContainer.appendChild(input);
            });

            bulkActionType.value = type;
            // Clear storage before submit so it doesn't linger after action
            sessionStorage.removeItem('selectedNotifications');
            bulkActionForm.submit();
        };

        // Initial UI Update
        updateUI();
    });
</script>
<style>
    .notification-item {
        transition: background-color 0.2s;
    }
    .notification-item:hover {
        background-color: #f8f9fa !important;
    }
    .form-check-input {
        cursor: pointer;
    }

    /* Modern Pagination Styling */
    nav[role="navigation"] {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    /* Hide the default "Showing X to Y" text inside the navigation if it exists */
    nav[role="navigation"] .flex.items-center.justify-between,
    nav[role="navigation"] div:first-child {
        display: none !important;
    }

    /* Force numeric pagination container to show */
    nav[role="navigation"] div:last-child {
        display: block !important;
    }

    .pagination {
        display: flex !important;
        padding-left: 0;
        list-style: none;
        gap: 8px;
        margin: 0;
    }

    .page-item {
        margin: 0;
    }

    .page-link {
        position: relative;
        display: flex !important;
        align-items: center;
        justify-content: center;
        color: #64748b;
        background-color: #fff;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
        border-radius: 10px !important;
        width: 40px !important;
        height: 40px !important;
        padding: 0 !important;
        font-weight: 600;
        font-size: 0.9rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .page-link:hover {
        z-index: 2;
        color: #3b82f6;
        background-color: #f8fafc;
        border-color: #3b82f6;
        text-decoration: none;
        transform: translateY(-2px);
    }

    .page-item.active .page-link {
        z-index: 3;
        color: #fff !important;
        background-color: #3b82f6 !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
    }

    .page-item.disabled .page-link {
        color: #cbd5e1;
        pointer-events: none;
        background-color: #f1f5f9;
        border-color: #e2e8f0;
    }

    /* Icon sizes */
    .page-link svg, 
    .page-link i {
        width: 16px !important;
        height: 16px !important;
    }

    /* Hide text inside page links if any (for "Next" and "Previous") */
    .page-link span:not([aria-hidden="true"]) {
        display: none !important;
    }
</style>
@endpush
@endsection
