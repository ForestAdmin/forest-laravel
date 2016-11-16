<?php

namespace ForestAdmin\ForestLaravel\Http\Controllers;

// use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;

class ApimapController extends Controller {
    public function index() {
        return Response::make('Liana installed correctly', 204);
    }
}
