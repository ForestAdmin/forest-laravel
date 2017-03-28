<?php

namespace ForestAdmin\ForestLaravel\Schema;

use Neomerx\JsonApi\Schema\SchemaProvider;

class ActionSchema extends SchemaProvider {
    protected $resourceType = 'actions';

    public function getId($action) {
        return $action->getCollection()->getName().'.'.$action->getName();
    }

    public function getAttributes($action) {
        $attributes = array();
        $attributes['name'] = $action->getName();
        $attributes['endpoint'] = $action->getEndpoint();
        $attributes['http-method'] = $action->getHttpMethod();
        $attributes['redirect'] = $action->getRedirect();
        $attributes['download'] = $action->getDownload();
        $attributes['global'] = $action->getGlobal();

        return $attributes;
    }
}
