<?php

namespace App\Http\Controllers;

use App\Models\Playground;

class MindArController extends Controller
{
    public function index(?Playground $playground = null)
    {
        $mindArConfig = [
            'target' => asset('mind/Model.mind'),
            'model' => asset('gltf/Model.gltf'),
        ];

        return view('MindAr.mainPage', compact('mindArConfig', 'playground'));
    }
}
