<?php

namespace ForestAdmin\ForestLaravel\Schema;

use Neomerx\JsonApi\Schema\SchemaProvider;

class CollectionSchema extends SchemaProvider {
    protected $resourceType = 'collections';

    public function getId($collection) {
        return $collection->getName();
    }

    public function getAttributes($collection) {
        $attributes = array();

        $attributes['name'] = $collection->getName();
        // TODO: Remove nameOld attribute once the lianas versions older than 0.1.4 are minority.
        $attributes['name-old'] = $collection->getNameOld();
        $attributes['fields'] = $collection->getFields();

        if($collection->getActions()) {
            $attributes['actions'] = $collection->getActions();
        }

        $attributes['only-for-relationships'] = false;
        $attributes['is-virtual'] = false;
        $attributes['is-read-only'] = false;
        $attributes['is-searchable'] = true;

        return $attributes;
    }

    public function getRelationships($collection, $isPrimary,
      array $includeRelationships) {

        return [
            'actions' => [
                'data' => $collection->getActions()
            ]
        ];
    }
}
