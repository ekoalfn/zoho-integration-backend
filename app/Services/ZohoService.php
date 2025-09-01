<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class ZohoService
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function refreshToken()
    {
        $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'refresh_token' => $this->request->session()->get('zoho_refresh_token'),
            'client_id' => Config::get('services.zoho.client_id'),
            'client_secret' => Config::get('services.zoho.client_secret'),
            'grant_type' => 'refresh_token',
        ]);

        $body = $response->json();

        if (isset($body['access_token'])) {
            $this->request->session()->put('zoho_access_token', $body['access_token']);
            return $body['access_token'];
        }

        return null;
    }

    public function makeRequest($method, $url, $data = [], $needsOrgId = true)
    {
        $accessToken = $this->request->session()->get('zoho_access_token');

        $requestData = $data;
        if ($needsOrgId) {
            $requestData['organization_id'] = $this->request->session()->get('zoho_organization_id');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
        ])->$method($url, $requestData);

        if ($response->status() === 401) { // Token expired
            $newAccessToken = $this->refreshToken();
            if ($newAccessToken) {
                // Retry the request with the new token
                $response = Http::withHeaders([
                    'Authorization' => 'Zoho-oauthtoken ' . $newAccessToken,
                ])->$method($url, $requestData);
            }
        }

        return $response;
    }
}
