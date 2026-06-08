<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use App\Models\JobapplicationModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class JobController extends Controller
{
    private function checkPermission()
    {
        $user = auth()->user();
        if ($user->account_type === 'manager' && !$user->has_job_permission) {
            abort(403, 'Unauthorized access to job applications.');
        }
    }

    // List job vacancies for Admin/Manager
    public function jobApplication(Request $request)
    {
        $this->checkPermission();
        $query = JobapplicationModel::with('vacancy');

        if ($request->filled('country')) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }

        if ($request->filled('designation')) {
            $query->where('designation', $request->designation);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $applications = $query->latest()->get();
        return view('dashboard.jobs.job_apllications', compact('applications'));
    }

    // View application details
    public function viewApplication(JobapplicationModel $application)
    {
        $this->checkPermission();
        return response()->json([
            'success' => true,
            'data' => $application
        ]);
    }

    // Create job vacancy form
    public function create()
    {
        return view('dashboard.jobs.create');
    }

    // Store job vacancy
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:manager,support_team',
            'country' => 'nullable|string|max:100',
        ]);

        JobVacancy::create($validated);
        return redirect()->route('jobs.index')->with('success', 'Job vacancy created successfully.');
    }

    // Delete vacancy
    public function destroy(JobVacancy $vacancy)
    {
        $vacancy->delete();
        return back()->with('success', 'Job vacancy deleted successfully.');
    }

    // List applications
    public function applications(Request $request)
    {
        $this->checkPermission();
        $query = JobapplicationModel::with('vacancy');

        if ($request->filled('country')) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }

        if ($request->filled('designation')) {
            $query->where('designation', $request->designation);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $applications = $query->latest()->get();
        return view('dashboard.jobs.applications', compact('applications'));
    }

    // Update application status
    public function updateApplicationStatus(Request $request, JobapplicationModel $application)
    {
        $this->checkPermission();
        $request->validate(['status' => 'required|in:selected,rejected']);
        $application->update(['status' => $request->status]);

        if ($request->status === 'selected') {
            // Check if user already exists
            $user = User::where('email', $application->email)->first();
            if (!$user) {
                $password = Str::random(10);
                $user = User::create([
                    'name' => $application->name,
                    'email' => $application->email,
                    'phone' => $application->phone,
                    'password' => Hash::make($password),
                    'account_type' => $application->vacancy ? $application->vacancy->category : 'support_team',
                    'is_active' => true,
                ]);

                // Send Email Notification
                try {
                    $message = "Congratulations! Your application has been SELECTED.\n\n" .
                        "Your account has been created on UniCou.\n" .
                        "Login Email: {$application->email}\n" .
                        "Temporary Password: {$password}\n\n" .
                        "Please login and reset your password immediately.\n" .
                        "Login URL: " . url('/login');

                    Mail::raw($message, function ($mail) use ($application) {
                        $mail->to($application->email)
                            ->subject('UniCou Job Selection - Account Created');
                    });
                } catch (\Exception $e) {
                    \Log::error("Failed to send job selection email: " . $e->getMessage());
                }
            } else {
                 // Send selection email if user already exists
                 try {
                    $message = "Congratulations! Your application has been SELECTED.\n\n" .
                        "Our team will connect with you soon.";

                    Mail::raw($message, function ($mail) use ($application) {
                        $mail->to($application->email)
                            ->subject('UniCou Job Selection');
                    });
                } catch (\Exception $e) {
                    \Log::error("Failed to send job selection email: " . $e->getMessage());
                }
            }
        } elseif ($request->status === 'rejected') {
            try {
                $message = "We regret to inform you that your application has been REJECTED.\n\n" .
                    "Thank you for your interest in UniCou.";

                Mail::raw($message, function ($mail) use ($application) {
                    $mail->to($application->email)
                        ->subject('UniCou Job Application Update');
                });
            } catch (\Exception $e) {
                \Log::error("Failed to send job rejection email: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Application status updated to ' . $request->status
        ]);
    }

    // Public list for careers
    public function careers()
    {
        $vacancies = JobVacancy::where('status', 'open')->latest()->get();
        return view('home.careers', compact('vacancies'));
    }

    // Application form
    public function applyForm(JobVacancy $vacancy)
    {
        return view('auth.forms.supportTeam', compact('vacancy'));
    }

    // Submit application
    public function submitApplication(Request $request)
    {
        try {
            $validated = $request->validate([
                'vacancy_id' => 'nullable|exists:job_vacancies,id',
                'name' => 'required|string|max:255',
                'dob' => 'required|date|before:-16 years',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'whatsapp_number' => 'required|string|max:20',
                'social_link' => 'nullable|url|max:255',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'country' => 'required|string|max:100',
                'post_code' => 'required|string|max:20',
                'reference_name' => 'required|string|max:255',
                'organization_name' => 'required|string|max:255',
                'reference_email' => 'required|email|max:255',
                'reference_phone' => 'required|string|max:20',
                'id_type' => 'required|string',
                'id_number' => 'required|string|max:50',
                'designation' => 'required|string',
                'bank_name' => 'required|string|max:255',
                'bank_country' => 'required|string|max:100',
                'bank_account_number' => 'required|string|max:50',
                'id_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'photograph' => 'required|file|mimes:jpg,jpeg,png|max:2048',
                'reference_letter' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            ], [
                'dob.before' => 'You must be at least 16 years old.',
                'photograph.max' => 'The photograph may not be greater than 2 MB.',
                'id_document.max' => 'The ID document may not be greater than 5 MB.',
                'reference_letter.max' => 'The reference letter may not be greater than 5 MB.',
            ]);

            // Handle File Uploads
            if ($request->hasFile('id_document')) {
                $validated['id_document'] = $request->file('id_document')->store('applications/ids', 'public');
            }
            if ($request->hasFile('photograph')) {
                $validated['photograph'] = $request->file('photograph')->store('applications/photos', 'public');
            }
            if ($request->hasFile('reference_letter')) {
                $validated['reference_letter'] = $request->file('reference_letter')->store('applications/references', 'public');
            }

            JobapplicationModel::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Your application has been submitted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroyApplication(JobapplicationModel $application)
    {
        $this->checkPermission();
        $application->delete();
        return response()->json([
            'success' => true,
            'message' => 'Application deleted successfully.'
        ]);
    }
}
