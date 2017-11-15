<?php

namespace ForestAdmin\ForestLaravel\Http\Controllers;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use ForestAdmin\ForestLaravel\Http\Services\ResourcesGetter;
use ForestAdmin\ForestLaravel\Serializer\ResourcesSerializer;

class ResourcesController extends ApplicationController {

    public function index(Request $request, $modelName) {
        $this->findModelsAndSchemas($modelName);

        if ($this->modelResource) {
            $getter = new ResourcesGetter($modelName, $this->modelResource,
              $this->schemaResource, $request);
            $getter->perform();
            $json = ResourcesSerializer::returnJsonRecords($this->modelResource,
              $this->schemaResource, $modelName, $getter->records,
              $getter->recordsCount);
            return Response::make($json, 200);
        } else {
            return Response::make('Collection not found', 404);
        }
    }

    public function exportCSV(Request $request, $modelName) {
        $this->findModelsAndSchemas($modelName);

        if ($this->modelResource) {
            $getter = new ResourcesGetter($modelName, $this->modelResource,
              $this->schemaResource, $request);
            $batchQuery = $getter->getQueryForBatch();
            return $this->streamResponseCSV($request, $modelName, $batchQuery);
        } else {
            return Response::make('Collection not found', 404);
        }
    }

    public function create($modelName, Request $request) {
        $this->findModelsAndSchemas($modelName);

        if ($this->modelResource) {
            try {
                $record = new $this->modelResource;
                $record = $this->setAttributes($record, $request);
                $record->save();

                $this->setRelationshipsHasMany($record, $request);

                $json = ResourcesSerializer::returnJsonRecord(
                  $this->modelResource, $this->schemaResource, $modelName,
                  $record);
                return Response::make($json, 200);
            } catch(\Exception $exception) {
                return Response::make($exception->getMessage(), 400);
            }
        } else {
            return Response::make('Collection not found', 404);
        }
    }

    public function show($modelName, $recordId) {
        $this->findModelsAndSchemas($modelName);

        if ($this->modelResource) {
            $record = $this->modelResource->find($recordId);

            if ($record) {
                return ResourcesSerializer::returnJsonRecord(
                  $this->modelResource, $this->schemaResource, $modelName,
                  $record);
            } else {
                return Response::make(
                  'The '.$modelName.' #'.$recordId.' does not exist.', 404);
            }
        } else {
            return Response::make('Collection not found', 404);
        }
    }

    public function update($modelName, $recordId, Request $request) {
        $this->findModelsAndSchemas($modelName);

        if ($this->modelResource) {
            try {
                $record = $this->modelResource->findOrFail($recordId);
                $record = $this->setAttributes($record, $request, true);

                // NOTICE: Enforce updated_at timestamp
                $record->touch();

                return ResourcesSerializer::returnJsonRecord(
                  $this->modelResource, $this->schemaResource, $modelName,
                  $record);
            } catch(ModelNotFoundException $exception) {
                return Response::make('Object not found for this recordId', 404);
            } catch(\Exception $exception) {
                return Response::make($exception->getMessage(), 400);
            }
        } else {
            return Response::make('Collection not found', 404);
        }
    }

    public function destroy($modelName, $recordId) {
        $this->findModelsAndSchemas($modelName);

        if ($this->modelResource) {
            try {
                $record = $this->modelResource->findOrFail($recordId);
                $record->delete();
                return Response::make('Success', 204);
            } catch(ModelNotFoundException $exception) {
                return Response::make('Object not found for this recordId', 404);
            }
        } else {
            return Response::make('Collection not found', 404);
        }
    }

    protected function getContentData(Request $request) {
        $content = json_decode($request->getContent(), true);
        if (!array_key_exists('data', $content) ||
              !array_key_exists('attributes', $content['data'])) {
            throw new \Exception('Malformed content');
        }
        return $content['data'];
    }

    protected function setAttributes($record, $request,
      $ignoreRelationships=false) {
        $content = $this->getContentData($request);

        foreach ($this->schemaResource->getFields() as $field) {
            $fieldName = $field->getField();

            if (array_key_exists($fieldName, $content['attributes'])) {
                $valueNew = $content['attributes'][$fieldName];

                if ($field->getType() == 'Date' && !($valueNew === NULL)) {
                    $valueNew = Carbon::instance(new DateTime($valueNew));
                }
                $record->{$fieldName} = $valueNew;
            } elseif (array_key_exists('relationships', $content) &&
              array_key_exists($fieldName, $content['relationships']) &&
              !$ignoreRelationships) {
                if ($content['relationships'][$fieldName]['data']) {
                    if (array_key_exists('id',
                      $content['relationships'][$fieldName]['data'])) {
                        // NOTICE: Set belongsTo relationships
                        $valueNew = $content['relationships'][$fieldName]
                          ['data']['id'];
                        $foreignKey = $this->modelResource
                                           ->{$fieldName}()
                                           ->getForeignKey();
                        $record->{$foreignKey} = $valueNew;
                    }
                }
            }
        }

        $record->save();
        return $record;
    }

    protected function setRelationshipsHasMany($record, $request) {
        $content = $this->getContentData($request);

        foreach ($this->schemaResource->getFields() as $field) {
            $fieldName = $field->getField();

            if (array_key_exists('relationships', $content) &&
              array_key_exists($fieldName, $content['relationships']) &&
              array_key_exists('data', $content['relationships'][$fieldName])) {
                if (!array_key_exists('type', $content['relationships'][$fieldName]['data']) &&
                  sizeof($content['relationships'][$fieldName]['data']) > 0) {
                    // NOTICE: Set hasMany relationships
                    $modelAssociation = $record->{$fieldName}()->getRelated();

                    $recordsAssociated = array();
                    $values = $content['relationships'][$fieldName]['data'];
                    foreach($values as $value) {
                        if (array_key_exists('id', $value)) {
                            $recordsAssociated[] =
                              $modelAssociation->find($value['id']);
                        }
                    }

                    $record->{$fieldName}()->saveMany($recordsAssociated);
                }
            }
        }
    }
}
