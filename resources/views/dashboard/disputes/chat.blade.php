@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Chat Area -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 1rem; min-height: 600px;">
                <!-- Header -->
                <div class="card-header bg-white border-bottom p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="{{ route('disputes.index') }}" class="btn btn-light btn-sm rounded-circle me-3">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div class="d-flex align-items-center">
                                <img src="{{ asset('images/user.png') }}" class="rounded-circle me-2" width="40" height="40">
                                <div>
                                    <h6 class="fw-bold mb-0" id="other-user-name">Loading...</h6>
                                    <small id="other-user-status" class="text-muted"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Offline</small>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-primary border px-3 py-2">#{{ $dispute->dispute_id }}</span>
                        </div>
                    </div>
                </div>

                <!-- Messages Body -->
                <div class="card-body p-4" id="chat-messages" style="height: 450px; overflow-y: auto; background-color: #fcfcfc;">
                    @foreach($dispute->messages as $message)
                        @include('dashboard.disputes.partials.message', ['message' => $message])
                    @endforeach
                    <div id="typing-indicator" class="d-none mb-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-light p-2 px-3 rounded-pill">
                                <span class="small text-muted italic" id="typing-text">typing...</span>
                                <span class="typing-dots"><span>.</span><span>.</span><span>.</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer / Input -->
                <div class="card-footer bg-white border-top p-3">
                    <form id="chat-form" action="{{ route('disputes.send', $dispute->id) }}" method="POST">
                        @csrf
                        <div class="input-group gap-2">
                            @if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->isSupport())
                            <button type="button" id="request-review-btn" class="btn btn-outline-warning d-flex align-items-center justify-content-center" 
                                    style="width: 50px; border-radius: 0.8rem;" title="Request Feedback">
                                <i class="fas fa-star"></i>
                            </button>
                            @endif
                            <input type="text" id="message-input" name="message" class="form-control border-0 bg-light p-3" 
                                   placeholder="Type your message..." style="border-radius: 0.8rem;" autocomplete="off">
                            <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center" 
                                    style="width: 50px; border-radius: 0.8rem;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                    

                </div>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 1rem;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Dispute Details</h5>
                    
                    <div class="mb-4">
                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Status</label>
                        @if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->isSupport())
                        <form action="{{ route('disputes.status', $dispute->id) }}" method="POST">
                            @csrf
                            <select name="status" class="form-select border-0 bg-light p-3" onchange="this.form.submit()" style="border-radius: 0.8rem;">
                                <option value="open" {{ $dispute->status == 'open' ? 'selected' : '' }}>Open</option>
                                <option value="pending" {{ $dispute->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="resolved" {{ $dispute->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ $dispute->status == 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </form>
                        @else
                        <div class="p-3 bg-light rounded-3 fw-bold text-uppercase">
                            {{ $dispute->status }}
                        </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Subject</label>
                        <p class="fw-bold mb-0">{{ $dispute->subject }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Description</label>
                        <p class="text-muted small mb-0">{{ $dispute->description }}</p>
                    </div>

                    <div class="mb-0">
                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Opened On</label>
                        <p class="mb-0">{{ $dispute->created_at->format('M d, Y \a\t h:i A') }}</p>
                    </div>

                    @if(auth()->user()->isAdmin() || auth()->user()->isManager() || (auth()->user()->isSupport() && auth()->id() === $dispute->assigned_to))
                    <div class="mt-4 pt-4 border-top">
                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Transfer Chat</label>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100 p-2" data-bs-toggle="modal" data-bs-target="#transferModal" style="border-radius: 0.8rem;">
                            <i class="fas fa-exchange-alt me-2"></i>Transfer to Member
                        </button>
                    </div>
                    @endif

                    @if(auth()->id() === $dispute->user_id && ($dispute->status === 'resolved' || $dispute->status === 'closed') && is_null($dispute->rating))
                    <div class="mt-4 pt-4 border-top">
                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Rate our Support</label>
                        <button type="button" class="btn btn-warning btn-sm w-100 p-2 text-white" data-bs-toggle="modal" data-bs-target="#feedbackModal" style="border-radius: 0.8rem;">
                            <i class="fas fa-star me-2"></i>Give Feedback
                        </button>
                    </div>
                    @endif

                    @if(!is_null($dispute->rating))
                    <div class="mt-4 pt-4 border-top">
                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block">User Feedback</label>
                        <div class="d-flex align-items-center mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= $dispute->rating ? 'text-warning' : 'text-muted' }}"></i>
                            @endfor
                        </div>
                        @if($dispute->feedback)
                        <p class="small text-muted italic mb-0">"{{ $dispute->feedback }}"</p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 1rem;">
                <div class="card-body p-4 text-center">
                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                    <h6 class="fw-bold">Need Help?</h6>
                    <p class="text-muted small mb-0">Our support team usually responds within 2-4 hours.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->isSupport())
<!-- Transfer Modal -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Transfer Dispute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('disputes.transfer', $dispute->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Support Member</label>
                        <select name="support_id" class="form-select border-0 bg-light p-3" style="border-radius: 0.8rem;" required>
                            <option value="">Choose member...</option>
                            @foreach(\App\Models\User::where('account_type', 'support_team')->where('id', '!=', auth()->id())->get() as $member)
                                <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Confirm Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if(auth()->id() === $dispute->user_id)
<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Share your Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('disputes.feedback', $dispute->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4 text-center">
                    <p class="text-muted mb-4">How would you rate the support you received for this dispute?</p>
                    
                    <div class="rating-stars mb-4">
                        @for($i = 5; $i >= 1; $i--)
                        <input type="radio" name="rating" value="{{ $i }}" id="star{{ $i }}" required>
                        <label for="star{{ $i }}"><i class="fas fa-star fa-2x"></i></label>
                        @endfor
                    </div>

                    <div class="text-start">
                        <label class="form-label fw-semibold">Comment (Optional)</label>
                        <textarea name="feedback" class="form-control border-0 bg-light p-3" rows="3" placeholder="Tell us more about your experience..." style="border-radius: 0.8rem;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.rating-stars {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
    gap: 10px;
}
.rating-stars input { display: none; }
.rating-stars label {
    cursor: pointer;
    color: #e9ecef;
    transition: color 0.2s;
}
.rating-stars label:hover,
.rating-stars label:hover ~ label,
.rating-stars input:checked ~ label {
    color: #ffc107;
}
</style>
@endif

@push('scripts')
<script>
    $(document).ready(function() {
        const chatMessages = $('#chat-messages');
        const chatForm = $('#chat-form');
        const messageInput = $('#message-input');
        let lastMessageId = $('.message-item').last().data('id') || 0;

        // Scroll to bottom
        chatMessages.scrollTop(chatMessages[0].scrollHeight);

        // Send Message
        chatForm.on('submit', function(e) {
            e.preventDefault();
            const message = messageInput.val().trim();
            if (!message) return;

            sendMessage(message);
        });

        // Request Review
        $('#request-review-btn').on('click', function() {
            if(confirm('Share feedback request with customer?')) {
                sendMessage('[DISPUTE_FEEDBACK_REQUEST]');
            }
        });

        function sendMessage(message) {
            const submitBtn = chatForm.find('button[type="submit"]');
            const reviewBtn = $('#request-review-btn');
            submitBtn.prop('disabled', true);
            reviewBtn.prop('disabled', true);

            $.ajax({
                url: chatForm.attr('action'),
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    message: message
                },
                success: function(response) {
                    if (response.success) {
                        // Only append if it doesn't exist yet (might have been fetched by polling)
                        if ($(`.message-item[data-id="${response.message_id}"]`).length === 0) {
                            chatMessages.append(response.message_html);
                            chatMessages.animate({ scrollTop: chatMessages[0].scrollHeight }, 300);
                        }
                        
                        messageInput.val('');
                        if (response.message_id > lastMessageId) {
                            lastMessageId = response.message_id;
                        }
                    }
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    reviewBtn.prop('disabled', false);
                }
            });
        }

        // Polling for new messages
        let isPolling = false;
        setInterval(function() {
            if (isPolling) return;
            isPolling = true;

            $.ajax({
                url: '{{ route("disputes.fetch", $dispute->id) }}',
                method: 'GET',
                data: { last_message_id: lastMessageId },
                success: function(response) {
                    // Update header
                    $('#other-user-name').text(response.other_user_name);
                    const statusEl = $('#other-user-status');
                    if (response.is_online) {
                        statusEl.removeClass('text-muted').addClass('text-success').html('<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Online');
                    } else {
                        statusEl.removeClass('text-success').addClass('text-muted').html('<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Offline');
                    }

                    // Update typing indicator
                    const typingIndicator = $('#typing-indicator');
                    if (response.is_typing) {
                        $('#typing-text').text(response.other_user_name + ' is typing');
                        typingIndicator.removeClass('d-none');
                        // Move indicator to bottom if new messages arrived
                        chatMessages.append(typingIndicator);
                    } else {
                        typingIndicator.addClass('d-none');
                    }

                    if (response.messages_html) {
                        // The controller returns a string of HTML. 
                        // We wrap it to parse individual message items and check for duplicates
                        const tempDiv = $('<div>').append(response.messages_html);
                        const newItems = tempDiv.find('.message-item');
                        
                        let appended = false;
                        newItems.each(function() {
                            const id = $(this).data('id');
                            if ($(`.message-item[data-id="${id}"]`).length === 0) {
                                // Insert before typing indicator
                                if (typingIndicator.is(':visible')) {
                                    $(this).insertBefore(typingIndicator);
                                } else {
                                    chatMessages.append($(this));
                                }
                                appended = true;
                            }
                        });

                        if (appended) {
                            chatMessages.animate({ scrollTop: chatMessages[0].scrollHeight }, 300);
                        }
                        
                        if (response.last_message_id > lastMessageId) {
                            lastMessageId = response.last_message_id;
                        }
                    }
                },
                complete: function() {
                    isPolling = false;
                }
            });
        }, 3000); // Poll every 3 seconds

        // Handle my typing
        let typingTimer;
        messageInput.on('keyup', function() {
            clearTimeout(typingTimer);
            if (messageInput.val()) {
                // Send typing status to server
                $.post('{{ route("disputes.typing", $dispute->id) }}', { _token: '{{ csrf_token() }}' });
                
                typingTimer = setTimeout(function() {
                    // Stop typing after 3s of inactivity
                }, 3000);
            }
        });
    });
</script>
@endpush
<style>
    .typing-dots span {
        animation: typing 1.4s infinite;
        font-weight: bold;
    }
    .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
    .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
    
    @keyframes typing {
        0%, 100% { opacity: 0.2; }
        50% { opacity: 1; }
    }
</style>
@endsection
