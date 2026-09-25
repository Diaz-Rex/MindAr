<?php

namespace App\Http\Controllers;

use App\Models\ModelAsset;
use App\Models\Playground;
use App\Models\PlaygroundObject;
use App\Support\PlaygroundQrTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

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

        if ($request->type === 'delete') {
            $validated = $request->validate([
                'qr_token' => ['required', 'string', 'size:26'],
            ]);

            $playground = Playground::where('user_id', auth()->id())
                ->where('qr_token', $validated['qr_token'])
                ->firstOrFail();
            $gltfDirectory = public_path('gltf/playgrounds/'.$playground->qr_token);
            $mindTargetPaths = collect([
                $playground->mind_target_path,
                'mind/playgrounds/'.$playground->qr_token.'.mind',
            ])->filter()->unique();

            DB::transaction(function () use ($playground) {
                PlaygroundObject::where('playground_id', $playground->id)->delete();
                ModelAsset::where('playground_id', $playground->id)->delete();
                $playground->delete();
            });
            File::deleteDirectory($gltfDirectory);

            $mindTargetPaths->each(function ($path) {
                File::delete(public_path($path));
            });

            return redirect()
                ->route('mind-ar.playground')
                ->with('success', 'Playground deleted.');
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

        if ($request->type === 'uploadModel') {
            $validated = $request->validate([
                'model_files' => ['required', 'array', 'min:1'],
                'model_files.*' => [
                    'required',
                    'file',
                    function ($attribute, $value, $fail) {
                        $extension = strtolower($value->getClientOriginalExtension());

                        if (! in_array($extension, ['dwg', 'skp', 'dae', 'glb', 'gltf', 'bin', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'ktx2', 'basis'], true)) {
                            $fail('Only DWG, SKP, DAE, GLB, GLTF, BIN, and model texture files can be uploaded.');
                        }
                    },
                ],
            ]);

            $uploadedFiles = collect($validated['model_files']);
            $modelFiles = $uploadedFiles->filter(function ($file) {
                return in_array(strtolower($file->getClientOriginalExtension()), ['dwg', 'skp', 'dae', 'glb', 'gltf'], true);
            })->values();

            if ($modelFiles->count() !== 1) {
                return response()->json([
                    'message' => 'Select exactly one DWG, SKP, DAE, GLB, or GLTF model file.',
                ], 422);
            }

            $uploadedNames = $uploadedFiles->map(function ($file) {
                return basename(str_replace('\\', '/', $file->getClientOriginalName()));
            });

            if ($uploadedNames->map(fn ($name) => strtolower($name))->unique()->count() !== $uploadedNames->count()) {
                return response()->json([
                    'message' => 'The selected files contain duplicate filenames.',
                ], 422);
            }

            $modelFile = $modelFiles->first();
            $sourceExtension = strtolower($modelFile->getClientOriginalExtension());
            $originalName = pathinfo($modelFile->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = in_array($sourceExtension, ['dwg', 'skp', 'dae'], true) ? 'glb' : $sourceExtension;
            $assetKey = Str::ulid().'.'.$extension;
            $assetDirectory = (string) Str::ulid();
            $relativeDirectory = 'gltf/playgrounds/'.$playground->qr_token.'/'.$assetDirectory;
            $modelFileName = in_array($sourceExtension, ['dwg', 'skp', 'dae'], true)
                ? (Str::slug($originalName) ?: 'model').'.glb'
                : basename(str_replace('\\', '/', $modelFile->getClientOriginalName()));
            $relativePath = $relativeDirectory.'/'.$modelFileName;
            $directory = public_path($relativeDirectory);
            $fileSize = $uploadedFiles->sum(fn ($file) => $file->getSize() ?: 0);
            $mimeType = $modelFile->getMimeType();
            $gltfData = null;
            $conversionDirectory = null;
            $convertedPath = null;

            if ($sourceExtension === 'gltf') {
                $gltfData = json_decode(File::get($modelFile->getRealPath()), true);

                if (! is_array($gltfData) || ($gltfData['asset']['version'] ?? null) !== '2.0') {
                    return response()->json([
                        'message' => 'The selected GLTF file is not valid glTF 2.0 JSON.',
                    ], 422);
                }

                $availableDependencies = $uploadedNames
                    ->mapWithKeys(fn ($name) => [strtolower($name) => $name]);
                $missingDependencies = [];

                foreach (['buffers', 'images'] as $section) {
                    foreach ($gltfData[$section] ?? [] as $index => $resource) {
                        $uri = $resource['uri'] ?? null;

                        if (! $uri || str_starts_with($uri, 'data:') || preg_match('/^https?:\/\//i', $uri)) {
                            continue;
                        }

                        $dependencyName = basename(rawurldecode(parse_url($uri, PHP_URL_PATH) ?: $uri));
                        $storedName = $availableDependencies->get(strtolower($dependencyName));

                        if (! $storedName) {
                            $missingDependencies[] = $dependencyName;

                            continue;
                        }

                        $gltfData[$section][$index]['uri'] = $storedName;
                    }
                }

                if ($missingDependencies) {
                    return response()->json([
                        'message' => 'Missing GLTF files: '.implode(', ', array_unique($missingDependencies)).'. Select the GLTF and all of its BIN/textures together.',
                    ], 422);
                }
            }

            if (in_array($sourceExtension, ['dwg', 'skp', 'dae'], true)) {
                if ($uploadedFiles->count() !== 1) {
                    return response()->json([
                        'message' => strtoupper($sourceExtension).' conversion accepts one file at a time.',
                    ], 422);
                }

                $conversionDirectory = storage_path('app/model-conversions/'.Str::ulid());
                $sourcePath = $conversionDirectory.DIRECTORY_SEPARATOR.'source.'.$sourceExtension;
                $convertedPath = $conversionDirectory.DIRECTORY_SEPARATOR.'converted.glb';
                File::ensureDirectoryExists($conversionDirectory);
                $modelFile->move($conversionDirectory, basename($sourcePath));

                $nodeBinary = env('NODE_BINARY');

                if (! $nodeBinary && PHP_OS_FAMILY === 'Windows') {
                    $windowsNode = 'C:\\Program Files\\nodejs\\node.exe';

                    if (File::exists($windowsNode)) {
                        $nodeBinary = $windowsNode;
                    }
                }

                $process = new Process([
                    $nodeBinary ?: 'node',
                    '--max-old-space-size=4096',
                    base_path('scripts/convert-model.mjs'),
                    $sourcePath,
                    $convertedPath,
                ], base_path());
                $process->setTimeout(180);

                try {
                    $process->run();
                } catch (\Throwable $exception) {
                    Log::warning('Playground model conversion process failed to start.', [
                        'type' => $sourceExtension,
                        'message' => $exception->getMessage(),
                    ]);
                    File::deleteDirectory($conversionDirectory);

                    return response()->json([
                        'message' => strtoupper($sourceExtension).' conversion failed: '.$exception->getMessage(),
                    ], 422);
                }

                if (! $process->isSuccessful() || ! File::exists($convertedPath)) {
                    $processError = trim($process->getErrorOutput().PHP_EOL.$process->getOutput());
                    preg_match('/MODEL_CONVERSION_ERROR:\s*([^\r\n]+)/i', $processError, $conversionErrorMatch);
                    preg_match('/(?:FATAL\s+)?ERROR:\s*([^\r\n]+)/i', $processError, $fatalErrorMatch);
                    $firstOutputLine = collect(preg_split('/\r\n|\r|\n/', $processError))
                        ->map(fn ($line) => trim($line))
                        ->first(fn ($line) => $line !== '');
                    $conversionMessage = $conversionErrorMatch[1]
                        ?? $fatalErrorMatch[1]
                        ?? $firstOutputLine
                        ?? 'The converter stopped with exit code '.$process->getExitCode().'.';
                    $conversionMessage = Str::limit($conversionMessage, 500, '...');

                    Log::warning('Playground model conversion failed.', [
                        'type' => $sourceExtension,
                        'exit_code' => $process->getExitCode(),
                        'output' => Str::limit($processError, 4000, '...'),
                    ]);
                    File::deleteDirectory($conversionDirectory);

                    return response()->json([
                        'message' => strtoupper($sourceExtension).' conversion failed: '.$conversionMessage,
                    ], 422);
                }

                $fileSize = File::size($convertedPath);
                $mimeType = 'model/gltf-binary';
            }

            File::ensureDirectoryExists($directory);

            try {
                if ($convertedPath) {
                    File::copy($convertedPath, $directory.DIRECTORY_SEPARATOR.$modelFileName);
                } else {
                    foreach ($uploadedFiles as $uploadedFile) {
                        $uploadedName = basename(str_replace('\\', '/', $uploadedFile->getClientOriginalName()));

                        if ($uploadedFile === $modelFile && $gltfData !== null) {
                            File::put(
                                $directory.DIRECTORY_SEPARATOR.$uploadedName,
                                json_encode($gltfData, JSON_UNESCAPED_SLASHES)
                            );
                        } else {
                            $uploadedFile->move($directory, $uploadedName);
                        }
                    }
                }

                $modelAsset = new ModelAsset;
                $modelAsset->user_id = auth()->id();
                $modelAsset->playground_id = $playground->id;
                $modelAsset->name = Str::limit(Str::headline($originalName), 100, '');
                $modelAsset->file_name = $assetKey;
                $modelAsset->file_path = $relativePath;
                $modelAsset->file_type = $extension;
                $modelAsset->mime_type = $mimeType;
                $modelAsset->file_size = $fileSize;
                $modelAsset->active = true;
                $modelAsset->save();
            } catch (\Throwable $exception) {
                File::deleteDirectory($directory);

                throw $exception;
            } finally {
                if ($conversionDirectory) {
                    File::deleteDirectory($conversionDirectory);
                }
            }

            return response()->json([
                'message' => 'Model uploaded.',
                'asset' => [
                    'key' => $modelAsset->file_name,
                    'label' => $modelAsset->name,
                    'url' => asset($modelAsset->file_path),
                ],
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
            $modelAsset->user_id = null;
            $modelAsset->playground_id = null;
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
                ->where(function ($query) use ($playground) {
                    $query->whereNull('playground_id')
                        ->orWhere('playground_id', $playground->id);
                })
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
            ->where(function ($query) use ($playground) {
                $query->whereNull('playground_id')
                    ->orWhere('playground_id', $playground->id);
            })
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
