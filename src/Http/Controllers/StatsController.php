<?php

namespace ForestAdmin\ForestLaravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use ForestAdmin\ForestLaravel\Serializer\StatsSerializer;
use ForestAdmin\ForestLaravel\Http\Services\StatValueGetter;
use ForestAdmin\ForestLaravel\Http\Services\StatPieGetter;
use ForestAdmin\ForestLaravel\Http\Services\StatLineGetter;

class StatsController extends ApplicationController {

    public function show($modelName, Request $request) {
        $this->findModelsAndSchemas($modelName);
        $params = $this->getContentData($request);
        $getter = null;

        if ($this->modelResource) {
            switch($params['type']) {
                case 'Value':
                    $getter = new StatValueGetter($this->modelResource, $params);
                    break;
                case 'Pie':
                    $getter = new StatPieGetter($this->modelResource, $params);
                    break;
                case 'Line':
                    $getter = new StatLineGetter($this->modelResource, $params);
                    break;
            }

            if ($getter) {
                $getter->perform();
                return StatsSerializer::serialize($getter->values);
            }

            return Response::make('Not implemented', 404);
        } else {
            return Response::make('Collection not found', 404);
        }
    }
    protected function getContentData(Request $request) {
        return json_decode($request->getContent(), true);
    }
}
