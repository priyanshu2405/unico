<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureProfileIsCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Allow them to visit the completion forms, logout, and email verification
            if ($request->routeIs('auth.form.student') || 
                $request->routeIs('auth.form.student.post') ||
                $request->routeIs('auth.forms.B2BResellerAgent') || 
                $request->routeIs('auth.form.agent.post') ||
                $request->routeIs('auth.form.support') ||
                $request->routeIs('auth.logout') ||
                $request->routeIs('verification.*') ||
                $request->routeIs('shufti.*')
            ) {
                return $next($request);
            }

            if ($user->account_type === 'student' && !$user->exam_purpose && $user->profile_verification_status !== 'verified') {
                return redirect()->route('auth.form.student');
            }
            if (in_array($user->account_type, ['reseller_agent', 'agent']) && !$user->business_name) {
                return redirect()->route('auth.forms.B2BResellerAgent');
            }
        }

        return $next($request);
    }
}
