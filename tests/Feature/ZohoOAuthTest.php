<?php

namespace Tests\Feature;

use App\Http\Controllers\ZohoMainController;
use App\Http\Middleware\AuthorityChecker;
use App\Http\Middleware\CheckIfActive;
use App\Models\User;
use App\Models\ZohoToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZohoOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            AuthorityChecker::class,
            CheckIfActive::class,
        ]);

        Env::getRepository()->set('ZOHO_CLIENT_ID', 'test-client-id');
        Env::getRepository()->set('ZOHO_CLIENT_SECRET', 'test-client-secret');
        Env::getRepository()->set('ZOHO_REDIRECT_URI', 'http://localhost/zoho/callback');
        Env::getRepository()->set('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com');
        Env::getRepository()->set('ZOHO_API_BASE', 'https://www.zohoapis.com');
    }

    public function test_authenticated_user_can_start_zoho_authorization(): void
    {
        $user = User::factory()->create();

        $this->withMiddleware(AuthorityChecker::class);

        $response = $this->actingAs($user)->get(route('zoho.auth'));

        $response->assertRedirectContains('https://accounts.zoho.com/oauth/v2/auth?');
        $response->assertSessionHas('zoho_oauth_state');

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame('test-client-id', $query['client_id']);
        $this->assertSame('ZohoMail.organization.groups.ALL,ZohoMail.organization.accounts.ALL,ZohoMail.accounts.ALL,ZohoMail.messages.ALL,ZohoMail.tasks.ALL,ZohoMail.partner.organization.ALL,WorkDrive.teamfolders.ALL,WorkDrive.files.ALL,WorkDrive.files.READ,ZohoFiles.files.READ,WorkDrive.files.CREATE,WorkDrive.files.UPDATE', $query['scope']);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame(session('zoho_oauth_state'), $query['state']);
    }

    public function test_callback_saves_encrypted_tokens_and_get_access_token_returns_the_current_token(): void
    {
        Http::fake([
            'https://accounts.zoho.com/oauth/v2/token' => Http::response([
                'access_token' => 'current-access-token',
                'refresh_token' => 'long-lived-refresh-token',
                'expires_in' => 3600,
                'api_domain' => 'https://www.zohoapis.com',
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['zoho_oauth_state' => 'valid-state'])
            ->get(route('zoho.callback', [
                'code' => 'authorization-code',
                'state' => 'valid-state',
                'location' => 'US',
            ]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Zoho connected successfully.')
            ->assertSessionHas('zoho_success', 'Zoho authentication was successful.');

        $zohoToken = ZohoToken::findOrFail(1);

        $this->assertSame($user->id, $zohoToken->authorized_by);
        $this->assertSame('current-access-token', $zohoToken->access_token);
        $this->assertSame('long-lived-refresh-token', $zohoToken->refresh_token);
        $this->assertNotSame('current-access-token', DB::table('zoho_tokens')->value('access_token'));
        $this->assertSame('current-access-token', ZohoMainController::getAccessToken());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="zoho-success-modal"', false)
            ->assertSee('Zoho authentication was successful.');

        Http::assertSentCount(1);
    }

    public function test_get_access_token_refreshes_an_expired_token(): void
    {
        Http::fake([
            'https://accounts.zoho.com/oauth/v2/token' => Http::response([
                'access_token' => 'refreshed-access-token',
                'expires_in' => 3600,
            ]),
        ]);

        ZohoToken::create([
            'id' => 1,
            'accounts_url' => 'https://accounts.zoho.com',
            'api_domain' => 'https://www.zohoapis.com',
            'access_token' => 'expired-access-token',
            'refresh_token' => 'long-lived-refresh-token',
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertSame('refreshed-access-token', ZohoMainController::getAccessToken());
        $this->assertSame('refreshed-access-token', ZohoToken::findOrFail(1)->access_token);

        Http::assertSent(function ($request) {
            return $request['grant_type'] === 'refresh_token'
                && $request['refresh_token'] === 'long-lived-refresh-token';
        });
    }
}
