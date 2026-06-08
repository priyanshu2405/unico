<style>
    .v-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
    }

    .v-card {
        border-radius: 24px;
        padding: 1.5rem;
        border: none;
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 240px;
        transition: transform 0.2s ease;
        overflow: hidden;
    }

    .v-card:hover {
        transform: translateY(-5px);
    }

    /* Pastel Backgrounds */
    .bg-pastel-orange { background-color: #fdf2e9; }
    .bg-pastel-blue { background-color: #ebf5ff; }
    .bg-pastel-green { background-color: #eafaf1; }
    .bg-pastel-purple { background-color: #f5eef8; }
    .bg-pastel-teal { background-color: #e8f8f5; }
    .bg-pastel-gray { background-color: #f8fafc; }

    .v-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }

    .v-icon-box {
        width: 48px;
        height: 48px;
        background: #fff;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .v-icon-box img {
        width: 32px;
        height: 32px;
        object-fit: contain;
    }

    .v-category-badge {
        font-size: 10px;
        font-weight: 800;
        padding: 4px 12px;
        background: rgba(0, 0, 0, 0.05);
        color: rgba(0, 0, 0, 0.5);
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .v-brand {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .v-main-title {
        font-size: 20px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1.3;
        margin-bottom: 0.5rem;
    }

    .v-desc {
        font-size: 12px;
        color: #64748b;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .v-divider {
        margin-top: auto;
        border-top: 1px dashed rgba(0, 0, 0, 0.1);
        padding-top: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .v-meta {
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-transform: uppercase;
    }

    .v-actions-overlay {
        display: flex;
        gap: 0.4rem;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        color: #64748b;
        transition: all 0.2s;
        text-decoration: none;
    }

    .action-btn:hover {
        background: #1e293b;
        color: #fff;
    }

    .btn-del:hover {
        background: #ef4444;
        color: #fff;
    }

    /* Ticket Cutouts */
    .v-card::before, .v-card::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        background-color: #f3f4f6; /* Match your page background */
        border-radius: 50%;
        bottom: 42px;
    }
    .v-card::before { left: -10px; }
    .v-card::after { right: -10px; }

</style>

<input type="hidden" id="total-vouchers-count" value="{{ $inventory->total() }}">
<div class="v-grid">
    @php
        $bgClasses = ['bg-pastel-orange', 'bg-pastel-blue', 'bg-pastel-green', 'bg-pastel-purple', 'bg-pastel-teal', 'bg-pastel-gray'];
    @endphp
    
    @forelse($inventory as $index => $v)
    <div class="v-card {{ $bgClasses[$index % count($bgClasses)] }}">
        <div class="v-card-top">
            <div class="v-icon-box">
               @if($v->logo)
                   
                   <img src="{{ Str::startsWith($v->logo, 'http') ? $v->logo : asset($v->logo) }}" alt="{{ $v->brand_name }}">
                @else
                    <i class="fas fa-ticket-alt text-muted"></i>
                @endif
            </div>
            <div class="v-category-badge">
                @if(auth()->user()->isAdmin() || (auth()->user()->isManager() && auth()->user()->can_edit_voucher_stock))
                <input type="checkbox" class="voucher-checkbox me-2" value="{{ $v->id }}">
                @endif
                {{ $v->voucher_type }}
            </div>
        </div>

        <div class="v-brand">{{ $v->brand_name }}</div>
        <h3 class="v-main-title">
            {{ $v->currency }} {{ number_format($v->agent_sale_price) }} - {{ $v->voucher_variant ?: 'Special' }}
            @if($v->is_expired)
            <span class="badge bg-danger ms-2" style="font-size: 0.65rem; padding: 0.35rem 0.5rem;">EXPIRED</span>
            @endif
        </h3>
        <p class="v-desc">SKU: {{ $v->sku_id }} | 
            @if(is_array($v->country_region))
                {{ in_array('all', $v->country_region) ? 'GLB' : (count($v->country_region) > 1 ? 'MULTY' : $v->country_region[0]) }}
            @else
                {{ $v->country_region }}
            @endif
             | 
            @if(is_array($v->state))
                {{ in_array('all', $v->state) ? 'All States' : (count($v->state) > 1 ? 'MULTY' : ($v->state[0] ?? 'N/A')) }}
            @else
                {{ $v->state ?: 'N/A' }}
            @endif
             | Qty: 
             @php
                $allCodes = is_array($v->upload_vouchers) ? $v->upload_vouchers : [];
                $deliveredCodes = is_array($v->delivered_vouchers) ? $v->delivered_vouchers : [];
                $availableCount = count(array_diff($allCodes, $deliveredCodes));
             @endphp
             {{ number_format($availableCount) }}</p>

        <div class="v-divider">
            <div class="v-meta">
                <i class="far fa-calendar-alt"></i>
                CREATED: {{ $v->created_at->format('M Y') }}
            </div>
            
            @if(auth()->user()->isAdmin() || (auth()->user()->isManager() && auth()->user()->can_edit_voucher_stock))
            <div class="v-actions-overlay">
                <a href="{{ route('inventory.edit', $v->id) }}" class="action-btn" title="Edit">
                    <i class="fas fa-edit fa-xs"></i>
                </a>
                <form action="{{ route('inventory.duplicate', $v->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="action-btn" title="Duplicate">
                        <i class="fas fa-copy fa-xs"></i>
                    </button>
                </form>
                <button type="button" class="action-btn btn-del delete-voucher-btn" data-id="{{ $v->id }}" title="Delete">
                    <i class="fas fa-trash-alt fa-xs"></i>
                </button>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <div class="mb-3">
            <i class="fas fa-box-open fa-3x text-muted"></i>
        </div>
        <h5 class="text-muted">No vouchers found in inventory</h5>
    </div>
    @endforelse
</div>

@include('dashboard.partials.custom-pagination', ['items' => $inventory])