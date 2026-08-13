<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SupportAssignment;
use App\Models\SupportOption;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerSupportController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can_assign_support_topics) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $assignments = SupportAssignment::with(['supportUser', 'topic'])
            ->where('manager_id', Auth::id())
            ->latest()
            ->get();

        $supportUsers = User::where('account_type', 'support_team')->where('is_active', 1)->get();
        $topics = SupportOption::where('type', 'topic')->where('is_active', true)->get();

        return view('dashboard.manager.support-assignments', compact('assignments', 'supportUsers', 'topics'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can_assign_support_topics) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'support_user_id' => 'required|exists:users,id',
            'country' => 'required|string',
            'region' => 'nullable|string',
            'topics' => 'required|array',
            'topics.*' => 'exists:support_options,id',
        ]);

        foreach ($request->topics as $topicId) {
            SupportAssignment::updateOrCreate([
                'support_user_id' => $request->support_user_id,
                'country' => $request->country,
                'region' => $request->region,
                'topic_id' => $topicId,
            ], [
                'manager_id' => Auth::id(),
            ]);
        }

        return redirect()->back()->with('success', 'Support assignments updated successfully.');
    }

    public function destroy($id)
    {
        if (!Auth::user()->can_assign_support_topics) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $assignment = SupportAssignment::where('id', $id)->where('manager_id', Auth::id())->firstOrFail();
        $assignment->delete();

        return response()->json(['success' => true, 'message' => 'Assignment removed successfully.']);
    }
}
