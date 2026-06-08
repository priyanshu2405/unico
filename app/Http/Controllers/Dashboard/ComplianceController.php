<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\UserApproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ComplianceController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('account_type', '!=', 'admin');

        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('profile_verification_status', $request->status);
        }

        if ($request->has('role') && $request->role != 'all' && $request->role != '') {
            $query->where('account_type', $request->role);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('user_id', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $pendingUsers = $query->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.partials.kyc-table', compact('pendingUsers'))->render();
        }

        return view('dashboard.kyc-compliance.index', compact('pendingUsers'));
    }

    public function show(User $user)
    {
        return view('dashboard.kyc-compliance.show', compact('user'));
    }

    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'status' => 'required|in:pending,verified'
        ]);

        $user->update([
            'profile_verification_status' => $request->status,
            'verified_at' => $request->status === 'verified' ? now() : $user->verified_at,
            'verified_by' => $request->status === 'verified' ? auth()->id() : $user->verified_by,
        ]);

        if ($request->status === 'verified') {
            try {
                Mail::to($user->email)->send(new UserApproved($user));
            } catch (\Exception $e) {
                // Handle mail error
            }
        }

        $displayStatus = $request->status === 'verified' ? 'Approved' : 'Pending';
        return response()->json(['success' => 'User status updated to ' . $displayStatus]);
    }

    public function approve(User $user)
    {
        if (auth()->user()->account_type === 'manager' && !auth()->user()->can_approve_user) {
            if (request()->ajax()) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $user->update([
            'profile_verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => auth()->id()
        ]);

        try {
            Mail::to($user->email)->send(new UserApproved($user));
        } catch (\Exception $e) {
            // Log error or handle silently
        }

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => 'User approved successfully and notification email sent.']);
        }

        return redirect()->back()->with('success', 'User approved successfully.');
    }

    public function reject(User $user)
    {
        if (auth()->user()->account_type === 'manager' && !auth()->user()->can_approve_user) {
            if (request()->ajax()) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $user->update([
            'profile_verification_status' => 'rejected',
            'verified_at' => now(),
            'verified_by' => auth()->id()
        ]);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => 'User KYC rejected successfully.']);
        }

        return redirect()->back()->with('success', 'User KYC rejected successfully.');
    }
}
