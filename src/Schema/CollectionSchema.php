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
}
