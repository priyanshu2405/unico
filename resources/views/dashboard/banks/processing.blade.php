@extends('layouts.master')

@section('content')
<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="text-center bg-white p-5 rounded shadow-sm border" style="max-width: 500px; width: 100%;">
        <div class="mb-4">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <h4 class="fw-bold mb-3">Confirming Your Payment...</h4>
        <p class="text-muted">Please wait a few seconds while we verify your transaction with Stripe. Do not close this window or refresh the page.</p>
        
        <div class="mt-4 pt-3 border-top text-start">
            <small class="text-muted text-uppercase fw-bold d-block mb-2" style="font-size: 0.65rem;">Transaction ID</small>
            <code class="small text-primary">{{ $sessionId }}</code>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sessionId = '{{ $sessionId }}';
        let attempts = 0;
        const maxAttempts = 15; // 15 attempts * 2 seconds = 30 seconds max

        function checkStatus() {
            attempts++;
            
            fetch("{{ route('wallet.checkStatus') }}?session_id=" + sessionId, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'paid') {
                    window.location.href = data.redirect + "?success=Amount added successfully!";
                } else if (data.status === 'pending' && attempts < maxAttempts) {
                    setTimeout(checkStatus, 2000); // Check every 2 seconds
                } else {
                    // Redirect to standard success page to let its logic handle it (final check)
                    window.location.href = "{{ route('wallet.success') }}?session_id=" + sessionId;
                }
            })
            .catch(error => {
                console.error('Error checking status:', error);
                // Fallback to success page
                window.location.href = "{{ route('wallet.success') }}?session_id=" + sessionId;
            });
        }

        // Initial check delay
        setTimeout(checkStatus, 2000);
    });
</script>
@endpush
@endsection
