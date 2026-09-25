<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthorityChecker;
use App\Http\Middleware\CheckIfActive;
use App\Models\Playground;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaygroundLobbyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            AuthorityChecker::class,
            CheckIfActive::class,
        ]);
    }

    public function test_authenticated_user_can_open_the_playground_lobby(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('mind-ar.playground'));

        $response->assertOk();
        $response->assertSee('No playgrounds yet');
    }

    public function test_user_can_create_a_named_qr_playground(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('mind-ar.playground'), [
            'type' => 'store',
            'name' => 'Product display',
        ]);

        $playground = Playground::where('user_id', $user->id)->firstOrFail();

        $response->assertRedirect(route('mind-ar.playground.build', $playground));
        $this->assertSame(26, strlen($playground->qr_token));
        $this->assertDatabaseHas('playgrounds', [
            'user_id' => $user->id,
            'name' => 'Product display',
        ]);
    }

    public function test_owner_can_open_builder_and_qr_can_open_public_mindar_page(): void
    {
        $user = User::factory()->create();
        $playground = $user->playgrounds()->create([
            'name' => 'Lobby QR',
            'qr_token' => (string) Str::ulid(),
        ]);

        $this->actingAs($user)
            ->get(route('mind-ar.playground.build', $playground))
            ->assertOk()
            ->assertSee('3D Playground');

        $this->get(route('mind-ar.playground'))
            ->assertOk()
            ->assertSee('Lobby QR')
            ->assertSee('data:image/svg+xml;base64,', false);

        $download = $this->post(route('mind-ar.playground'), [
            'type' => 'downloadQr',
            'qr_token' => $playground->qr_token,
        ]);

        $download->assertOk();
        $download->assertHeader('Content-Type', 'image/png');
        $download->assertDownload('lobby-qr-qr.png');
        $this->assertStringStartsWith("\x89PNG\r\n\x1A\n", $download->getContent());
        $this->assertSame('image/png', getimagesizefromstring($download->getContent())['mime']);

        $this->get(route('mind-ar.playground.viewer', $playground))
            ->assertOk()
            ->assertSee('<title>Lobby QR</title>', false);
    }

    public function test_another_user_cannot_open_the_builder(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();
        $playground = $owner->playgrounds()->create([
            'name' => 'Private editor',
            'qr_token' => (string) Str::ulid(),
        ]);

        $this->actingAs($anotherUser)
            ->get(route('mind-ar.playground.build', $playground))
            ->assertForbidden();

        $this->post(route('mind-ar.playground'), [
            'type' => 'downloadQr',
            'qr_token' => $playground->qr_token,
        ])->assertNotFound();
    }

    public function test_public_qr_page_remains_available_to_authenticated_users_without_permissions(): void
    {
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        $playground = $owner->playgrounds()->create([
            'name' => 'Public QR',
            'qr_token' => (string) Str::ulid(),
        ]);

        $this->withMiddleware([
            AuthorityChecker::class,
            CheckIfActive::class,
        ]);

        $this->actingAs($visitor)
            ->get(route('mind-ar.playground.viewer', $playground))
            ->assertOk()
            ->assertSee('<title>Public QR</title>', false);
    }
}
