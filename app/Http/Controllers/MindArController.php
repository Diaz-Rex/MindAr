<?php

namespace App\Http\Controllers;

class MindArController extends Controller
{
    public function index()
    {
        $mindArConfig = [
            'target' => asset('mind/Model.mind'),
            'model' => asset('gltf/Model.gltf'),
        ];

        return view('MindAr.mainPage', compact('mindArConfig'));
    }
}
