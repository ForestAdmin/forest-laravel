<?php

namespace ForestAdmin\ForestLaravel\Serializer;

use ForestAdmin\ForestLaravel\Http\Services\SchemaUtils;
use ForestAdmin\ForestLaravel\Serializer\JsonApi;

class ResourcesSerializer {

    protected static function serializeRecord($collectionModel,
      $collectionSchema, $type, $record, $excludeHasMany = false) {
        $resource = new JsonApi\Resource($type, $record->id);
        foreach ($collectionSchema->getFields() as $field) {
            $fieldName = $field->getField();
            if ($field->isTypeToOne()) {
                $recordAssociated = $record->{$fieldName};

                if ($recordAssociated) {
                    $typeAssociation = SchemaUtils::classNameToForestCollectionName(
                      get_class($collectionModel->{$fieldName}()->getRelated()));
                    $resourceAssociated = new JsonApi\Resource($typeAssociation,
                      $recordAssociated->id);
                    $resourceAssociated->add_link('related', '/forest/'.
                      $fieldName.'/'.$recordAssociated->id);

                    foreach ($recordAssociated->getAttributes() as $key => $value) {
                        if (strpos($key, '_id') === false) {
                            $resourceAssociated->add_data($key, $value);
                        }
                    };

                    $resource->add_relation($fieldName, $resourceAssociated,
                      true, array(
                        'links' => array(
                            'related' => '/forest/'.$fieldName.'/'.
                               $recordAssociated->id
                        ),
                        'data' => array(
                            'type' => $typeAssociation,
                            'id' => $recordAssociated->id
                        )
                    ));
                }
            } elseif ($field->isTypeToMany()) {
                if (!$excludeHasMany) {
                    $resourceAssociated = new JsonApi\Resource($fieldName);
                    $resource->add_relation($fieldName, $resourceAssociated, true,
                      array(
                        'links' => array(
                            'related' => '/forest/'.$type.'/'.$record->id.
                              '/relationships/'.$fieldName
                        )
                    ));
                }
            } else {
                if ($field->getType() == 'Date') {
                    if (gettype($record->{$fieldName}) === 'object') {
                        $resource->add_data($fieldName,
                          (string) $record->{$fieldName});
                    } else {
                        // NOTICE: Handles dates with DatePresenter
                        $resource->add_data($fieldName, $record->{$fieldName});
                    }
                } else {
                    $resource->add_data($fieldName, $record->{$fieldName});
                }
            }
        }

        return $resource;
    }

    public static function returnJsonRecord($collectionModel, $collectionSchema,
      $type, $record) {
        return self::serializeRecord($collectionModel, $collectionSchema, $type,
          $record)->get_json();
    }

    public static function returnJsonRecords($collectionModel,
      $collectionSchema, $type, $records, $countTotal) {
        $collection = array();

        foreach ($records as $record) {
            $resource = self::serializeRecord($collectionModel,
              $collectionSchema, $type, $record, true);
            $collection[] = $resource;
        }

        $jsonapi = new JsonApi\Collection($type);
        $jsonapi->fill_collection($collection);
        $jsonapi->add_meta('count', $countTotal);

        return $jsonapi->get_json();
    }
}
