<?php

namespace ForestAdmin\ForestLaravel\Model;

class Action {

    protected $collection;
    protected $name;
    protected $httpMethod;
    protected $endpoint;
    protected $redirect;
    protected $download;

    public function __construct($collection, $action) {
        $this->setCollection($collection);

        if (array_key_exists('name', $action)) {
            $this->setName($action['name']);
        }

        if (array_key_exists('httpMethod', $action)) {
            $this->setHttpMethod($action['httpMethod']);
        }

        if (array_key_exists('endpoint', $action)) {
            $this->setEndpoint($action['endpoint']);
        }

        if (array_key_exists('redirect', $action)) {
            $this->setRedirect($action['redirect']);
        }

        if (array_key_exists('download', $action)) {
            $this->setDownload($action['download']);
        }
    }

    public function setCollection($collection) {
        $this->collection = $collection;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setHttpMethod($httpMethod) {
        $this->httpMethod = $httpMethod;
    }

    public function setEndpoint($endpoint) {
        $this->endpoint = $endpoint;
    }

    public function setRedirect($redirect) {
        $this->redirect = $redirect;
    }

    public function setDownload($download) {
        $this->download = $download;
    }

    public function getCollection() {
        return $this->collection;
    }

    public function getName() {
        return $this->name;
    }

    public function getHttpMethod() {
        return $this->httpMethod;
    }

    public function getEndpoint() {
        return $this->endpoint;
    }

    public function getRedirect() {
        return $this->redirect;
    }

    public function getDownload() {
        return $this->download;
    }
}
