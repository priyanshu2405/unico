<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Stevebauman\Location\Facades\Location;

class SetUserLocation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('country_code')) {
            $ip = $request->ip(); // user IP
            
            // In local development, avoid external API calls for localhost IP
            if (app()->environment('local') && in_array($ip, ['127.0.0.1', '::1'])) {
                $position = false;
            } else {
                try {
                    $position = Location::get($ip);
                } catch (\Exception $e) {
                    $position = false;
                }
            }

            if ($position) {
                session([
                    'country_code' => $position->countryCode,
                    'country_name' => $position->countryName,
                ]);
            } else {
                session([
                    'country_code' => 'IN',
                    'country_name' => 'India',
                ]);
            }
        }

        return $next($request);
    }
}
