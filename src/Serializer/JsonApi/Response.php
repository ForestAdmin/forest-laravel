<?php

namespace ForestAdmin\ForestLaravel\Serializer\JsonApi;

class Response extends Base {
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const STATUS_NO_CONTENT = 204;
    const STATUS_NOT_MODIFIED = 304;
    const STATUS_TEMPORARY_REDIRECT = 307;
    const STATUS_PERMANENT_REDIRECT = 308;
    const STATUS_BAD_REQUEST = 400;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOT_FOUND = 404;
    const STATUS_METHOD_NOT_ALLOWED = 405;
    const STATUS_UNPROCESSABLE_ENTITY = 422;
    const STATUS_INTERNAL_SERVER_ERROR = 500;
    const STATUS_SERVICE_UNAVAILABLE = 503;

    const CONTENT_TYPE_OFFICIAL = 'application/vnd.api+json';
    const CONTENT_TYPE_DEBUG = 'application/json';
    const CONTENT_TYPE_JSONP = 'application/javascript';

    const JSONP_CALLBACK_DEFAULT = "JSONP_CALLBACK";

    const ENCODE_DEFAULT = 320;
    const ENCODE_DEBUG = 448;

    public static $send_status_headers = true;

    protected $links = array();
    protected $meta_data = array();
    protected $included_resources = array();
    protected $http_status = self::STATUS_OK;
    protected $redirect_location = null;

    public function __construct() {
        parent::__construct();

        $self_link = $_SERVER['REQUEST_URI'];
        if (isset($_SERVER['PATH_INFO'])) {
            $self_link = $_SERVER['PATH_INFO'];
        }

        $this->set_self_link($self_link);
    }

    public function __toString() {
        return $this->get_json();
    }

    public function get_json($encode_options = null) {
        if (is_int($encode_options) == false) {
            $encode_options = self::ENCODE_DEFAULT;
        }
        if (base::$debug || strpos($_SERVER['HTTP_ACCEPT'], '/json') == false) {
            $encode_options = self::ENCODE_DEBUG;
        }

        $response = $this->get_array();

        $json = json_encode($response, $encode_options);

        return $json;
    }

    public function send_response($content_type = null, $encode_options = null, $response = null, $jsonp_callback = null) {
        if (is_null($response) && $this->http_status != self::STATUS_NO_CONTENT) {
            $response = $this->get_json($encode_options);
        }

        if (empty($content_type)) {
            $content_type = self::CONTENT_TYPE_OFFICIAL;
        }
        if (base::$debug || strpos($_SERVER['HTTP_ACCEPT'], '/json') == false) {
            $content_type = self::CONTENT_TYPE_DEBUG;
        }

        if (self::$send_status_headers) {
            $this->send_status_headers();
        }

        header('Content-Type: '.$content_type.'; charset=utf-8');

        if ($this->http_status == self::STATUS_NO_CONTENT) {
            return;
        }

        if ($content_type == self::CONTENT_TYPE_JSONP) {
            if (empty($jsonp_callback)) {
                $jsonp_callback = self::JSONP_CALLBACK_DEFAULT;
            }
            echo $jsonp_callback.'('.$response.')';
            return;
        }

        echo $response;
    }

    private function send_status_headers() {
        if ($this->redirect_location) {
            if ($this->http_status == self::STATUS_OK) {
            $this->set_http_status(self::STATUS_TEMPORARY_REDIRECT);
            }

            header('Location: '.$this->redirect_location, $replace=true, $this->http_status);
            return;
        }

        http_response_code($this->http_status);
    }

    public function set_http_status($http_status) {
        $this->http_status = $http_status;
    }

    public function set_redirect_location($location) {
        if (self::$send_status_headers == false && base::$debug) {
            trigger_error('location will not be send out unless response::$send_status_headers is true', E_USER_NOTICE);
        }

        $this->redirect_location = $location;
    }

    public function get_included_resources() {
        return $this->included_resources;
    }

    public function set_self_link($link) {
        $this->links['self'] = $link;
    }

    public function add_included_resource(\Resource $resource) {
        if (property_exists($this, 'included_resources') == false) {
            throw new \Exception(get_class($this).' can not contain included resources');
        }

        $resource_array = $resource->get_array();
        if (empty($resource_array['data']['id'])) {
            return;
        }

        $resource_array = $resource_array['data'];
        unset($resource_array['relationships'], $resource_array['meta']);

        $key = $resource_array['type'].'/'.$resource_array['id'];

        $this->included_data[$key] = $resource_array;

        $this->included_resources[$key] = $resource;
    }

    public function fill_included_resources($resources) {
        foreach ($resources as $resource) {
            $this->add_included_resource($resource);
        }
    }

    public function add_meta($key, $meta_data) {
        if (is_object($meta_data)) {
            $meta_data = parent::convert_object_to_array($meta_data);
        }

        $this->meta_data[$key] = $meta_data;
    }

    public function fill_meta($meta_data) {
        foreach ($meta_data as $key => $single_meta_data) {
            $this->add_meta($key, $single_meta_data);
        }
    }
}
