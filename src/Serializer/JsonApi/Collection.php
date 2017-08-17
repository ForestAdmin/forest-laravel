<?php

namespace ForestAdmin\ForestLaravel\Serializer\JsonApi;

class Collection extends Response {
    protected $primary_type = null;
    protected $primary_collection = array();
    protected $included_data = array();

    public function __construct($type = null) {
        parent::__construct();
        $this->primary_type = $type;
    }

    public function get_array() {
          $response = array();

          if ($this->links) {
              $response['links'] = $this->links;
          }

          $response['data'] = $this->primary_collection;

          if ($this->included_data) {
              $response['included'] = array_values($this->included_data);
          }

          if ($this->meta_data) {
              $response['meta'] = $this->meta_data;
          }

          return $response;
    }

    public function add_resource(resource $resource) {
        $resource_array = $resource->get_array();

        $included_resources = $resource->get_included_resources();
        if (!empty($included_resources)) {
            $this->fill_included_resources($included_resources);
        }

        $this->primary_collection[] = $resource_array['data'];
    }

    public function fill_collection($resources) {
          foreach ($resources as $resource) {
              $this->add_resource($resource);
          }
    }
}
