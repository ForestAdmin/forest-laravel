<?php

namespace ForestAdmin\ForestLaravel\Http\Controllers;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use ForestAdmin\ForestLaravel\Http\Services\HasManyGetter;
use ForestAdmin\ForestLaravel\Http\Services\SchemaUtils;
use ForestAdmin\ForestLaravel\Serializer\ResourcesSerializer;

class AssociationsController extends ApplicationController {
    public function index($modelName, $recordId, $associationName,
      Request $request) {
        $this->findModelsAndSchemas($modelName, $associationName);

        if ($this->modelResource && $this->modelAssociation) {
            $getter = new HasManyGetter($this->modelResource,
              $this->modelAssociation, $this->schemaAssociation,
              $associationName, $request);
            $getter->perform();

            return ResourcesSerializer::returnJsonRecords(
              $this->modelAssociation, $this->schemaAssociation,
              $this->schemaAssociation->getName(), $getter->records,
              $getter->recordsCount);
        } else {
            return Response::make('Collections not found', 404);
        }
    }

    public function count($modelName, $recordId, $associationName,
      Request $request) {
        $this->findModelsAndSchemas($modelName, $associationName);

        if ($this->modelResource && $this->modelAssociation) {
            $getter = new HasManyGetter($this->modelResource,
              $this->modelAssociation, $this->schemaAssociation,
              $associationName, $request);
            $getter->count();
            $count = $getter->recordsCount;
            return Response::make(['count' => $count], 200);
        } else {
            return Response::make('Collections not found', 404);
        }
    }

    public function exportCSV($modelName, $recordId, $associationName,
      Request $request) {
        $this->findModelsAndSchemas($modelName, $associationName);

        if ($this->modelResource && $this->modelAssociation) {
            $getter = new HasManyGetter($this->modelResource,
              $this->modelAssociation, $this->schemaAssociation,
              $associationName, $request);
            $batchQuery = $getter->getBaseQuery();
            return $this->streamResponseCSV($request, $associationName,
              $batchQuery);
        } else {
            return Response::make('Collections not found', 404);
        }
    }

    public function update($modelName, $recordId, $associationName,
      Request $request) {
        $this->findModelsAndSchemas($modelName, $associationName);
        $content = $this->getContentData($request);

        if ($this->modelResource) {
            $record = $this->modelResource->find($recordId);
            $recordAssociated = null;

            if ($content['id']) {
              $recordAssociated = $this->modelAssociation->find($content['id']);
            }

            if (method_exists($record->{$associationName}(), 'associate')) {
              // NOTICE: Update a belongsTo relationship
              if ($recordAssociated) {
                $record->{$associationName}()->associate($recordAssociated);
              } else {
                $record->{$associationName}()->dissociate();
              }
            } else {
              // NOTICE: Update a hasOne relationship
              $recordDissociated = $record->{$associationName}()->get()->first();

              if ($recordDissociated) {
                $foreignKey = $modelName.'_id';
                $recordDissociated->{$foreignKey} = null;
                $recordDissociated->save();
              }

              if ($recordAssociated) {
                $record->{$associationName}()->save($recordAssociated);
              }
            }

            $record->save();
        }
        return Response::make('Success', 204);
    }

    public function associate($modelName, $recordId, $associationName,
      Request $request) {
        $this->findModelsAndSchemas($modelName, $associationName);
        $content = $this->getContentData($request);

        if ($this->modelResource) {
            $record = $this->modelResource->find($recordId);
            $relation = SchemaUtils::getRelationship($record, $associationName);
            if ($relation instanceof HasMany) {
                $recordsAssociated = array();

                foreach($content as $value) {
                    $recordsAssociated[] = $this->modelAssociation
                                                ->find($value['id']);
                }

                $relation->saveMany($recordsAssociated);
            } else {
                // NOTICE: Set BelongsToMany association
                foreach($content as $value) {
                    $relation->attach($value['id']);
                }
            }
        }

        return Response::make('Success', 204);
    }

    public function dissociate($modelName, $recordId, $associationName,
      Request $request) {
        $this->findModelsAndSchemas($modelName, $associationName);
        $content = $this->getContentData($request);
        $withDeletion = $request->query('delete', 'false') === 'true';

        if ($this->modelResource) {
            $record = $this->modelResource->find($recordId);
            $relation = SchemaUtils::getRelationship($record, $associationName);

            $removeAssociation = !$withDeletion || $relation instanceof BelongsToMany;

            if ($removeAssociation) {
              if ($relation instanceof HasMany) {
                  $foreignKey = $relation->getForeignKey();
                  $foreignKey = explode('.', $foreignKey)[1];
                  foreach($content as $value) {
                      $recordDissociated = $this->modelAssociation->find($value['id']);
                      $recordDissociated->{$foreignKey} = null;
                      $recordDissociated->save();
                  }
              } else {
                  // NOTICE: Set BelongsToMany association
                  foreach($content as $value) {
                      $relation->detach($value['id']);
                  }
              }
            }

            if ($withDeletion) {
              foreach($content as $value) {
                  $recordDissociated = $this->modelAssociation->destroy($value['id']);
              }
            }
        }

        return Response::make('Success', 204);
    }

    protected function getContentData(Request $request) {
        $content = json_decode($request->getContent(), true);
        if (!array_key_exists('data', $content)) {
            throw new \Exception('Malformed content');
        }
        return $content['data'];
    }
}
