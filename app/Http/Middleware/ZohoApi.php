<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class ZohoApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('zoho_access_token')) {
            if ($request->session()->has('zoho_refresh_token')) {
                $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
                    'refresh_token' => $request->session()->get('zoho_refresh_token'),
                    'client_id' => Config::get('services.zoho.client_id'),
                    'client_secret' => Config::get('services.zoho.client_secret'),
                    'grant_type' => 'refresh_token',
                ]);

                $body = $response->json();

                if (isset($body['access_token'])) {
                    $request->session()->put('zoho_access_token', $body['access_token']);
                } else {
                    // If refresh fails, return JSON error for API calls
                    return response()->json(['error' => 'Authentication required'], 401);
                }
            } else {
                return response()->json(['error' => 'Authentication required'], 401);
            }
        }

        return $next($request);
    }
}
