<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Contact;
use App\Services\ZohoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class ZohoController extends Controller
{
    public function redirectToZoho()
    {
        $queryParams = http_build_query([
            'scope' => 'ZohoBooks.fullaccess.all,AAAServer.profile.Read',
            'client_id' => Config::get('services.zoho.client_id'),
            'response_type' => 'code',
            'redirect_uri' => Config::get('services.zoho.redirect'),
            'access_type' => 'offline',
        ]);

        return redirect('https://accounts.zoho.com/oauth/v2/auth?' . $queryParams);
    }

    public function handleZohoCallback(Request $request)
    {
        $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'code' => $request->input('code'),
            'client_id' => Config::get('services.zoho.client_id'),
            'client_secret' => Config::get('services.zoho.client_secret'),
            'redirect_uri' => Config::get('services.zoho.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        $body = $response->json();

        if (isset($body['access_token'])) {
            $request->session()->put('zoho_access_token', $body['access_token']);
            if (isset($body['refresh_token'])) {
                $request->session()->put('zoho_refresh_token', $body['refresh_token']);
            }

            $orgResponse = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $body['access_token'],
            ])->get('https://www.zohoapis.com/books/v3/organizations');

            $orgBody = $orgResponse->json();

            if (isset($orgBody['organizations'][0]['organization_id'])) {
                $request->session()->put('zoho_organization_id', $orgBody['organizations'][0]['organization_id']);
            }

            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/dashboard');
        }

        return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/error')->with('error', $body);
    }

    public function getChartOfAccounts(ZohoService $zohoService)
    {
        return $zohoService->makeRequest('get', 'https://www.zohoapis.com/books/v3/chartofaccounts')->json();
    }

    public function syncChartOfAccounts(ZohoService $zohoService)
    {
        $response = $zohoService->makeRequest('get', 'https://www.zohoapis.com/books/v3/chartofaccounts');

        if ($response->successful()) {
            $accounts = $response->json()['chartofaccounts'];

            foreach ($accounts as $account) {
                ChartOfAccount::updateOrCreate(
                    ['account_id' => $account['account_id']],
                    [
                        'account_name' => $account['account_name'],
                        'account_type' => $account['account_type_formatted'],
                        'is_active' => $account['is_active'],
                        'description' => $account['description'] ?? '',
                    ]
                );
            }

            return response()->json(['message' => 'Chart of Accounts synced successfully.']);
        }

        return response()->json(['error' => 'Failed to sync Chart of Accounts.'], $response->status());
    }

    public function getContacts(ZohoService $zohoService)
    {
        return $zohoService->makeRequest('get', 'https://www.zohoapis.com/books/v3/contacts')->json();
    }

    public function syncContacts(ZohoService $zohoService)
    {
        $response = $zohoService->makeRequest('get', 'https://www.zohoapis.com/books/v3/contacts');

        if ($response->successful()) {
            $contacts = $response->json()['contacts'];

            foreach ($contacts as $contact) {
                Contact::updateOrCreate(
                    ['contact_id' => $contact['contact_id']],
                    [
                        'contact_name' => $contact['contact_name'],
                        'company_name' => $contact['company_name'],
                        'contact_type' => $contact['contact_type'],
                        'email' => $contact['email'] ?? null,
                        'phone' => $contact['phone'] ?? null,
                        'is_active' => $contact['status'] === 'active',
                    ]
                );
            }

            return response()->json(['message' => 'Contacts synced successfully.']);
        }

        return response()->json(['error' => 'Failed to sync Contacts.'], $response->status());
    }

    public function createExpense(Request $request, ZohoService $zohoService)
    {
        $data = $request->only(['account_id', 'amount', 'paid_through_account_id', 'date', 'description', 'customer_id']);
        return $zohoService->makeRequest('post', 'https://www.zohoapis.com/books/v3/expenses', $data)->json();
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/');
    }

    public function getUser(ZohoService $zohoService)
    {
        $response = $zohoService->makeRequest('get', 'https://accounts.zoho.com/oauth/user/info', [], false);

        if ($response->successful()) {
            return $response->json();
        }

        return response()->json(['error' => 'Failed to fetch user from Zoho.'], $response->status());
    }
}
