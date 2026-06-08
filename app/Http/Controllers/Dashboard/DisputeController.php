<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class DisputeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Dispute::with(['user', 'messages', 'assignedStaff'])->latest();

        // If not staff, only show their own disputes
        if (!$user->isAdmin() && !$user->isManager() && !$user->isSupport()) {
            $query->where('user_id', $user->id);
        }

        // If support member, only show unassigned OR disputes assigned to them
        if ($user->isSupport() && !($user->isAdmin() || $user->isManager())) {
            $query->where(function($q) use ($user) {
                $q->whereNull('assigned_to')
                  ->orWhere('assigned_to', $user->id);
            });
        }


        // Apply Country Visibility Restrictions for Managers and Support
        if (($user->isManager() || $user->isSupport()) && !$user->isAdmin()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('country', $user->country);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('dispute_id', 'like', "%$search%")
                  ->orWhere('subject', 'like', "%$search%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%$search%");
                  });
            });
        }

        $perPage = $request->get('per_page', 15);
        $disputes = $query->paginate($perPage)->withQueryString();
        
        return view('dashboard.disputes.index', compact('disputes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        $dispute = Dispute::create([
            'dispute_id' => 'DS-' . strtoupper(Str::random(8)),
            'user_id' => auth()->id(),
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority ?? 'medium',
            'status' => 'open',
        ]);

        // Create initial message
        DisputeMessage::create([
            'dispute_id' => $dispute->id,
            'user_id' => auth()->id(),
            'message' => $request->description,
        ]);

        return back()->with('success', 'Dispute created successfully.');
    }

    public function show($id)
    {
        $user = auth()->user();
        $dispute = Dispute::with(['user', 'messages.user'])->findOrFail($id);

        // Authorization check
        if (!$user->isAdmin() && !$user->isManager() && !$user->isSupport() && $dispute->user_id !== $user->id) {
            abort(403);
        }

        // Apply Country Visibility Restrictions for Managers and Support in show page
        if (($user->isManager() || $user->isSupport()) && !$user->isAdmin() && $dispute->user_id !== $user->id) {
            if ($dispute->user->country !== $user->country) {
                abort(403, 'Unauthorized access to this country\'s disputes.');
            }
        }

        // Mark messages as read
        DisputeMessage::where('dispute_id', $dispute->id)
            ->where('user_id', '!=', $user->id)
            ->update(['is_read' => true]);

        // Update online status
        Cache::put('user-online-' . $user->id, true, now()->addMinutes(2));

        return view('dashboard.disputes.chat', compact('dispute'));
    }

    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $dispute = Dispute::findOrFail($id);
        $user = auth()->user();

        // If support member replies to an unassigned dispute, assign it to them
        if ($user->isSupport() && is_null($dispute->assigned_to)) {
            $dispute->update(['assigned_to' => $user->id]);
        }
        
        $message = DisputeMessage::create([
            'dispute_id' => $dispute->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message_id' => $message->id,
                'message_html' => view('dashboard.disputes.partials.message', ['message' => $message])->render()
            ]);
        }

        return back();
    }

    public function fetchMessages(Request $request, $id)
    {
        $last_message_id = $request->last_message_id;
        $user = auth()->user();
        $dispute = Dispute::findOrFail($id);

        // Update current user's online status
        Cache::put('user-online-' . $user->id, true, now()->addMinutes(2));

        $newMessages = DisputeMessage::with('user')
            ->where('dispute_id', $id)
            ->where('id', '>', $last_message_id)
            ->get();

        $messages_html = '';
        foreach ($newMessages as $message) {
            $messages_html .= view('dashboard.disputes.partials.message', ['message' => $message])->render();
            
            // Mark as read if from other user
            if ($message->user_id !== $user->id) {
                $message->update(['is_read' => true]);
            }
        }

        // Determine "the other party"
        $isCustomer = ($user->id === $dispute->user_id);
        $otherUser = null;
        
        if ($isCustomer) {
            // If I am customer, the other is the assigned staff OR the staff who replied last
            if ($dispute->assigned_to) {
                $otherUser = $dispute->assignedStaff;
            } else {
                $lastStaffMsg = $dispute->messages()
                    ->where('user_id', '!=', $user->id)
                    ->latest()
                    ->first();
                if ($lastStaffMsg) {
                    $otherUser = $lastStaffMsg->user;
                }
            }
        } else {
            // If I am staff, the other is the customer
            $otherUser = $dispute->user;
        }

        $isTyping = false;
        $isOnline = false;
        if ($otherUser) {
            $isTyping = Cache::has('dispute-typing-' . $id . '-' . $otherUser->id);
            $isOnline = Cache::has('user-online-' . $otherUser->id);
        }

        return response()->json([
            'messages_html' => $messages_html,
            'last_message_id' => $newMessages->count() > 0 ? $newMessages->last()->id : $last_message_id,
            'is_typing' => $isTyping,
            'is_online' => $isOnline,
            'other_user_name' => $otherUser ? $otherUser->name : 'Support'
        ]);
    }

    public function updateTyping(Request $request, $id)
    {
        Cache::put('dispute-typing-' . $id . '-' . auth()->id(), true, now()->addSeconds(10));
        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,pending,resolved,closed',
        ]);

        $dispute = Dispute::findOrFail($id);
        $dispute->update(['status' => $request->status]);

        return back()->with('success', 'Dispute status updated.');
    }

    public function transfer(Request $request, $id)
    {
        $request->validate([
            'support_id' => 'required|exists:users,id',
        ]);

        $dispute = Dispute::findOrFail($id);
        
        // Authorization: Only admin, manager, or currently assigned support can transfer
        if (!auth()->user()->isAdmin() && !auth()->user()->isManager() && auth()->id() !== $dispute->assigned_to) {
            abort(403);
        }

        $dispute->update(['assigned_to' => $request->support_id]);

        return back()->with('success', 'Dispute transferred successfully.');
    }

    public function ratings(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isManager() && !$user->isSupport()) {
            abort(403);
        }

        $query = Dispute::with(['user', 'assignedStaff'])
            ->whereNotNull('rating')
            ->latest();

        // If support, they only see their own ratings
        if ($user->isSupport() && !($user->isAdmin() || $user->isManager())) {
            $query->where('assigned_to', $user->id);
        }

        // Apply Country Visibility Restrictions for Managers and Support
        if (($user->isManager() || $user->isSupport()) && !$user->isAdmin()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('country', $user->country);
            });
        }

        $perPage = $request->get('per_page', 15);
        $ratings = $query->paginate($perPage)->withQueryString();

        return view('dashboard.disputes.ratings', compact('ratings'));
    }

    public function submitFeedback(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string',
        ]);

        $dispute = Dispute::findOrFail($id);
        
        // Only the user who opened the dispute can give feedback
        if (auth()->id() !== $dispute->user_id) {
            abort(403);
        }

        $dispute->update([
            'rating' => $request->rating,
            'feedback' => $request->feedback,
        ]);

        // Update staff average rating
        if ($dispute->assigned_to) {
            $staff = User::find($dispute->assigned_to);
            $avgRating = Dispute::where('assigned_to', $staff->id)
                ->whereNotNull('rating')
                ->avg('rating');
            
            $staff->update(['rating' => $avgRating]);
        }

        return back()->with('success', 'Thank you for your feedback!');
    }
}
