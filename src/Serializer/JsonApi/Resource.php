<?php

namespace ForestAdmin\ForestLaravel\Serializer\JsonApi;

class Resource extends Response {
    protected $primary_type = null;
    protected $primary_id = null;
    protected $primary_attributes = array();
    protected $primary_relationships = array();
    protected $primary_links = array();
    protected $primary_meta_data = array();
    protected $included_data = array();

    public function __construct($type, $id=null) {
        parent::__construct();

        $this->primary_type = $type;
        $this->primary_id = $id;
    }

    public function get_array() {
        $response = array();

        if ($this->links) {
            $response['links'] = $this->links;
        }

        $response['data'] = array('type' => $this->primary_type);

        if ($this->primary_id) {
            $response['data']['id'] = $this->primary_id;
        }
        if ($this->primary_attributes) {
            $response['data']['attributes'] = $this->primary_attributes;
        }
        if ($this->primary_relationships) {
            $response['data']['relationships'] = $this->primary_relationships;
        }
        if ($this->primary_links) {
            $response['data']['links'] = $this->primary_links;
        }
        if ($this->primary_meta_data) {
            $response['data']['meta'] = $this->primary_meta_data;
        }

        if ($this->included_data) {
            $response['included'] = array_values($this->included_data);
        }

        if ($this->meta_data) {
            $response['meta'] = $this->meta_data;
        }

        return $response;
    }

    public function add_data($key, $value) {
        if (is_object($value)) {
            $value = parent::convert_object_to_array($value);
        }

        $this->primary_attributes[$key] = $value;
    }

    public function fill_data($values) {
        if (is_object($values)) {
            $values = parent::convert_object_to_array($values);
        }
        if (is_array($values) == false) {
            throw new \Exception('use add_data() for adding scalar values');
        }

        if (isset($values['id']) && $values['id'] == $this->primary_id) {
            unset($values['id']);
        }

        foreach ($values as $key => $single_value) {
            $this->add_data($key, $single_value);
        }
    }

    public function add_relation($key, $resource, $skip_include = false,
      $relation = null) {
        if ($resource instanceof resource) {
            $resource_array = $resource->get_array();

            if (!empty($resource_array['data']['attributes']) &&
              $skip_include == false) {
                $this->add_included_resource($resource);
            }

            if (!$relation) {
                $relation = array(
                    'links' => array(
                        'self'    => $this->links['self'].'/relationships/'.$key,
                        'related' => $this->links['self'].'/'.$key
                    ),
                    'data'  => array(
                        'type' => $resource_array['data']['type']
                    ),
                );
            }

            if (!empty($resource_array['data']['id'])) {
                $relation['data']['id'] = $resource_array['data']['id'];
            }
        }

        if (is_array($relation) == false) {
            throw new \Exception('unknown relation format');
        }

        $this->primary_relationships[$key] = $relation;
    }

    public function fill_relations($relations, $skip_include=false) {
        foreach ($relations as $key => $relation) {
            $this->add_relation($key, $relation, $skip_include);
        }
    }

    public function add_link($key, $link) {
        if (is_object($link)) {
            $link = parent::convert_object_to_array($link);
        }
        if (is_string($link) == false && is_array($link) == false) {
            throw new \Exception('link should be a string or an array');
        }

        $this->primary_links[$key] = $link;
    }

    public function fill_links($links) {
        foreach ($links as $key => $link) {
            $this->add_link($key, $link);
        }
    }

    public function set_self_link($link) {
        parent::set_self_link($link);

        $this->add_link($key='self', $link);
    }

    public function add_meta($key, $meta_data, $data_level=false) {
        if ($data_level == false) {
            return parent::add_meta($key, $meta_data);
        }

        if (is_object($meta_data)) {
            $meta_data = parent::convert_object_to_array($meta_data);
        }

        $this->primary_meta_data[$key] = $meta_data;
    }

    public function fill_meta($meta_data, $data_level=false) {
        foreach ($meta_data as $key => $single_meta_data) {
            $this->add_meta($key, $single_meta_data, $data_level);
        }
    }
}
