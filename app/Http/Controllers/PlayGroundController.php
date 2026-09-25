<?php

namespace App\Http\Controllers;

class PlayGroundController extends Controller
{
    public function index()
    {
        $modelUrl = asset('gltf/Model.gltf');

        return view('playground.build', compact('modelUrl'));
    }
}
