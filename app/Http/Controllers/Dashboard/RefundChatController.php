<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Models\RefundChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class RefundChatController extends Controller
{
    /**
     * Show the refund chat interface.
     */
    public function show($id)
    {
        $user = auth()->user();
        $refund = RefundRequest::with(['user', 'messages.user'])->findOrFail($id);

        // Authorization checks
        if (!$user->isAdmin() && !$user->isManager() && !$user->isSupport() && $refund->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this chat.');
        }

        // Apply Country Visibility Restrictions for Managers and Support
        if (($user->isManager() || $user->isSupport()) && !$user->isAdmin() && $refund->user_id !== $user->id) {
            if (!$user->can_process_refunds) {
                abort(403, 'Unauthorized action.');
            }
            if (!$user->can_view_all_countries) {
                $permitted = $user->permitted_countries ?? [];
                if (!in_array($refund->user->country ?? '', $permitted)) {
                    abort(403, 'Unauthorized access to this country\'s refunds.');
                }
            }
        }

        // Mark messages as read
        RefundChatMessage::where('refund_request_id', $refund->id)
            ->where('user_id', '!=', $user->id)
            ->update(['is_read' => true]);

        // Update online status
        Cache::put('user-online-' . $user->id, true, now()->addMinutes(2));

        return view('dashboard.refunds.chat', compact('refund'));
    }

    /**
     * Send a new message (text and/or video/image file).
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'nullable|string',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi,webm,ogg,mpeg|max:51200',
        ]);

        $refund = RefundRequest::findOrFail($id);
        $user = auth()->user();

        // Check if both text message and file are missing
        if (!$request->filled('message') && !$request->hasFile('file')) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Message or file is required.'], 422);
            }
            return back()->withErrors('Message or file is required.');
        }

        $filePath = null;
        $fileType = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mimeType = $file->getMimeType();

            if (str_starts_with($mimeType, 'image/')) {
                $fileType = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $fileType = 'video';
            } else {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Invalid file type. Only images and videos are supported.'], 422);
                }
                return back()->withErrors('Invalid file type. Only images and videos are supported.');
            }

            $filePath = $file->store('refund_chats', 'public');
        }

        $message = RefundChatMessage::create([
            'refund_request_id' => $refund->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'file_path' => $filePath,
            'file_type' => $fileType,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message_id' => $message->id,
                'message_html' => view('dashboard.refunds.partials.message', ['message' => $message])->render()
            ]);
        }

        return back();
    }

    /**
     * Fetch new messages for polling.
     */
    public function fetchMessages(Request $request, $id)
    {
        $last_message_id = $request->last_message_id;
        $user = auth()->user();
        $refund = RefundRequest::findOrFail($id);

        // Update current user's online status
        Cache::put('user-online-' . $user->id, true, now()->addMinutes(2));

        $newMessages = RefundChatMessage::with('user')
            ->where('refund_request_id', $id)
            ->where('id', '>', $last_message_id)
            ->get();

        $messages_html = '';
        foreach ($newMessages as $message) {
            $messages_html .= view('dashboard.refunds.partials.message', ['message' => $message])->render();

            // Mark as read if from other user
            if ($message->user_id !== $user->id) {
                $message->update(['is_read' => true]);
            }
        }

        // Determine the other party
        $isCustomer = ($user->id === $refund->user_id);
        $otherUser = null;

        if ($isCustomer) {
            $lastStaffMsg = $refund->messages()
                ->where('user_id', '!=', $user->id)
                ->latest()
                ->first();
            if ($lastStaffMsg) {
                $otherUser = $lastStaffMsg->user;
            }
        } else {
            $otherUser = $refund->user;
        }

        $isTyping = false;
        $isOnline = false;
        if ($otherUser) {
            $isTyping = Cache::has('refund-typing-' . $id . '-' . $otherUser->id);
            $isOnline = Cache::has('user-online-' . $otherUser->id);
        }

        return response()->json([
            'messages_html' => $messages_html,
            'last_message_id' => $newMessages->count() > 0 ? $newMessages->last()->id : $last_message_id,
            'is_typing' => $isTyping,
            'is_online' => $isOnline,
            'other_user_name' => $otherUser ? $otherUser->name : 'Admin Support'
        ]);
    }

    /**
     * Update user typing status in Cache.
     */
    public function updateTyping(Request $request, $id)
    {
        Cache::put('refund-typing-' . $id . '-' . auth()->id(), true, now()->addSeconds(10));
        return response()->json(['success' => true]);
    }
}
