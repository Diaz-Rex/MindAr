<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthorityChecker;
use App\Http\Middleware\CheckIfActive;
use App\Models\ModelAsset;
use App\Models\Playground;
use App\Models\PlaygroundObject;
use App\Models\User;
use App\Support\PlaygroundQrTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
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
            ->assertSee('Scanning for a playground QR code')
            ->assertSee('id="qr-camera"', false)
            ->assertDontSee('gltf/Model.gltf', false)
            ->assertDontSee('mind/Model.mind', false)
            ->assertDontSee('mindar-image=', false);
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
            ->assertSee('3D Playground')
            ->assertSee('model-upload-progress', false)
            ->assertSee('Uploading 0%', false);

        $this->get(route('mind-ar.playground'))
            ->assertOk()
            ->assertSee('Lobby QR')
            ->assertSee(route('mind-ar.playground.target-image', $playground, false), false);

        $targetImage = $this->get(route('mind-ar.playground.target-image', $playground));

        $targetImage->assertOk();
        $targetImage->assertHeader('Content-Type', 'image/png');
        $targetImageSize = getimagesizefromstring($targetImage->getContent());
        $this->assertSame('image/png', $targetImageSize['mime']);
        $this->assertSame(1000, $targetImageSize[0]);
        $this->assertSame(1000, $targetImageSize[1]);

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
            $this->assertSame(3, $playground->mind_target_version);
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

    public function test_owner_can_upload_a_model_only_for_its_playground(): void
    {
        $user = User::factory()->create();
        $playground = $user->playgrounds()->create([
            'name' => 'Model uploads',
            'qr_token' => (string) Str::ulid(),
        ]);
        $otherPlayground = $user->playgrounds()->create([
            'name' => 'Other playground',
            'qr_token' => (string) Str::ulid(),
        ]);
        $uploadedPath = null;

        try {
            $response = $this->actingAs($user)
                ->post(route('mind-ar.playground.build', $playground), [
                    'type' => 'uploadModel',
                    'model_files' => [
                        UploadedFile::fake()->create('private-chair.glb', 100, 'model/gltf-binary'),
                    ],
                ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Model uploaded.')
                ->assertJsonPath('asset.label', 'Private Chair');

            $modelAsset = ModelAsset::where('playground_id', $playground->id)->firstOrFail();
            $uploadedPath = public_path($modelAsset->file_path);

            $this->assertSame($user->id, $modelAsset->user_id);
            $this->assertSame('glb', $modelAsset->file_type);
            $this->assertFileExists($uploadedPath);

            $this->get(route('mind-ar.playground.build', $playground))
                ->assertOk()
                ->assertSee('Private Chair');

            $this->get(route('mind-ar.playground.build', $otherPlayground))
                ->assertOk()
                ->assertDontSee('Private Chair');

            $this->postJson(route('mind-ar.playground.build', $otherPlayground), [
                'type' => 'saveScene',
                'objects' => [[
                    'id' => 'foreign-model',
                    'name' => 'Foreign model',
                    'asset' => $modelAsset->file_name,
                    'position' => [0, 0, 0],
                    'rotation' => [0, 0, 0],
                    'scale' => [1, 1, 1],
                ]],
            ])->assertUnprocessable();
        } finally {
            if ($uploadedPath) {
                File::delete($uploadedPath);
            }
        }
    }

    public function test_owner_can_upload_a_sketchup_model_and_store_the_converted_glb(): void
    {
        $user = User::factory()->create();
        $playground = $user->playgrounds()->create([
            'name' => 'SketchUp conversion',
            'qr_token' => (string) Str::ulid(),
        ]);
        $fixtureDirectory = storage_path('framework/testing/model-conversion');
        $fixturePath = $fixtureDirectory.DIRECTORY_SEPARATOR.'simple-table.skp';
        $assetDirectory = null;

        File::ensureDirectoryExists($fixtureDirectory);
        $fixtureProcess = new Process([
            'node',
            '--input-type=module',
            '-e',
            "import { writeFileSync } from 'node:fs'; import { create } from 'openskp'; const builder = create(); builder.addFace([[0,0,0],[10,0,0],[10,10,0],[0,10,0]]); writeFileSync(process.argv[1], builder.toBytes());",
            $fixturePath,
        ], base_path());
        $fixtureProcess->mustRun();

        try {
            $response = $this->actingAs($user)
                ->post(route('mind-ar.playground.build', $playground), [
                    'type' => 'uploadModel',
                    'model_files' => [
                        new UploadedFile($fixturePath, 'simple-table.skp', 'application/octet-stream', null, true),
                    ],
                ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Model uploaded.')
                ->assertJsonPath('asset.label', 'Simple Table');

            $modelAsset = ModelAsset::where('playground_id', $playground->id)->firstOrFail();
            $assetPath = public_path($modelAsset->file_path);
            $assetDirectory = dirname($assetPath);

            $this->assertSame('glb', $modelAsset->file_type);
            $this->assertStringEndsWith('.glb', $modelAsset->file_path);
            $this->assertFileExists($assetPath);
            $this->assertSame('glTF', substr(File::get($assetPath), 0, 4));
        } finally {
            if ($assetDirectory) {
                File::deleteDirectory($assetDirectory);
            }
            File::deleteDirectory($fixtureDirectory);
        }
    }

    public function test_invalid_sketchup_upload_returns_the_converter_error(): void
    {
        $user = User::factory()->create();
        $playground = $user->playgrounds()->create([
            'name' => 'Invalid SketchUp conversion',
            'qr_token' => (string) Str::ulid(),
        ]);

        $this->actingAs($user)
            ->post(route('mind-ar.playground.build', $playground), [
                'type' => 'uploadModel',
                'model_files' => [
                    UploadedFile::fake()->createWithContent('invalid.skp', 'not a sketchup file'),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'SKP conversion failed: Not a valid SketchUp file (bad header magic) | stage=header');

        $this->assertDatabaseMissing('model_assets', [
            'playground_id' => $playground->id,
        ]);
    }

    public function test_owner_can_upload_a_dae_model_and_store_the_converted_glb(): void
    {
        $user = User::factory()->create();
        $playground = $user->playgrounds()->create([
            'name' => 'DAE conversion',
            'qr_token' => (string) Str::ulid(),
        ]);
        $dae = <<<'DAE'
<?xml version="1.0" encoding="utf-8"?>
<COLLADA xmlns="http://www.collada.org/2005/11/COLLADASchema" version="1.4.1">
  <asset><unit name="meter" meter="1"/><up_axis>Y_UP</up_axis></asset>
  <library_geometries>
    <geometry id="triangle" name="Triangle">
      <mesh>
        <source id="triangle-positions">
          <float_array id="triangle-positions-array" count="9">0 0 0 1 0 0 0 1 0</float_array>
          <technique_common><accessor source="#triangle-positions-array" count="3" stride="3"><param name="X" type="float"/><param name="Y" type="float"/><param name="Z" type="float"/></accessor></technique_common>
        </source>
        <vertices id="triangle-vertices"><input semantic="POSITION" source="#triangle-positions"/></vertices>
        <triangles count="1"><input semantic="VERTEX" source="#triangle-vertices" offset="0"/><p>0 1 2</p></triangles>
      </mesh>
    </geometry>
  </library_geometries>
  <library_visual_scenes><visual_scene id="Scene"><node id="Triangle"><instance_geometry url="#triangle"/></node></visual_scene></library_visual_scenes>
  <scene><instance_visual_scene url="#Scene"/></scene>
</COLLADA>
DAE;
        $assetDirectory = null;

        try {
            $response = $this->actingAs($user)
                ->post(route('mind-ar.playground.build', $playground), [
                    'type' => 'uploadModel',
                    'model_files' => [
                        UploadedFile::fake()->createWithContent('triangle.dae', $dae),
                    ],
                ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Model uploaded.')
                ->assertJsonPath('asset.label', 'Triangle');

            $modelAsset = ModelAsset::where('playground_id', $playground->id)->firstOrFail();
            $assetPath = public_path($modelAsset->file_path);
            $assetDirectory = dirname($assetPath);

            $this->assertSame('glb', $modelAsset->file_type);
            $this->assertStringEndsWith('.glb', $modelAsset->file_path);
            $this->assertSame('glTF', substr(File::get($assetPath), 0, 4));
        } finally {
            if ($assetDirectory) {
                File::deleteDirectory($assetDirectory);
            }
        }
    }

    public function test_owner_can_delete_a_playground_and_all_related_files(): void
    {
        $user = User::factory()->create();
        $playground = $user->playgrounds()->create([
            'name' => 'Delete everything',
            'qr_token' => (string) Str::ulid(),
            'mind_target_path' => null,
        ]);
        $assetDirectory = public_path('gltf/playgrounds/'.$playground->qr_token.'/asset');
        $assetPath = $assetDirectory.'/model.glb';
        $mindPath = public_path('mind/playgrounds/'.$playground->qr_token.'.mind');

        File::ensureDirectoryExists($assetDirectory);
        File::ensureDirectoryExists(dirname($mindPath));
        File::put($assetPath, 'model');
        File::put($mindPath, 'target');

        $playground->mind_target_path = 'mind/playgrounds/'.$playground->qr_token.'.mind';
        $playground->mind_target_version = PlaygroundQrTarget::VERSION;
        $playground->target_compiled_at = now();
        $playground->save();

        $modelAsset = ModelAsset::create([
            'user_id' => $user->id,
            'playground_id' => $playground->id,
            'name' => 'Delete Model',
            'file_name' => (string) Str::ulid().'.glb',
            'file_path' => 'gltf/playgrounds/'.$playground->qr_token.'/asset/model.glb',
            'file_type' => 'glb',
            'file_size' => 5,
            'active' => true,
        ]);
        PlaygroundObject::create([
            'playground_id' => $playground->id,
            'model_asset_id' => $modelAsset->id,
            'object_uuid' => 'delete-object',
            'name' => 'Delete Object',
        ]);

        try {
            $this->actingAs($user)
                ->post(route('mind-ar.playground'), [
                    'type' => 'delete',
                    'qr_token' => $playground->qr_token,
                ])
                ->assertRedirect(route('mind-ar.playground'))
                ->assertSessionHas('success', 'Playground deleted.');

            $this->assertDatabaseMissing('playgrounds', ['id' => $playground->id]);
            $this->assertDatabaseMissing('model_assets', ['id' => $modelAsset->id]);
            $this->assertDatabaseMissing('playground_objects', ['object_uuid' => 'delete-object']);
            $this->assertDirectoryDoesNotExist(public_path('gltf/playgrounds/'.$playground->qr_token));
            $this->assertFileDoesNotExist($mindPath);
        } finally {
            File::deleteDirectory(public_path('gltf/playgrounds/'.$playground->qr_token));
            File::delete($mindPath);
        }
    }

    public function test_gltf_upload_requires_and_stores_its_external_buffer(): void
    {
        $user = User::factory()->create();
        $playground = $user->playgrounds()->create([
            'name' => 'GLTF dependencies',
            'qr_token' => (string) Str::ulid(),
        ]);
        $gltf = json_encode([
            'asset' => ['version' => '2.0'],
            'buffers' => [[
                'uri' => 'buffer.bin',
                'byteLength' => 4,
            ]],
            'scenes' => [[]],
            'scene' => 0,
        ]);
        $assetDirectory = null;

        $this->actingAs($user)
            ->post(route('mind-ar.playground.build', $playground), [
                'type' => 'uploadModel',
                'model_files' => [
                    UploadedFile::fake()->createWithContent('sculpture.gltf', $gltf),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Missing GLTF files: buffer.bin. Select the GLTF and all of its BIN/textures together.');

        try {
            $response = $this->actingAs($user)
                ->post(route('mind-ar.playground.build', $playground), [
                    'type' => 'uploadModel',
                    'model_files' => [
                        UploadedFile::fake()->createWithContent('sculpture.gltf', $gltf),
                        UploadedFile::fake()->createWithContent('buffer.bin', 'test'),
                    ],
                ]);

            $response->assertOk()
                ->assertJsonPath('asset.label', 'Sculpture');

            $modelAsset = ModelAsset::where('playground_id', $playground->id)->firstOrFail();
            $assetDirectory = dirname(public_path($modelAsset->file_path));

            $this->assertFileExists($assetDirectory.DIRECTORY_SEPARATOR.'sculpture.gltf');
            $this->assertFileExists($assetDirectory.DIRECTORY_SEPARATOR.'buffer.bin');
        } finally {
            if ($assetDirectory) {
                File::deleteDirectory($assetDirectory);
            }
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
        $assetDirectory = public_path('gltf/playgrounds/'.$playground->qr_token.'/test-asset');
        $assetPath = $assetDirectory.'/saved-model.glb';
        File::ensureDirectoryExists($assetDirectory);
        File::put($assetPath, 'model');
        $asset = ModelAsset::create([
            'user_id' => $user->id,
            'playground_id' => $playground->id,
            'name' => 'Model',
            'file_name' => 'saved-model.glb',
            'file_path' => 'gltf/playgrounds/'.$playground->qr_token.'/test-asset/saved-model.glb',
            'file_type' => 'glb',
            'mime_type' => 'model/gltf-binary',
            'file_size' => 5,
            'active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('mind-ar.playground.build', $playground), [
                'type' => 'saveScene',
                'objects' => [[
                    'id' => 'saved-object-1',
                    'name' => 'Saved Model',
                    'asset' => 'saved-model.glb',
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
            ->assertSee('rotation="90 0 0"', false)
            ->assertDontSee('Save changes');

        File::deleteDirectory(public_path('gltf/playgrounds/'.$playground->qr_token));
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
