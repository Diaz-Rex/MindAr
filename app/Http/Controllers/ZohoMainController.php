<?php

namespace App\Http\Controllers;

use App\Models\ZohoToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ZohoMainController extends Controller
{
    public function redirectToZoho(Request $request)
    {
        if (! env('ZOHO_CLIENT_ID') || ! env('ZOHO_CLIENT_SECRET')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Zoho OAuth credentials are not configured.');
        }

        if (! env('ZOHO_REDIRECT_URI')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Zoho redirect URI is not configured.');
        }

        $state = Str::random(64);
        $request->session()->put('zoho_oauth_state', $state);

        $url = rtrim(env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com'), '/').'/oauth/v2/auth?'.http_build_query([
            'scope' => 'ZohoMail.organization.groups.ALL,ZohoMail.organization.accounts.ALL,ZohoMail.accounts.ALL,ZohoMail.messages.ALL,ZohoMail.tasks.ALL,ZohoMail.partner.organization.ALL,WorkDrive.teamfolders.ALL,WorkDrive.files.ALL,WorkDrive.files.READ,ZohoFiles.files.READ,WorkDrive.files.CREATE,WorkDrive.files.UPDATE',
            'client_id' => env('ZOHO_CLIENT_ID'),
            'response_type' => 'code',
            'access_type' => 'offline',
            'redirect_uri' => env('ZOHO_REDIRECT_URI'),
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return redirect()->away($url);
    }

    public function handleCallback(Request $request)
    {
        $savedState = $request->session()->pull('zoho_oauth_state');
        $returnedState = $request->query('state');

        if (! is_string($savedState) || ! is_string($returnedState) || ! hash_equals($savedState, $returnedState)) {
            abort(419, 'Invalid Zoho OAuth state. Please connect Zoho again.');
        }

        if ($request->query('error')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Zoho authorization was cancelled or denied.');
        }

        $code = $request->query('code');

        if (! $code) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Zoho did not return an authorization code.');
        }

        $accountsUrl = rtrim(env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com'), '/');

        if ($request->query('location')) {
            $location = strtoupper($request->query('location'));
            $accountsUrl = config("services.zoho.accounts_urls.{$location}", $accountsUrl);
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(3, 500, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->post($accountsUrl.'/oauth/v2/token', [
                    'code' => $code,
                    'client_id' => env('ZOHO_CLIENT_ID'),
                    'client_secret' => env('ZOHO_CLIENT_SECRET'),
                    'redirect_uri' => env('ZOHO_REDIRECT_URI'),
                    'grant_type' => 'authorization_code',
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return redirect()
                ->route('dashboard')
                ->with('error', 'Unable to connect to Zoho. Please open /zoho/auth and try again.');
        }

        if ($response->failed() || ! $response->json('access_token')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Zoho authorization failed. Please try again.');
        }

        $zohoToken = ZohoToken::where('id', 1)->get()->first();

        if (! $zohoToken) {
            $zohoToken = new ZohoToken;
            $zohoToken->id = 1;
        }

        $zohoToken->authorized_by = $request->user()->id;
        $zohoToken->accounts_url = $accountsUrl;
        $zohoToken->api_domain = $response->json('api_domain') ?: env('ZOHO_API_BASE', 'https://www.zohoapis.com');
        $zohoToken->access_token = $response->json('access_token');

        if ($response->json('refresh_token')) {
            $zohoToken->refresh_token = $response->json('refresh_token');
        }

        $zohoToken->expires_at = now()->addSeconds(max(((int) $response->json('expires_in')) - 60, 60));
        $zohoToken->save();

        return redirect()
            ->route('dashboard')
            ->with([
                'success' => 'Zoho connected successfully.',
                'zoho_success' => 'Zoho authentication was successful.',
            ]);
    }

    public static function getAccessToken()
    {
        $zohoToken = ZohoToken::where('id', 1)->get()->first();

        if (! $zohoToken || ! $zohoToken->refresh_token) {
            throw new RuntimeException('Zoho is not connected. Open /zoho/auth first.');
        }

        if ($zohoToken->access_token && $zohoToken->expires_at && $zohoToken->expires_at->isFuture()) {
            return $zohoToken->access_token;
        }

        $accountsUrl = rtrim($zohoToken->accounts_url ?: env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com'), '/');

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(3, 500, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->post($accountsUrl.'/oauth/v2/token', [
                    'refresh_token' => $zohoToken->refresh_token,
                    'client_id' => env('ZOHO_CLIENT_ID'),
                    'client_secret' => env('ZOHO_CLIENT_SECRET'),
                    'grant_type' => 'refresh_token',
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Unable to connect to Zoho while refreshing the access token.', 0, $exception);
        }

        if ($response->failed() || ! $response->json('access_token')) {
            throw new RuntimeException('Unable to refresh the Zoho access token.');
        }

        $zohoToken->access_token = $response->json('access_token');
        $zohoToken->api_domain = $response->json('api_domain') ?: $zohoToken->api_domain;
        $zohoToken->expires_at = now()->addSeconds(max(((int) $response->json('expires_in')) - 60, 60));
        $zohoToken->save();

        return $zohoToken->access_token;
    }
}
