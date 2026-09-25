<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthorityChecker;
use App\Http\Middleware\CheckIfActive;
use App\Models\ModelAsset;
use App\Models\Playground;
use App\Models\PlaygroundObject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaygroundLobbyTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_generic_mindar_camera_scans_and_opens_playground_qr_codes(): void
    {
        $this->get(route('mind-ar.index'))
            ->assertOk()
            ->assertSee('/vendor/jsqr/jsQR-1.4.0.js', false)
            ->assertSee('Scanning for a playground QR code');
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
            ->assertSee(route('mind-ar.playground.target-image', $playground, false), false);

        $targetImage = $this->get(route('mind-ar.playground.target-image', $playground));

        $targetImage->assertOk();
        $targetImage->assertHeader('Content-Type', 'image/png');
        $this->assertSame('image/png', getimagesizefromstring($targetImage->getContent())['mime']);

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

    public function test_owner_can_store_the_compiled_qr_image_target(): void
    {
        $user = User::factory()->create();
        $playground = $user->playgrounds()->create([
            'name' => 'Compiled QR',
            'qr_token' => (string) Str::ulid(),
        ]);
        $targetPath = public_path('mind/playgrounds/'.$playground->qr_token.'.mind');

        try {
            $this->actingAs($user)
                ->post(route('mind-ar.playground.build', $playground), [
                    'type' => 'saveMindTarget',
                    'mind_target' => UploadedFile::fake()->create('target.mind', 20),
                ])
                ->assertOk()
                ->assertJsonPath('message', 'QR image target is ready.');

            $playground->refresh();
            $this->assertSame('mind/playgrounds/'.$playground->qr_token.'.mind', $playground->mind_target_path);
            $this->assertSame(2, $playground->mind_target_version);
            $this->assertNotNull($playground->target_compiled_at);
            $this->assertFileExists($targetPath);

            $this->get(route('mind-ar.playground.viewer', $playground))
                ->assertOk()
                ->assertSee(asset($playground->mind_target_path), false)
                ->assertSee('autoStart: true', false);
        } finally {
            File::delete($targetPath);
        }
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

        $this->actingAs($anotherUser)
            ->postJson(route('mind-ar.playground.build', $playground), [
                'type' => 'saveScene',
                'objects' => [],
            ])
            ->assertForbidden();

        $this->post(route('mind-ar.playground'), [
            'type' => 'downloadQr',
            'qr_token' => $playground->qr_token,
        ])->assertNotFound();
    }

    public function test_owner_can_save_and_reload_playground_scene_objects(): void
    {
        $user = User::factory()->create();
        $playground = $user->playgrounds()->create([
            'name' => 'Saved scene',
            'qr_token' => (string) Str::ulid(),
        ]);
        $asset = ModelAsset::where('file_name', 'Model.gltf')->get()->first();

        if (! $asset) {
            $asset = ModelAsset::create([
                'name' => 'Model',
                'file_name' => 'Model.gltf',
                'file_path' => 'gltf/Model.gltf',
                'file_type' => 'gltf',
                'mime_type' => 'model/gltf+json',
                'file_size' => filesize(public_path('gltf/Model.gltf')),
                'active' => true,
            ]);
        }

        $response = $this->actingAs($user)
            ->postJson(route('mind-ar.playground.build', $playground), [
                'type' => 'saveScene',
                'objects' => [[
                    'id' => 'saved-object-1',
                    'name' => 'Saved Model',
                    'asset' => 'Model.gltf',
                    'position' => [1.25, 2.5, -3.75],
                    'rotation' => [10, 20, 30],
                    'scale' => [1, 1.5, 2],
                    'visible' => true,
                    'locked' => false,
                ]],
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Playground saved.');

        $this->assertDatabaseHas('playground_objects', [
            'playground_id' => $playground->id,
            'model_asset_id' => $asset->id,
            'object_uuid' => 'saved-object-1',
            'name' => 'Saved Model',
            'position_x' => 1.25,
            'rotation_z' => 30,
            'scale_y' => 1.5,
        ]);
        $this->assertNotNull($playground->fresh()->scene_saved_at);

        $this->get(route('mind-ar.playground.build', $playground))
            ->assertOk()
            ->assertSee('saved-object-1');

        $this->get(route('mind-ar.playground.viewer', $playground))
            ->assertOk()
            ->assertSee('id="scene-object-saved-object-1"', false)
            ->assertSee('src="#model-asset-'.$asset->id.'"', false)
            ->assertSee('position="1.25 2.5 -3.75"', false)
            ->assertSee('rotation="10 20 30"', false)
            ->assertSee('scale="1 1.5 2"', false)
            ->assertSee('id="saved-playground-scene"', false)
            ->assertSee('rotation="0 0 0"', false)
            ->assertDontSee('Save changes');
    }

    public function test_owner_can_save_an_empty_scene(): void
    {
        $user = User::factory()->create();
        $playground = $user->playgrounds()->create([
            'name' => 'Empty scene',
            'qr_token' => (string) Str::ulid(),
        ]);

        PlaygroundObject::where('playground_id', $playground->id)->delete();

        $this->actingAs($user)
            ->postJson(route('mind-ar.playground.build', $playground), [
                'type' => 'saveScene',
                'objects' => [],
            ])
            ->assertOk();

        $this->assertNotNull($playground->fresh()->scene_saved_at);
        $this->assertSame(0, PlaygroundObject::where('playground_id', $playground->id)->get()->count());

        $this->get(route('mind-ar.playground.viewer', $playground))
            ->assertOk()
            ->assertSee('This playground does not have a saved visible scene yet.');
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
