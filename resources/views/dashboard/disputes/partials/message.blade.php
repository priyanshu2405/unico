@php
    $isMe = $message->user_id === auth()->id();
@endphp

<div class="d-flex {{ $isMe ? 'justify-content-end' : 'justify-content-start' }} mb-4 message-item" data-id="{{ $message->id }}">
    <div class="message-wrapper" style="max-width: 75%;">
        @if(!$isMe)
        <div class="small text-muted mb-1 ms-1">{{ $message->user->name }}</div>
        @endif
        
        <div class="message-box p-3 {{ $isMe ? 'bg-primary text-white' : 'bg-light text-dark' }}" 
             style="border-radius: 1.2rem; {{ $isMe ? 'border-bottom-right-radius: 0.2rem;' : 'border-bottom-left-radius: 0.2rem;' }}">
            <div class="message-content">
                @if($message->message === '[DISPUTE_FEEDBACK_REQUEST]')
                    @if(!$isMe)
                        <div class="text-center py-2">
                            <h6 class="fw-bold mb-2">Rate our Support</h6>
                            <p class="small mb-3">Your feedback helps us improve our service.</p>
                            <button type="button" class="btn btn-warning btn-sm px-4 text-white" data-bs-toggle="modal" data-bs-target="#feedbackModal" style="border-radius: 0.5rem;">
                                <i class="fas fa-star me-1"></i> Rate Now
                            </button>
                        </div>
                    @else
                        <div class="text-center py-2">
                            <i class="fas fa-star mb-1"></i>
                            <p class="small mb-0">Feedback request sent to customer</p>
                        </div>
                    @endif
                @else
                    {{ $message->message }}
                @endif
            </div>
        </div>
        
        <div class="d-flex align-items-center {{ $isMe ? 'justify-content-end' : 'justify-content-start' }} mt-1 px-1">
            <span class="text-muted small" style="font-size: 0.7rem;">{{ $message->created_at->format('h:i A') }}</span>
            @if($isMe)
            <i class="fas fa-check-double ms-1 {{ $message->is_read ? 'text-primary' : 'text-muted' }}" style="font-size: 0.7rem;"></i>
            @endif
        </div>
    </div>
</div>
