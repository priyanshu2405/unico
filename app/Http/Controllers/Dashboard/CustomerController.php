<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SupportQuery;
use App\Models\SupportOption;
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
