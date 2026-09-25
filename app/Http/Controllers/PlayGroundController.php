<?php

namespace App\Http\Controllers;

use App\Models\ModelAsset;
use App\Models\Playground;
use App\Models\PlaygroundObject;
use App\Support\PlaygroundQrTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PlayGroundController extends Controller
{
    public function lobby(Request $request)
    {
        $publicPlaygroundUrl = function (Playground $playground) {
            $publicUrl = rtrim(config('app.playground_public_url'), '/');

            return $publicUrl.route('mind-ar.playground.viewer', $playground, false);
        };

        if ($request->type === 'downloadQr') {
            $validated = $request->validate([
                'qr_token' => ['required', 'string', 'size:26'],
            ]);

            $playground = Playground::where('user_id', auth()->id())
                ->where('qr_token', $validated['qr_token'])
                ->firstOrFail();

            $qrUrl = $publicPlaygroundUrl($playground);

            if ($playground->qr_url !== $qrUrl) {
                $playground->qr_url = $qrUrl;
                $playground->mind_target_path = null;
                $playground->mind_target_version = null;
                $playground->target_compiled_at = null;
                $playground->save();
            }

            $png = PlaygroundQrTarget::png($playground);

            $fileName = Str::slug($playground->name);

            if ($fileName === '') {
                $fileName = 'playground';
            }

            return response($png, 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'-qr.png"',
            ]);
        }

        if ($request->type === 'store') {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:100'],
            ]);

            $playground = $request->user()->playgrounds()->create([
                'name' => $validated['name'],
                'qr_token' => (string) Str::ulid(),
            ]);

            $playground->qr_url = $publicPlaygroundUrl($playground);
            $playground->save();

            return redirect()
                ->route('mind-ar.playground.build', $playground)
                ->with('success', 'Playground created.');
        }

        $playgrounds = Playground::where('user_id', auth()->id())
            ->latest()
            ->get();

        $playgrounds->each(function (Playground $playground) use ($publicPlaygroundUrl) {
            $qrUrl = $publicPlaygroundUrl($playground);

            if ($playground->qr_url !== $qrUrl) {
                $playground->qr_url = $qrUrl;
                $playground->mind_target_path = null;
                $playground->mind_target_version = null;
                $playground->target_compiled_at = null;
                $playground->save();
            }

            $playground->qr_code = route('mind-ar.playground.target-image', $playground, false);
        });

        return view('playground.lobby', compact('playgrounds'));
    }

    public function index(Request $request, Playground $playground)
    {
        if ($playground->user_id !== auth()->id()) {
            abort(403);
        }

        $publicUrl = rtrim(config('app.playground_public_url'), '/');
        $qrUrl = $publicUrl.route('mind-ar.playground.viewer', $playground, false);

        if ($playground->qr_url !== $qrUrl) {
            $playground->qr_url = $qrUrl;
            $playground->mind_target_path = null;
            $playground->mind_target_version = null;
            $playground->target_compiled_at = null;
            $playground->save();
        }

        if ($request->type === 'saveMindTarget') {
            $validated = $request->validate([
                'mind_target' => ['required', 'file', 'max:20480'],
            ]);

            $directory = public_path('mind/playgrounds');
            $fileName = $playground->qr_token.'.mind';
            $relativePath = 'mind/playgrounds/'.$fileName;
            File::ensureDirectoryExists($directory);
            $validated['mind_target']->move($directory, $fileName);

            $playground->mind_target_path = $relativePath;
            $playground->mind_target_version = PlaygroundQrTarget::VERSION;
            $playground->target_compiled_at = now();
            $playground->save();

            return response()->json([
                'message' => 'QR image target is ready.',
                'target' => asset($relativePath),
            ]);
        }

        $assetPaths = collect(glob(public_path('gltf/*.{gltf,glb}'), GLOB_BRACE))
            ->sort()
            ->values();

        foreach ($assetPaths as $path) {
            $fileName = basename($path);
            $modelAsset = ModelAsset::where('file_name', $fileName)->get()->first();

            if (! $modelAsset) {
                $modelAsset = new ModelAsset;
            }

            $modelAsset->name = Str::headline(pathinfo($path, PATHINFO_FILENAME));
            $modelAsset->file_name = $fileName;
            $modelAsset->file_path = 'gltf/'.$fileName;
            $modelAsset->file_type = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $modelAsset->mime_type = function_exists('mime_content_type') ? (mime_content_type($path) ?: null) : null;
            $modelAsset->file_size = filesize($path) ?: 0;
            $modelAsset->active = true;

            if (! $modelAsset->exists || $modelAsset->isDirty()) {
                $modelAsset->save();
            }
        }

        if ($request->type === 'saveScene') {
            $validated = $request->validate([
                'objects' => ['present', 'array', 'max:200'],
                'objects.*.id' => ['required', 'string', 'max:100', 'distinct'],
                'objects.*.name' => ['required', 'string', 'max:100'],
                'objects.*.asset' => ['required', 'string', 'max:255'],
                'objects.*.position' => ['required', 'array', 'size:3'],
                'objects.*.position.*' => ['required', 'numeric', 'between:-100000,100000'],
                'objects.*.rotation' => ['required', 'array', 'size:3'],
                'objects.*.rotation.*' => ['required', 'numeric', 'between:-100000,100000'],
                'objects.*.scale' => ['required', 'array', 'size:3'],
                'objects.*.scale.*' => ['required', 'numeric', 'gt:0', 'max:10000'],
                'objects.*.visible' => ['sometimes', 'boolean'],
                'objects.*.locked' => ['sometimes', 'boolean'],
            ]);

            $assetKeys = collect($validated['objects'])
                ->pluck('asset')
                ->unique()
                ->values();
            $modelAssets = ModelAsset::whereIn('file_name', $assetKeys)
                ->where('active', true)
                ->get()
                ->keyBy('file_name');

            if ($modelAssets->count() !== $assetKeys->count()) {
                return response()->json([
                    'message' => 'One or more model assets are unavailable.',
                ], 422);
            }

            DB::transaction(function () use ($playground, $validated, $modelAssets) {
                PlaygroundObject::where('playground_id', $playground->id)->delete();

                foreach ($validated['objects'] as $index => $object) {
                    $playgroundObject = new PlaygroundObject;
                    $playgroundObject->playground_id = $playground->id;
                    $playgroundObject->model_asset_id = $modelAssets->get($object['asset'])->id;
                    $playgroundObject->object_uuid = $object['id'];
                    $playgroundObject->name = $object['name'];
                    $playgroundObject->position_x = $object['position'][0];
                    $playgroundObject->position_y = $object['position'][1];
                    $playgroundObject->position_z = $object['position'][2];
                    $playgroundObject->rotation_x = $object['rotation'][0];
                    $playgroundObject->rotation_y = $object['rotation'][1];
                    $playgroundObject->rotation_z = $object['rotation'][2];
                    $playgroundObject->scale_x = $object['scale'][0];
                    $playgroundObject->scale_y = $object['scale'][1];
                    $playgroundObject->scale_z = $object['scale'][2];
                    $playgroundObject->visible = $object['visible'] ?? true;
                    $playgroundObject->locked = $object['locked'] ?? false;
                    $playgroundObject->sort_order = $index;
                    $playgroundObject->save();
                }

                $playground->scene_saved_at = now();
                $playground->save();
            });

            return response()->json([
                'message' => 'Playground saved.',
                'saved_at' => $playground->fresh()->scene_saved_at->toIso8601String(),
            ]);
        }

        $modelLibrary = ModelAsset::where('active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (ModelAsset $modelAsset) => is_file(public_path($modelAsset->file_path)))
            ->values()
            ->map(fn (ModelAsset $modelAsset) => [
                'key' => $modelAsset->file_name,
                'label' => $modelAsset->name,
                'url' => asset($modelAsset->file_path),
            ]);

        $sceneObjects = PlaygroundObject::with('modelAsset')
            ->where('playground_id', $playground->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PlaygroundObject $object) => [
                'id' => $object->object_uuid,
                'name' => $object->name,
                'asset' => $object->modelAsset->file_name,
                'position' => [$object->position_x, $object->position_y, $object->position_z],
                'rotation' => [$object->rotation_x, $object->rotation_y, $object->rotation_z],
                'scale' => [$object->scale_x, $object->scale_y, $object->scale_z],
                'visible' => $object->visible,
                'locked' => $object->locked,
            ]);

        $qrTargetUrl = route('mind-ar.playground.target-image', $playground, false);
        $mindTargetReady = $playground->mind_target_version === PlaygroundQrTarget::VERSION &&
            $playground->mind_target_path &&
            is_file(public_path($playground->mind_target_path));

        return view('playground.build', compact(
            'modelLibrary',
            'playground',
            'sceneObjects',
            'qrTargetUrl',
            'mindTargetReady'
        ));
    }
}
