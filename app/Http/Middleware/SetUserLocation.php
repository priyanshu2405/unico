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
        $ip = $request->ip(); // user IP
        $position = Location::get($ip);

        // Debugging location data
        // dd($position);

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

        return $next($request);
    }
}
