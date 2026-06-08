<style>
    .custom-pagination-container {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-family: 'Inter', sans-serif;
        color: #6b7280;
        margin-top: 20px;
    }

    .pagination-left,
    .pagination-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .pagination-select {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 4px 12px;
        font-size: 14px;
        font-weight: 500;
        color: #111827;
        outline: none;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 8px center;
        background-size: 16px;
        padding-right: 32px;
        transition: border-color 0.2s;
    }

    .pagination-select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
    }

    .pagination-text {
        font-size: 14px;
        white-space: nowrap;
    }

    .pagination-text b {
        color: #111827;
        font-weight: 600;
    }

    .pagination-nav-group {
        display: flex;
        align-items: center;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 4px;
        background: #fff;
    }

    .pagination-nav-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        color: #9ca3af;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .pagination-nav-btn:hover:not(:disabled) {
        background: #f3f4f6;
        color: #111827;
    }

    .pagination-nav-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .pagination-nav-divider {
        width: 1px;
        height: 16px;
        background: #e5e7eb;
        margin: 0 4px;
    }

    .page-input-box {
        background: #eef2ff;
        color: #4f46e5;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        margin: 0 8px;
        min-width: 32px;
        text-align: center;
        display: inline-block;
    }

    .pagination-v-divider {
        width: 1px;
        height: 24px;
        background: #e5e7eb;
        margin: 0 4px;
    }
</style>

<script>
    // Define the core function globally if not already defined
    if (typeof window.fetchCustomPage !== 'function') {
        window.fetchCustomPage = function(url) {
            if (!url) return;
            
            // Show loading state
            $('.table-responsive').css('opacity', '0.5');
            
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    // Identify the target container
                    let target = $('#table-container');
                    if (!target.length) target = $('#voucher-list-container');
                    if (!target.length) target = $('.ajax-pagination-container');
                    if (!target.length) target = $('.ajax-pagination');
                    
                    if (target.length) {
                        target.html(response);
                        // Update browser URL
                        window.history.pushState({}, '', url);
                        
                        // Update any page-level export links
                        const csvLink = $('#csv-export-link');
                        if (csvLink.length) {
                            try {
                                let exportUrl;
                                if (csvLink.attr('href')) {
                                    const baseUrl = csvLink.attr('href').split('?')[0];
                                    const params = url.indexOf('?') !== -1 ? url.split('?')[1] : '';
                                    exportUrl = baseUrl + (params ? '?' + params : '');
                                } else {
                                    // Fallback if href is missing for some reason
                                    exportUrl = url.replace('.index', '.export');
                                }
                                csvLink.attr('href', exportUrl);
                            } catch(e) {
                                console.error('Error updating export link:', e);
                            }
                        }
                    } else {
                        // Fallback to full page reload if no AJAX container found
                        window.location.href = url;
                    }
                },
                error: function(xhr) {
                    $('.table-responsive').css('opacity', '1');
                    console.error('AJAX error:', xhr);
                },
                complete: function() {
                    $('.table-responsive').css('opacity', '1');
                }
            });
        };
    }

    // Attach event listener for the per_page dropdown
    if (typeof window.handlePerPageChange !== 'function') {
        window.handlePerPageChange = function(el) {
            const perPage = $(el).val();
            
            // Try to get base URL from existing pagination links to preserve filters
            let baseUrl = window.location.href;
            const $navBtn = $('.pagination-nav-btn[onclick*="fetchCustomPage"]').first();
            if ($navBtn.length) {
                const onclickAttr = $navBtn.attr('onclick');
                const match = onclickAttr.match(/fetchCustomPage\(['"](.*?)['"]\)/);
                if (match && match[1]) {
                    baseUrl = match[1];
                }
            }
            
            try {
                const url = new URL(baseUrl, window.location.origin);
                url.searchParams.set('per_page', perPage);
                url.searchParams.set('page', 1); // Reset to first page
                window.fetchCustomPage(url.toString());
            } catch (e) {
                // Fallback to window.location if URL parsing fails
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('per_page', perPage);
                currentUrl.searchParams.set('page', 1);
                window.fetchCustomPage(currentUrl.toString());
            }
        };
    }

    // Generic form handler to preserve per_page
    if (typeof window.handleAjaxFilter !== 'function') {
        window.handleAjaxFilter = function(form, event) {
            if (event) event.preventDefault();
            const $form = $(form);
            const formData = $form.serializeArray();
            const url = new URL($form.attr('action') || window.location.href, window.location.origin);
            
            // Add all form fields to URL
            formData.forEach(field => {
                if (field.value) {
                    url.searchParams.set(field.name, field.value);
                } else {
                    url.searchParams.delete(field.name);
                }
            });
            
            // Preserve current per_page if it exists in the URL or the select
            const perPage = $('#perPageSelect').val();
            if (perPage) {
                url.searchParams.set('per_page', perPage);
            }
            
            url.searchParams.set('page', 1); // Reset to first page on new filter
            
            window.fetchCustomPage(url.toString());
            
            // Hide offcanvas if it exists
            const offcanvasEl = document.querySelector('.offcanvas.show');
            if (offcanvasEl) {
                const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                if (offcanvas) offcanvas.hide();
            }
        };
    }
</script>

<div class="custom-pagination-container">
    <div class="pagination-left">
        <span class="pagination-text">Items per page:</span>
        <select class="pagination-select pagination-select-per-page" id="perPageSelect" onchange="handlePerPageChange(this)">
            <option value="10" {{ $items->perPage() == 10 ? 'selected' : '' }}>10</option>
            <option value="25" {{ $items->perPage() == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ $items->perPage() == 50 ? 'selected' : '' }}>50</option>
            <option value="100" {{ $items->perPage() == 100 ? 'selected' : '' }}>100</option>
        </select>
        <div class="pagination-v-divider"></div>
        <span class="pagination-text">
            Showing <b>{{ $items->firstItem() ?? 0 }}</b> to <b>{{ $items->lastItem() ?? 0 }}</b> of <b>{{ $items->total() }}</b> results
        </span>
    </div>

    <div class="pagination-right">
        <span class="pagination-text">
            Page <span class="page-input-box">{{ $items->currentPage() }}</span> of {{ $items->lastPage() }}
        </span>
        
        <div class="pagination-nav-group">
            <button class="pagination-nav-btn" {{ $items->onFirstPage() ? 'disabled' : '' }} onclick="fetchCustomPage('{{ $items->url(1) }}')" title="First Page">
                <i class="fas fa-angle-double-left"></i>
            </button>
            <button class="pagination-nav-btn" {{ $items->onFirstPage() ? 'disabled' : '' }} onclick="fetchCustomPage('{{ $items->previousPageUrl() }}')" title="Previous Page">
                <i class="fas fa-angle-left"></i>
            </button>
            
            <div class="pagination-nav-divider"></div>
            
            <button class="pagination-nav-btn" {{ !$items->hasMorePages() ? 'disabled' : '' }} onclick="fetchCustomPage('{{ $items->nextPageUrl() }}')" title="Next Page">
                <i class="fas fa-angle-right"></i>
            </button>
            <button class="pagination-nav-btn" {{ !$items->hasMorePages() ? 'disabled' : '' }} onclick="fetchCustomPage('{{ $items->url($items->lastPage()) }}')" title="Last Page">
                <i class="fas fa-angle-double-right"></i>
            </button>
        </div>
    </div>
</div>