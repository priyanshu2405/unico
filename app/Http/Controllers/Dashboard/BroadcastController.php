<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\PromotionalNotification;
use App\Notifications\SystemAlertNotification;
use Illuminate\Support\Facades\Notification;

class BroadcastController extends Controller
{
    /**
     * Display the broadcast creation form.
     */
    public function showBroadcastForm()
    {
        // Only Admin can access
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        return view('dashboard.broadcast.index');
    }

    /**
     * Send system notifications or alerts to targeted users.
     */
    public function sendBroadcast(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'target' => 'required|string|in:all,manager,support_team,student,reseller_agent,agent',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:1000',
            'type' => 'required|string|in:notification,alert',
        ]);

        $target = $request->input('target');
        $title = $request->input('title');
        $message = $request->input('message');
        $type = $request->input('type');

        // Fetch target users
        if ($target === 'all') {
            $users = User::all();
        } else {
            $users = User::where('account_type', $target)->get();
        }

        if ($users->isEmpty()) {
            return back()->with('error', 'No users found matching the selected target role.');
        }

        // Dispatch notifications
        if ($type === 'notification') {
            Notification::send($users, new PromotionalNotification($title, $message));
            $successMsg = 'Promotional notification broadcasted successfully to ' . $users->count() . ' users.';
        } else {
            Notification::send($users, new SystemAlertNotification($title, $message));
            $successMsg = 'System Alert broadcasted successfully to ' . $users->count() . ' users.';
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Display alerts list page for the logged-in user.
     */
    public function alertsIndex(Request $request)
    {
        $user = auth()->user();
        
        // Mark all alerts as read when visiting the alerts page
        $user->unreadNotifications()
            ->where('type', SystemAlertNotification::class)
            ->update(['read_at' => now()]);
            
        $user->load('unreadNotifications');

        $perPage = $request->get('per_page', 10);
        $alerts = $user->notifications()
            ->where('type', SystemAlertNotification::class)
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.alerts.index', compact('alerts'));
    }

    /**
     * Get count of unread alert notifications.
     */
    public function getUnreadAlertsCount()
    {
        $count = auth()->user()
            ->unreadNotifications()
            ->where('type', SystemAlertNotification::class)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark all alert notifications as read.
     */
    public function markAllAlertsAsRead()
    {
        auth()->user()
            ->unreadNotifications()
            ->where('type', SystemAlertNotification::class)
            ->update(['read_at' => now()]);

        return back()->with('success', 'All system alerts marked as read.');
    }

    /**
     * Handle bulk actions (read/delete) for alerts.
     */
    public function alertsBulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No alerts selected.');
        }

        $alerts = auth()->user()->notifications()
            ->where('type', SystemAlertNotification::class)
            ->whereIn('id', $ids);

        if ($action === 'read') {
            $alerts->update(['read_at' => now()]);
            $message = 'Selected alerts marked as read.';
        } elseif ($action === 'delete') {
            $alerts->delete();
            $message = 'Selected alerts deleted.';
        } else {
            return back()->with('error', 'Invalid action.');
        }

        return back()->with('success', $message);
    }
}
