<?php

namespace App\Http\Controllers;

use App\Models\Playground;
use App\Models\PlaygroundObject;
use App\Support\PlaygroundQrTarget;
use Illuminate\Http\Request;

class MindArController extends Controller
{
    public function index(Request $request, ?Playground $playground = null)
    {
        if ($request->route('type') === 'targetImage' && $playground) {
            if (! $playground->qr_url) {
                $publicUrl = rtrim(config('app.playground_public_url'), '/');
                $playground->qr_url = $publicUrl.route('mind-ar.playground.viewer', $playground, false);
            }

            $png = PlaygroundQrTarget::png($playground);

            return response($png, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        $targetReady = false;
        $targetPath = null;

        if ($playground &&
            $playground->mind_target_version === PlaygroundQrTarget::VERSION &&
            $playground->mind_target_path &&
            is_file(public_path($playground->mind_target_path))) {
            $targetReady = true;
            $targetPath = $playground->mind_target_path;
        }

        $mindArConfig = [
            'target' => $targetPath ? asset($targetPath) : null,
            'target_ready' => $targetReady,
            'scene_scale' => 0.18,
        ];

        if ($playground) {
            $sceneObjects = PlaygroundObject::with('modelAsset')
                ->where('playground_id', $playground->id)
                ->where('visible', true)
                ->orderBy('sort_order')
                ->get()
                ->filter(function (PlaygroundObject $object) {
                    return $object->modelAsset &&
                        $object->modelAsset->active === true &&
                        is_file(public_path($object->modelAsset->file_path));
                })
                ->values()
                ->map(fn (PlaygroundObject $object) => [
                    'id' => $object->object_uuid,
                    'name' => $object->name,
                    'asset_id' => $object->modelAsset->id,
                    'asset_url' => asset($object->modelAsset->file_path),
                    'position' => [$object->position_x, $object->position_y, $object->position_z],
                    'rotation' => [$object->rotation_x, $object->rotation_y, $object->rotation_z],
                    'scale' => [$object->scale_x, $object->scale_y, $object->scale_z],
                ]);
        } else {
            $sceneObjects = collect();
        }

        $modelAssets = $sceneObjects
            ->unique('asset_id')
            ->map(fn (array $object) => [
                'id' => $object['asset_id'],
                'url' => $object['asset_url'],
            ])
            ->values();

        return view('MindAr.mainPage', compact('mindArConfig', 'modelAssets', 'playground', 'sceneObjects'));
    }
}
