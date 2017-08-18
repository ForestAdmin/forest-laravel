<?php

namespace ForestAdmin\ForestLaravel\Http\Controllers;

use Carbon\Carbon;
use ForestAdmin\ForestLaravel\Bootstraper;
use ForestAdmin\ForestLaravel\Http\Services\SchemaUtils;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    protected function streamResponseCSV($request, $modelName, $batchQuery) {
        $filename = $request->filename.'.csv';
        $headers = [
            'Content-type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename='.$filename,
            'Last-Modified' => Carbon::now()->timestamp,
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache'
        ];

        $response = new StreamedResponse(function () use ($request,
          $modelName, $batchQuery) {
            $CSVHeader = explode(',', $request->header);

            foreach ($request->request as $key => $value) {
                if ($key == 'fields') {
                    $fields = $value;
                    $fieldNamesRequested = explode(',', $fields[$modelName]);
                }
            }

            $handle = fopen('php://output', 'w');
            fputcsv($handle, $CSVHeader);

            $batchQuery->chunk(1000, function ($records) use
              ($handle, $modelName, $fields, $fieldNamesRequested) {
                foreach ($records as $record) {
                    $values = array_map(function ($fieldName) use
                      ($record, $fields) {
                        if (array_key_exists($fieldName, $fields)) {
                            return $record->{$fieldName}
                              ->{$fields[$fieldName]};
                        } else if ($record->{$fieldName}) {
                            return $record->{$fieldName};
                        } else {
                            return '';
                        }
                      }, $fieldNamesRequested);

                    fputcsv($handle, $values);
                }
            });

            fclose($handle);
        }, 200, $headers);

        return $response;
    }

    private function getModelAssociation($modelName, $associationName) {
        $model = SchemaUtils::findResource($modelName);
        $relationObj = SchemaUtils::getRelationship($model, $associationName);
        return $relationObj->getRelated();
    }

    private function getSchema($modelName, $associationName = null) {
        $name = $modelName;

        if ($associationName) {
            $relationObj = SchemaUtils::getRelationship(
              $this->modelResource, $associationName);
            $modelAssociation = $relationObj->getRelated();
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
