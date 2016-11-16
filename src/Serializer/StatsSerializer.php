<?php

namespace ForestAdmin\ForestLaravel\Serializer;

use ForestAdmin\ForestLaravel\Serializer\JsonApi;

class StatsSerializer {
    public static function serialize($values) {
        $resource = new JsonApi\Resource('stats', uniqid());
        $resource->add_data('value', $values);
        return $resource->get_json();
    }
}
