<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SupportQuery;
use App\Models\SupportOption;
use App\Models\SupportAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function supportindex()
    {
        $topics = SupportOption::where('type', 'topic')->where('is_active', true)->get();
        return view('dashboard.Customer-support.cust-query', compact('topics'));
    }

    public function getIssuesByTopic($topicId)
    {
        $issues = SupportOption::where('type', 'issue')
            ->where('parent_id', $topicId)
            ->where('is_active', true)
            ->get();
        return response()->json($issues);
    }

    public function storeSupportQuery(Request $request)
    {
        /*
        // NEW ADVANCED ROUTING LOGIC (Commented out as requested)
        $request->validate([
            'topic' => 'required|exists:support_options,id',
            'issue' => 'required|exists:support_options,id',
            'description' => 'required|string',
        ]);

        $topic = SupportOption::find($request->topic);
        $issue = SupportOption::find($request->issue);
        $user = Auth::user();

        // 1. Find support assignment based on country, region, and topic
        $assignment = SupportAssignment::where('topic_id', $topic->id)
            ->where('country', $user->country)
            ->where(function ($query) use ($user) {
                $query->where('region', $user->state)
                      ->orWhereNull('region'); // Fallback to country level if no specific region assignment
            })
            ->first();

        SupportQuery::create([
            'user_id' => $user->id,
            'topic' => $topic->name,
            'issue' => $issue->name,
            'description' => $request->description,
            'country' => $user->country,
            'region' => $user->state,
            'assigned_to' => $assignment ? $assignment->support_user_id : null,
        ]);
        */

        // OLD ROUTING LOGIC
        $request->validate([
            'topic' => 'required|exists:support_options,id',
            'issue' => 'required|exists:support_options,id',
            'description' => 'required|string',
        ]);

        $topic = SupportOption::find($request->topic);
        $issue = SupportOption::find($request->issue);

        SupportQuery::create([
            'user_id' => Auth::id(),
            'topic' => $topic->name,
            'issue' => $issue->name,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Your query has been submitted successfully. Our team will get back to you soon.');
    }

    public function CustomerQuery(Request $request)
    {
        /*
        // NEW CUSTOMER QUERY LIST LOGIC (Commented out as requested)
        $perPage = $request->get('per_page', 10);
        $user = Auth::user();

        $query = SupportQuery::with(['user', 'assignedTo', 'escalatedBy'])->latest();

        // If it's a support team member, only show queries assigned to them
        if ($user->account_type === 'support_team') {
            $query->where('assigned_to', $user->id);
        } elseif ($user->account_type === 'manager') {
            // Managers can see everything in their scope, assuming they can see all queries for now
        }

        if ($request->has('topic') && $request->topic != '') {
            $query->where('topic', $request->topic);
        }

        // Count queries *before* applying pagination to get correct stats for current view
        $baseQuery = clone $query;
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
        ];

        $queries = $query->paginate($perPage)->withQueryString();
        $topics = SupportOption::where('type', 'topic')->get();
        $issues = SupportOption::where('type', 'issue')->with('parent')->get();
        
        // Users to escalate to
        $escalationUsers = User::whereIn('account_type', ['manager', 'support_team'])
            ->where('id', '!=', $user->id)
            ->where('is_active', 1)
            ->get();

        return view('dashboard.Customer-support.support', compact('queries', 'topics', 'issues', 'stats', 'escalationUsers'));
        */

        // OLD CUSTOMER QUERY LIST LOGIC
        $perPage = $request->get('per_page', 10);
        $queries = SupportQuery::with('user')->latest()->paginate($perPage)->withQueryString();
        $topics = SupportOption::where('type', 'topic')->get();
        $issues = SupportOption::where('type', 'issue')->with('parent')->get();
        
        $stats = [
            'total' => SupportQuery::count(),
            'pending' => SupportQuery::where('status', 'pending')->count(),
            'closed' => SupportQuery::where('status', 'closed')->count(),
        ];

        return view('dashboard.Customer-support.support', compact('queries', 'topics', 'issues', 'stats'));
    }

    public function escalateQuery(Request $request, $id)
    {
        $request->validate([
            'escalate_to' => 'required|exists:users,id',
            'escalation_level' => 'required|in:peer,senior,manager'
        ]);

        $query = SupportQuery::findOrFail($id);
        $query->escalated_by = Auth::id();
        $query->assigned_to = $request->escalate_to;
        $query->escalation_level = $request->escalation_level;
        $query->save();

        return response()->json([
            'success' => true,
            'message' => 'Query escalated successfully.'
        ]);
    }

    public function resolveQuery($id)
    {
        $query = SupportQuery::findOrFail($id);
        $query->status = 'closed';
        $query->save();

        return response()->json([
            'success' => true,
            'message' => 'Support query resolved and closed successfully.',
            'status' => 'closed',
        ]);
    }

    public function storeSupportOption(Request $request)
    {
        $request->validate([
            'type' => 'required|in:topic,issue',
            'name' => 'required|string|max:255',
            'parent_id' => 'required_if:type,issue|nullable|exists:support_options,id',
        ]);

        SupportOption::create($request->all());

        return response()->json(['success' => true, 'message' => 'Support option added successfully.']);
    }

    public function deleteSupportOption($id)
    {
        SupportOption::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Support option deleted successfully.']);
    }
}
