<?php

namespace ForestAdmin\ForestLaravel\Http\Controllers;

// use App\Http\Controllers\Controller;
use ForestAdmin\ForestLaravel\Bootstraper;
use ForestAdmin\ForestLaravel\Http\Services\SchemaUtils;
use Illuminate\Routing\Controller;

class ApplicationController extends Controller {

    protected $model;
    protected $collectionSchema;
    protected $association;

    protected function findModelsAndSchemas($modelName,
      $associationName = null) {
        $this->modelResource = SchemaUtils::findResource($modelName);
        $this->schemaResource = $this->getSchema($modelName);

        if ($associationName) {
            $this->modelAssociation = $this->getModelAssociation($modelName,
              $associationName);
            $this->schemaAssociation = $this->getSchema($modelName,
              $associationName);
        }
    }

    private function getModelAssociation($modelName, $associationName) {
        $model = SchemaUtils::findResource($modelName);
        return $model->{$associationName}()->getRelated();
    }

    private function getSchema($modelName, $associationName = null) {
        $name = $modelName;

        if ($associationName) {
            $modelAssociation = $this->modelResource
                                     ->{$associationName}()
                                     ->getRelated();
            $name = SchemaUtils::findResourceName($modelAssociation);
        }

        $collections = (new Bootstraper())->getCollections();

        foreach($collections as $collection) {
            if ($collection->getName() == $name) {
                return $collection;
            }
        }

        return;
    }
}
