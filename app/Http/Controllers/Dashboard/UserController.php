<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCreated;
use App\Notifications\UserCreatedNotification;
use Illuminate\Support\Facades\Notification;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_view_users)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }

        $query = User::with('riskLevel');

        if (!auth()->user()->isAdmin()) {
            $query->whereNotIn('account_type', ['admin', 'manager', 'support_team']);
        } else {
            // For Admin and others, exclude admins to match "admin ko chhod kar"
            $query->where('account_type', '!=', 'admin');
        }

        // Apply Country Visibility Restrictions
        $authUser = auth()->user();
        if (($authUser->isManager() || $authUser->isSupport()) && !$authUser->isAdmin()) {
            if (!$authUser->can_view_all_countries) {
                $permitted = $authUser->permitted_countries ?? [];
                $query->whereIn('country', $permitted);
            }
        }

        // Apply Reseller Agent Restriction (Only see users they added)
        if ($authUser->isResellerAgent()) {
            $query->where('sub_agent_id', $authUser->id);
        }

        if ($request->has('role') && $request->role != 'all' && $request->role != '') {
            $query->where('account_type', $request->role);
        }

        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('is_active', $request->status);
        }

        if ($request->has('verification_status') && $request->verification_status != 'all' && $request->verification_status != '') {
            if ($request->verification_status === 'pending') {
                $query->where(function ($q) {
                    $q->where('profile_verification_status', 'pending')
                        ->orWhereNull('profile_verification_status')
                        ->orWhere('profile_verification_status', '');
                });
            } else {
                $query->where('profile_verification_status', $request->verification_status);
            }
        }

        if ($request->has('flags') && $request->flags != 'all' && $request->flags != '') {
            if ($request->flags === 'email_verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->flags === 'doc_verified') {
                $query->where('profile_verification_status', 'verified');
            }
        }

        if ($request->has('country') && $request->country != 'all' && $request->country != '') {
            $query->where('country', $request->country);
        }
        if ($request->has('state') && $request->state != 'all' && $request->state != '') {
            $query->where('state', $request->state);
        }

        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ["%{$search}%"])
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);
        $users = $query->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.partials.users-table', compact('users'))->render();
        }

        return view('dashboard.users.index', compact('users'));
    }

    public function create()
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_create_user || auth()->user()->account_type === 'reseller_agent')) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }
        return view('dashboard.users.create');
    }

    public function store(Request $request)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_create_user || auth()->user()->account_type === 'reseller_agent')) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }

        $accountTypeRule = 'required|in:manager,reseller_agent,support_team,student,agent';
        if (auth()->user()->account_type === 'reseller_agent') {
            $accountTypeRule = 'required|in:agent,student';
        }

        $countryRule = 'required|string|max:255';
        $authUser = auth()->user();
        if ($authUser->isResellerAgent() && !$authUser->can_view_all_countries) {
            $permitted = $authUser->permitted_countries ?? [];
            $countryRule = 'required|string|in:' . implode(',', $permitted);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            // 'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'account_type' => $accountTypeRule,
            'phone' => 'nullable|string|max:20',
            'country' => $countryRule,
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
        ]);

        $countryIso = null;
        if ($request->filled('country')) {
            $countries = \App\Helpers\CountryHelper::getAllCountries();
            $countryIso = array_search($request->country, $countries);
            if ($countryIso === false) {
                $countryIso = null;
            }
        }

        $userData = [
            'user_id' => User::generateNextUserId($request->account_type, $countryIso ?? 'IN'),
            'first_name' => $request->first_name,
            'name' => $request->first_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'account_type' => $request->account_type,
            'phone' => $request->phone,
            'country' => $request->country,
            'country_iso' => $countryIso,
            'state' => $request->state,
            'city' => $request->city,
            'profile_verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ];

        if (auth()->user()->account_type === 'reseller_agent') {
            $userData['sub_agent_id'] = auth()->id();
        }

        $user = User::create($userData);

        // Send Welcome Email
      try {
            if (auth()->user()->account_type === 'reseller_agent') {
                // Custom message for agents created by resellers including credentials
                $resellerName = auth()->user()->name;
                $email = $user->email;
                $password = $request->password;
                $messageContent = "Your account has been created by Reseller Agent $resellerName.\n\n" .
                                 "Login Details:\n" .
                                 "Email: $email\n" .
                                 "Password: $password\n\n" .
                                 "Please login to complete your profile.";
                
                Mail::raw($messageContent, function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Account Created - Unicou Voucher Login Details');
                });
            } else {
                Mail::to($user->email)->send(new UserCreated($user, $request->password));
            }
        } catch (\Exception $e) {
            \Log::error("Failed to send welcome email: " . $e->getMessage());
        }

        // Notify Admins and Managers
        $adminsAndManagers = User::whereIn('account_type', ['admin', 'manager'])->get();
        Notification::send($adminsAndManagers, new UserCreatedNotification($user, auth()->user()));

        if (auth()->user()->account_type === 'reseller_agent') {
            return redirect()->route('regular.agent')->with('success', 'Agent created successfully and notification email sent.');
        }

        return redirect()->route('users.management')->with('success', 'User created successfully and welcome email sent.');
    }

    public function show(User $user)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_view_users || (auth()->user()->isResellerAgent() && $user->sub_agent_id === auth()->id()))) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }
        return view('dashboard.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_edit_user || (auth()->user()->isResellerAgent() && $user->sub_agent_id === auth()->id()))) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }
        return view('dashboard.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_edit_user || (auth()->user()->isResellerAgent() && $user->sub_agent_id === auth()->id()))) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }

        // If it's a file-only upload from the KYC tab, we relax the validation
        $isFileUpload = $request->hasFile('profile_photo') ||
            $request->hasFile('aadhar_card') ||
            $request->hasFile('pan_card') ||
            $request->hasFile('registration_doc') ||
            $request->hasFile('id_doc') ||
            $request->hasFile('id_doc_final');

        $rules = [
            'first_name' => $isFileUpload ? 'nullable|string|max:255' : 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => ($isFileUpload ? 'nullable' : 'required') . '|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'id_type' => 'nullable|string|max:255',
            'id_number' => 'nullable|string|max:255',
            'primary_contact' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:255',
            'profile_verification_status' => 'nullable|in:pending,verified,suspended,rejected',
            // Business fields
            'business_name' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            // Student fields
            'exam_purpose' => 'nullable|string|max:255',
            'highest_education' => 'nullable|string|max:255',
            'passing_year' => 'nullable|integer',
            'preferred_countries' => 'nullable|array',
            'bank_name' => 'nullable|string|max:255',
            'bank_country' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            // Files
            'profile_photo' => 'nullable|image|max:2048',
            'aadhar_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'pan_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'registration_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_doc_final' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];

        $request->validate($rules);

        $data = $request->except(['_token', '_method', 'profile_photo', 'aadhar_card', 'pan_card', 'registration_doc', 'id_doc', 'id_doc_final', 'preferred_countries']);

        if ($request->has('first_name') && !empty($request->first_name)) {
            $data['name'] = $request->first_name . ($request->last_name ? ' ' . $request->last_name : '');
        }

        if ($request->has('preferred_countries')) {
            $data['preferred_countries'] = $request->preferred_countries;
        }

        if ($request->has('country')) {
            if (empty($request->country)) {
                $data['country_iso'] = null;
            } else {
                $countries = \App\Helpers\CountryHelper::getAllCountries();
                $countryIso = array_search($request->country, $countries);
                $data['country_iso'] = ($countryIso !== false) ? $countryIso : null;
            }
        }

        // Sync primary_contact and phone
        if ($request->has('phone') && !empty($request->phone)) {
            $data['primary_contact'] = $request->phone;
        } elseif ($request->has('primary_contact') && !empty($request->primary_contact)) {
            $data['phone'] = $request->primary_contact;
        }

        // Handle File Uploads
        $fileFields = ['profile_photo', 'aadhar_card', 'pan_card', 'registration_doc', 'id_doc', 'id_doc_final'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('users/' . $field, 'public');
            }
        }

        // Filter out null values to prevent overwriting with null if fields are missing from request
        $data = array_filter($data, function ($value) {
            return !is_null($value);
        });

        $user->update($data);

        if ($request->ajax()) {
            return response()->json(['success' => 'User details updated successfully.']);
        }

        return back()->with('success', 'User details updated successfully.');
    }

    public function destroy(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            if (request()->ajax()) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            return redirect()->route('users.management')->with('error', 'Unauthorized action.');
        }

        $user->delete();

        if (request()->ajax()) {
            return response()->json(['success' => 'User deleted successfully']);
        }

        return redirect()->route('users.management')->with('success', 'User deleted successfully');
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if (!(auth()->user()->isAdmin() || auth()->user()->can_freeze_user)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        if (!auth()->user()->isAdmin() && in_array($user->account_type, ['admin', 'manager', 'support_team'])) {
            return response()->json(['status' => 'error', 'message' => 'You cannot freeze administrative accounts.'], 403);
        }

        if ($user->id === auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot freeze your own account'
            ], 403);
        }

        $user->is_active = $user->is_active ? 0 : 1;
        $user->save();

        $message = $user->is_active
            ? 'User account unfrozen successfully'
            : 'User account frozen successfully';

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'status'  => $user->is_active ? 'active' : 'frozen',
                'message' => $message
            ]);
        }

        return back()->with('success', $message);
    }

    public function suspend(User $user)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_freeze_user)) {
            return back()->with('error', 'Unauthorized action.');
        }

        if (!auth()->user()->isAdmin() && in_array($user->account_type, ['admin', 'manager', 'support_team'])) {
            return back()->with('error', 'You cannot suspend administrative accounts.');
        }

        $newStatus = $user->profile_verification_status === 'suspended' ? 'verified' : 'suspended';
        $user->update(['profile_verification_status' => $newStatus]);

        $message = $newStatus === 'suspended' ? 'User suspended successfully.' : 'User unsuspended successfully.';

        if (request()->ajax()) {
            return response()->json(['success' => $message]);
        }

        return back()->with('success', $message);
    }

    public function updatePassword(Request $request, User $user)
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->can_reset_password) {
            return back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updatePermissions(Request $request, User $user)
    {
        // Only admin can update permissions
        if (auth()->user()->account_type !== 'admin') {
            return back()->with('error', 'Unauthorized action.');
        }

        $user->update([
            'can_freeze_user' => $request->has('can_freeze_user'),
            'can_reset_password' => $request->has('can_reset_password'),
            'can_create_user' => $request->has('can_create_user'),
            'can_edit_user' => $request->has('can_edit_user'),
            'can_approve_user' => $request->has('can_approve_user'),
            'can_view_users' => $request->has('can_view_users'),

            'can_impersonate_user' => $request->has('can_impersonate_user'),
            'can_stop_system_sales' => $request->has('can_stop_system_sales'),
            'can_stop_country_sales' => $request->has('can_stop_country_sales'),
            'can_stop_voucher_sales' => $request->has('can_stop_voucher_sales'),
            'can_view_user_email_name' => $request->has('can_view_user_email_name'),
            'has_job_permission' => $request->has('has_job_permission'),
            'can_view_voucher_code' => $request->has('can_view_voucher_code'),
            'can_view_orders' => $request->has('can_view_orders'),
            'can_view_sales_report' => $request->has('can_view_sales_report'),
            'can_view_disputes' => $request->has('can_view_disputes'),
            'can_view_voucher_stock' => $request->has('can_view_voucher_stock'),
            'can_edit_voucher_stock' => $request->has('can_edit_voucher_stock'),
            'can_view_reports_page' => $request->has('can_view_reports_page'),
            'can_process_refunds' => $request->has('can_process_refunds'),
             'can_view_all_countries' => $request->has('can_view_all_countries'),
            'can_assign_support_topics' => $request->has('can_assign_support_topics'),
            'permitted_countries' => $request->input('permitted_countries', []),
        ]);

        return back()->with('success', 'User permissions updated successfully.');
    }

    public function updateCategory(Request $request, User $user)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_edit_user)) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'category' => 'required|string|in:silver,gold,diamond',
        ]);

        $user->update([
            'category' => $request->category,
        ]);

        return response()->json(['success' => 'Category updated successfully.']);
    }

    public function updateLimit(Request $request, User $user)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_edit_user)) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'voucher_limit' => 'required|integer|min:0',
        ]);

        $user->update([
            'voucher_limit' => $request->voucher_limit,
        ]);

        return response()->json(['success' => 'Voucher limit updated successfully.']);
    }

    public function downloadPDF(Request $request)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_view_users)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }

        $query = User::where('id', '!=', auth()->id());

        if (!auth()->user()->isAdmin()) {
            $query->whereNotIn('account_type', ['admin', 'manager', 'support_team']);
        }

        if ($request->has('role') && $request->role != 'all' && $request->role != '') {
            $query->where('account_type', $request->role);
        }

        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('is_active', $request->status);
        }

        if ($request->has('verification_status') && $request->verification_status != 'all' && $request->verification_status != '') {
            $query->where('profile_verification_status', $request->verification_status);
        }

        if ($request->has('country') && $request->country != 'all' && $request->country != '') {
            $query->where('country', $request->country);
        }
        if ($request->has('state') && $request->state != 'all' && $request->state != '') {
            $query->where('state', $request->state);
        }

        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->get();
        $filename = "users_export_" . date('Y-m-d_H-i-s') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        fputcsv($handle, ['User ID', 'Name', 'Email', 'Role', 'Phone', 'Country', 'Status', 'Verification', 'Registered Date']);

        foreach ($users as $user) {
            fputcsv($handle, [
                $user->user_id,
                $user->first_name . ' ' . $user->last_name,
                $user->email,
                ucwords(str_replace('_', ' ', $user->account_type)),
                "\t" . $user->phone,
                $user->country,
                $user->is_active ? 'Active' : 'Frozen',
                ucfirst($user->profile_verification_status),
                $user->created_at->format('d M Y')
            ]);
        }

        fclose($handle);
        exit;
    }

    public function exportCustomerSource(Request $request)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_view_users)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }

        $query = User::where('id', '!=', auth()->id());

        if (!auth()->user()->isAdmin()) {
            $query->whereNotIn('account_type', ['admin', 'manager', 'support_team']);
        }

        if ($request->has('role') && $request->role != 'all' && $request->role != '') {
            $query->where('account_type', $request->role);
        }

        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('is_active', $request->status);
        }

        if ($request->has('verification_status') && $request->verification_status != 'all' && $request->verification_status != '') {
            $query->where('profile_verification_status', $request->verification_status);
        }

        if ($request->has('country') && $request->country != 'all' && $request->country != '') {
            $query->where('country', $request->country);
        }
        if ($request->has('state') && $request->state != 'all' && $request->state != '') {
            $query->where('state', $request->state);
        }

        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->get();
        $filename = "Customers_Detail_Report_" . date('Y-m-d_H-i-s') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0",
            "Expires"             => "0"
        ];

        $columns = [
            'User ID', 'Account Type', 'Name', 'First Name', 'Last Name', 'Email', 'Phone', 
            'Country ISO', 'Country', 'State', 'City', 'Zip Code', 'Address', 'DOB', 
            'ID Type', 'ID Number', 'Primary Contact', 'WhatsApp Number', 
            'Business Name', 'Business Type', 'Registration Number', 'Business Contact', 
            'Business Email', 'Website', 'Representative Name', 'Designation',
            'Exam Purpose', 'Highest Education', 'Passing Year', 'Preferred Countries',
            'Bank Name', 'Bank Country', 'Account Number', 'Status', 'Verification', 'Registered Date'
        ];

        $callback = function() use($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($users as $user) {
                $row = [
                    $user->user_id,
                    ucwords(str_replace('_', ' ', $user->account_type)),
                    $user->name,
                    $user->first_name,
                    $user->last_name,
                    $user->email,
                    "\t" . $user->phone,
                    $user->country_iso,
                    $user->country,
                    $user->state,
                    $user->city,
                    $user->post_code ?? $user->zip_code ?? '',
                    $user->address,
                    $user->dob,
                    $user->id_type,
                    $user->id_number,
                    $user->primary_contact,
                    "\t" . $user->whatsapp_number,
                    $user->business_name,
                    $user->business_type,
                    $user->registration_number,
                    "\t" . $user->business_contact,
                    $user->business_email,
                    $user->website,
                    $user->representative_name,
                    $user->designation,
                    $user->exam_purpose,
                    $user->highest_education,
                    $user->passing_year,
                    is_array($user->preferred_countries) ? implode(', ', $user->preferred_countries) : $user->preferred_countries,
                    $user->bank_name,
                    $user->bank_country,
                    $user->account_number,
                    $user->is_active ? 'Active' : 'Frozen',
                    ucfirst($user->profile_verification_status),
                    $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : 'N/A',
                ];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function profile()
    {
        $user = auth()->user();
        return view('dashboard.my-profile.index', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'id_type' => 'nullable|string|max:255',
            'id_number' => 'nullable|string|max:255',
            'primary_contact' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:255',
            // Business fields
            'business_name' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'representative_name' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'social_media' => 'nullable|url|max:255',
            // Student fields
            'exam_purpose' => 'nullable|string|max:255',
            'highest_education' => 'nullable|string|max:255',
            'passing_year' => 'nullable|integer',
            'preferred_countries' => 'nullable|array',
            'bank_name' => 'nullable|string|max:255',
            'bank_country' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            // Support fields
            'reference_name' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'reference_email' => 'nullable|email|max:255',
            'reference_phone' => 'nullable|string|max:255',
            'social_link' => 'nullable|url|max:255',
            // Files
            'profile_photo' => 'nullable|image|max:2048',
            'id_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_doc_final' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'registration_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'photograph' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'reference_letter' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ];

        $request->validate($rules);

        $data = $request->except(['_token', 'profile_photo', 'id_doc', 'id_doc_final', 'registration_doc', 'id_document', 'photograph', 'reference_letter', 'preferred_countries']);

        if ($request->has('preferred_countries')) {
            $data['preferred_countries'] = $request->preferred_countries;
        }

        if ($request->has('country')) {
            if (empty($request->country)) {
                $data['country_iso'] = null;
            } else {
                $countries = \App\Helpers\CountryHelper::getAllCountries();
                $countryIso = array_search($request->country, $countries);
                $data['country_iso'] = ($countryIso !== false) ? $countryIso : null;
            }
        }

        // Handle File Uploads
        $fileFields = ['profile_photo', 'id_doc', 'id_doc_final', 'registration_doc', 'id_document', 'photograph', 'reference_letter'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store('users/' . $field, 'public');
                $data[$field] = $path;
                
                // If profile_photo or photograph is uploaded, sync to profile_photo
                if ($field === 'profile_photo' || $field === 'photograph') {
                    $data['profile_photo'] = $path;
                }
            }
        }

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function managers(Request $request)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_view_users)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }

        $query = User::where('account_type', 'manager');

        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('is_active', $request->status);
        }

        if ($request->has('country') && $request->country != 'all' && $request->country != '') {
            $query->where('country', $request->country);
        }
        if ($request->has('state') && $request->state != 'all' && $request->state != '') {
            $query->where('state', $request->state);
        }

        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('time') && $request->time != '') {
            $query->whereTime('last_login_at', $request->time);
        }

        if ($request->has('from_time') && $request->from_time != '') {
            $query->whereTime('last_login_at', '>=', $request->from_time);
        }
        if ($request->has('to_time') && $request->to_time != '') {
            $query->whereTime('last_login_at', '<=', $request->to_time);
        }

        if ($request->has('rating') && $request->rating != 'all' && $request->rating != '') {
            $query->where('rating', '>=', $request->rating);
        }

        if ($request->has('category') && $request->category != 'all' && $request->category != '') {
            $query->where('category', $request->category);
        }

        $perPage = $request->input('per_page', 10);
        $users = $query->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.partials.manager-table', compact('users'))->render();
        }

        return view('dashboard.pages.manager', compact('users'));
    }

    public function managersDownloadCSV(Request $request)
    {
        return $this->exportRoleCSV('manager', $request);
    }

    public function supportTeamDownloadCSV(Request $request)
    {
        return $this->exportRoleCSV('support_team', $request);
    }

    public function resellerAgentDownloadCSV(Request $request)
    {
        return $this->exportRoleCSV('reseller_agent', $request);
    }

    public function regularAgentDownloadCSV(Request $request)
    {
        return $this->exportRoleCSV('agent', $request);
    }

    public function studentDownloadCSV(Request $request)
    {
        return $this->exportRoleCSV('student', $request);
    }

    private function exportRoleCSV($role, $request)
    {
        $authUser = auth()->user();
        if (!$authUser) {
            abort(403, 'Unauthorized action.');
        }

        // Authorization checks matching page views
        if (in_array($role, ['manager', 'support_team', 'reseller_agent'])) {
            if (!($authUser->isAdmin() || $authUser->can_view_users)) {
                return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
            }
        } elseif (in_array($role, ['agent', 'student'])) {
            if (!($authUser->isAdmin() || $authUser->can_view_users || $authUser->isResellerAgent())) {
                return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
            }
        }

        $query = User::where('account_type', $role);

        // Scope to connected sub-agents / students if authenticated user is a reseller agent
        if ($authUser->account_type === 'reseller_agent') {
            if ($role === 'student' && !$authUser->can_view_all_countries && !empty($authUser->permitted_countries)) {
                $query->whereIn('country', $authUser->permitted_countries);
            }
            $query->where('sub_agent_id', $authUser->id);
        }

        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('is_active', $request->status);
        }

        if ($request->has('country') && $request->country != 'all' && $request->country != '') {
            $query->where('country', $request->country);
        }
        if ($request->has('state') && $request->state != 'all' && $request->state != '') {
            $query->where('state', $request->state);
        }

        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->has('time') && $request->time != '') {
            $query->whereTime('last_login_at', $request->time);
        }
        if ($request->has('from_time') && $request->from_time != '') {
            $query->whereTime('last_login_at', '>=', $request->from_time);
        }
        if ($request->has('to_time') && $request->to_time != '') {
            $query->whereTime('last_login_at', '<=', $request->to_time);
        }

        if ($request->has('rating') && $request->rating != 'all' && $request->rating != '') {
            $query->where('rating', '>=', $request->rating);
        }
        if ($request->has('category') && $request->category != 'all' && $request->category != '') {
            $query->where('category', $request->category);
        }

        if ($role === 'student') {
            if ($request->has('highest_education') && $request->highest_education != '') {
                $query->where('highest_education', 'like', "%{$request->highest_education}%");
            }
            if ($request->has('preferred_country') && $request->preferred_country != 'all' && $request->preferred_country != '') {
                $query->whereJsonContains('preferred_countries', $request->preferred_country);
            }
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        if ($role === 'student') {
            $users = $query->withCount([
                'orders as orders_count' => function ($q) {
                    $q->where('status', 'delivered');
                },
                'disputes as disputes_count'
            ])->withSum(['orders as total_revenue' => function ($q) {
                $q->where('status', 'delivered');
            }], 'amount_in_base_currency')
                ->latest()->get();
        } else {
            $users = $query->latest()->get();
        }

        $filename = "{$role}_export_" . date('Y-m-d_H-i-s') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($users, $role) {
            $handle = fopen('php://output', 'w');

            if ($role === 'student') {
                fputcsv($handle, [
                    'Sr. No.',
                    'User ID',
                    'Date of Reg.',
                    'Last Active',
                    'Full Name',
                    'Country',
                    'Email ID',
                    'Highest Education',
                    'Contact No.',
                    'Vouchers Purchased',
                    'Revenue Paid',
                    'Disputed Payments',
                    'Referral Points',
                    'Bonus Points',
                    'Status'
                ]);

                foreach ($users as $index => $user) {
                    fputcsv($handle, [
                        $index + 1,
                        $user->user_id,
                        $user->created_at ? $user->created_at->format('d M Y') : 'N/A',
                        $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : 'Never',
                        $user->first_name . ' ' . $user->last_name,
                        $user->country,
                        $user->email,
                        $user->highest_education ?? 'N/A',
                        $user->phone,
                        $user->orders_count ?? 0,
                        number_format($user->total_revenue ?? 0, 2),
                        $user->disputes_count ?? 0,
                        $user->referral_points ?? 0,
                        $user->bonus_points ?? 0,
                        $user->is_active ? 'Active' : 'Frozen'
                    ]);
                }
            } else {
                fputcsv($handle, ['User ID', 'Name', 'Email', 'Country', 'Phone', 'Status', 'Registered Date']);

                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->user_id,
                        $user->first_name . ' ' . $user->last_name,
                        $user->email,
                        $user->country,
                        $user->phone,
                        $user->is_active ? 'Active' : 'Frozen',
                        $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : 'N/A'
                    ]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function ResellerAgent(Request $request)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_view_users)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }
        $query = User::where('account_type', 'reseller_agent');

        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('is_active', $request->status);
        }
        if ($request->has('country') && $request->country != 'all' && $request->country != '') {
            $query->where('country', $request->country);
        }
        if ($request->has('state') && $request->state != 'all' && $request->state != '') {
            $query->where('state', $request->state);
        }
        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('time') && $request->time != '') {
            $query->whereTime('last_login_at', $request->time);
        }

        if ($request->has('from_time') && $request->from_time != '') {
            $query->whereTime('last_login_at', '>=', $request->from_time);
        }
        if ($request->has('to_time') && $request->to_time != '') {
            $query->whereTime('last_login_at', '<=', $request->to_time);
        }

        if ($request->has('rating') && $request->rating != 'all' && $request->rating != '') {
            $query->where('rating', '>=', $request->rating);
        }

        if ($request->has('category') && $request->category != 'all' && $request->category != '') {
            $query->where('category', $request->category);
        }

        $perPage = $request->input('per_page', 10);
        $users = $query->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.partials.reseller-table', compact('users'))->render();
        }

        return view('dashboard.pages.reseller', compact('users'));
    }

    public function SupportTeam(Request $request)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_view_users)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }
        $query = User::where('account_type', 'support_team');

        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('is_active', $request->status);
        }
        if ($request->has('country') && $request->country != 'all' && $request->country != '') {
            $query->where('country', $request->country);
        }
        if ($request->has('state') && $request->state != 'all' && $request->state != '') {
            $query->where('state', $request->state);
        }
        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('time') && $request->time != '') {
            $query->whereTime('last_login_at', $request->time);
        }

        if ($request->has('from_time') && $request->from_time != '') {
            $query->whereTime('last_login_at', '>=', $request->from_time);
        }
        if ($request->has('to_time') && $request->to_time != '') {
            $query->whereTime('last_login_at', '<=', $request->to_time);
        }

        if ($request->has('rating') && $request->rating != 'all' && $request->rating != '') {
            $query->where('rating', '>=', $request->rating);
        }

        if ($request->has('category') && $request->category != 'all' && $request->category != '') {
            $query->where('category', $request->category);
        }

        $perPage = $request->input('per_page', 10);
        $users = $query->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.partials.support-table', compact('users'))->render();
        }

        return view('dashboard.pages.support', compact('users'));
    }

    public function RegularAgent(Request $request)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_view_users || auth()->user()->isResellerAgent())) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }
        $query = User::where('account_type', 'agent');

        if (auth()->user()->account_type === 'reseller_agent') {
             $query->where('sub_agent_id', auth()->id());
        }

        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('is_active', $request->status);
        }
        if ($request->has('country') && $request->country != 'all' && $request->country != '') {
            $query->where('country', $request->country);
        }
        if ($request->has('state') && $request->state != 'all' && $request->state != '') {
            $query->where('state', $request->state);
        }
        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('time') && $request->time != '') {
            $query->whereTime('last_login_at', $request->time);
        }

        if ($request->has('from_time') && $request->from_time != '') {
            $query->whereTime('last_login_at', '>=', $request->from_time);
        }
        if ($request->has('to_time') && $request->to_time != '') {
            $query->whereTime('last_login_at', '<=', $request->to_time);
        }

        if ($request->has('rating') && $request->rating != 'all' && $request->rating != '') {
            $query->where('rating', '>=', $request->rating);
        }

        if ($request->has('category') && $request->category != 'all' && $request->category != '') {
            $query->where('category', $request->category);
        }

        $perPage = $request->input('per_page', 10);
        $users = $query->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.partials.regularAgent-table', compact('users'))->render();
        }

        return view('dashboard.pages.regularAgent', compact('users'));
    }

    public function Student(Request $request)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_view_users || auth()->user()->isResellerAgent())) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }
        $query = User::where('account_type', 'student');

        if (auth()->user()->account_type === 'reseller_agent') {
            $authUser = auth()->user();
            if (!$authUser->can_view_all_countries && !empty($authUser->permitted_countries)) {
                $query->whereIn('country', $authUser->permitted_countries);
            }
            $query->where('sub_agent_id', auth()->id());
        }

        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('is_active', $request->status);
        }
        if ($request->has('country') && $request->country != 'all' && $request->country != '') {
            $query->where('country', $request->country);
        }
        if ($request->has('state') && $request->state != 'all' && $request->state != '') {
            $query->where('state', $request->state);
        }
        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->has('highest_education') && $request->highest_education != '') {
            $query->where('highest_education', 'like', "%{$request->highest_education}%");
        }

        if ($request->has('preferred_country') && $request->preferred_country != 'all' && $request->preferred_country != '') {
            $query->whereJsonContains('preferred_countries', $request->preferred_country);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('time') && $request->time != '') {
            $query->whereTime('last_login_at', $request->time);
        }

        if ($request->has('from_time') && $request->from_time != '') {
            $query->whereTime('last_login_at', '>=', $request->from_time);
        }
        if ($request->has('to_time') && $request->to_time != '') {
            $query->whereTime('last_login_at', '<=', $request->to_time);
        }

        if ($request->has('rating') && $request->rating != 'all' && $request->rating != '') {
            $query->where('rating', '>=', $request->rating);
        }

        if ($request->has('category') && $request->category != 'all' && $request->category != '') {
            $query->where('category', $request->category);
        }

        $perPage = $request->input('per_page', 10);
        $users = $query->withCount(['orders as orders_count' => function ($q) {
            $q->where('status', 'delivered');
        }])->withSum(['orders as total_revenue' => function ($q) {
            $q->where('status', 'delivered');
        }], 'amount_in_base_currency')
            ->latest()->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.partials.student-table', compact('users'))->render();
        }

        return view('dashboard.pages.student', compact('users'));
    }
    
    public function bulkVerify(Request $request)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_approve_user)) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $ids = $request->ids;
        if (empty($ids)) {
            return response()->json(['error' => 'No users selected.'], 400);
        }

        $users = User::whereIn('id', $ids)->get();
        $count = 0;
        foreach ($users as $user) {
            $isCurrentlyVerified = in_array($user->profile_verification_status, ['verified', 'approved']);
            $newStatus = $isCurrentlyVerified ? 'pending' : 'verified';

            $user->update([
                'profile_verification_status' => $newStatus,
                'verified_at' => $newStatus === 'verified' ? now() : $user->verified_at,
                'verified_by' => $newStatus === 'verified' ? auth()->id() : $user->verified_by,
            ]);
            $count++;
        }

        return response()->json(['success' => $count . ' users status updated successfully.']);
    }

    

    public function impersonate(User $user)
    {
        if (!(auth()->user()->isAdmin() || auth()->user()->can_impersonate_user)) {
            return back()->with('error', 'Unauthorized action.');
        }

        if (!auth()->user()->isAdmin() && in_array($user->account_type, ['admin', 'manager', 'support_team'])) {
            return back()->with('error', 'You cannot impersonate administrative accounts.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot impersonate yourself.');
        }

        // Store original user ID in session
        session(['impersonator_id' => auth()->id()]);

        // Login as the user
        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'You are now impersonating ' . $user->first_name);
    }

    public function stopImpersonating()
    {
        if (!session()->has('impersonator_id')) {
            return redirect()->route('dashboard');
        }

        $originalUserId = session()->pull('impersonator_id');
        $originalUser = User::find($originalUserId);

        if ($originalUser) {
            auth()->login($originalUser);
            return redirect()->route('users.management')->with('success', 'Back to your account.');
        }

        return redirect()->route('login');
    }
}
