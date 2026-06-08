@extends('agent.layout.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold">English Language Vouchers</h4>
        </div>
    </div>

    <div class="row">
        @forelse($vouchers as $voucher)
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px; border: 1px solid #eee !important;">
                <div class="card-body p-3">
                    <div class="row align-items-start">
                        <!-- Logo Section (Left) -->
                        <div class="col-5 text-center border-end" style="min-height: 100px; display: flex; align-items: center; justify-content: center;">
                            @if($voucher->logo)
                                <img src="{{ asset('images/logos/' . $voucher->logo) }}" alt="{{ $voucher->name }}" style="max-width: 100%; max-height: 70px; object-fit: contain;">
                            @else
                                <div class="text-muted small">
                                    <i class="fas fa-image fa-3x opacity-25"></i><br>
                                    No Logo
                                </div>
                            @endif
                        </div>
                        
                        <!-- Details Section (Right) -->
                        <div class="col-7 ps-3">
                            <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.85rem; height: 38px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                {{ $voucher->name }}
                            </h6>
                            
                            <div class="mb-2">
                                <span class="fw-bold text-dark" style="font-size: 1rem;">PKR {{ number_format($voucher->price) }}</span>
                                @if($voucher->original_price)
                                    <span class="text-danger text-decoration-line-through ms-1" style="font-size: 0.75rem;">PKR {{ number_format($voucher->original_price) }}</span>
                                @endif
                            </div>

                            @if($voucher->stock > 0)
                                <button class="btn btn-sm text-white fw-bold px-3 py-2 w-100" style="background: #d63384; border-radius: 20px; font-size: 0.8rem;">Order Now</button>
                            @else
                                <button class="btn btn-sm text-dark fw-bold px-3 py-2 w-100" style="background: #ffc107; border-radius: 20px; font-size: 0.8rem;">Out Of Stock</button>
                            @endif
                        </div>
                    </div>

                    <hr class="my-3 opacity-10">

                    <!-- Points Section -->
                    <div class="d-flex justify-content-between align-items-center px-1">
                        <div class="d-flex align-items-center gap-1">
                            <img src="https://cdn-icons-png.flaticon.com/512/2830/2830305.png" width="14" alt="silver">
                            <span style="font-size: 0.7rem; color: #888;">{{ $voucher->quarterly_points }} Quarterly points</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <img src="https://cdn-icons-png.flaticon.com/512/2830/2830312.png" width="14" alt="gold">
                            <span style="font-size: 0.7rem; color: #888;">{{ $voucher->yearly_points }} Yearly points</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <p class="text-muted">No vouchers available at the moment.</p>
        </div>
        @endforelse
    </div>
</div>

<style>
    .card { transition: transform 0.2s; }
    .card:hover { transform: translateY(-5px); }
</style>
@endsection
