<?php

namespace ForestAdmin\ForestLaravel\Http\Utils;

use GuzzleHttp;
use Illuminate\Support\Facades\Config;

class Client {

    protected $client;

    public function __construct() {
        $this->client = new GuzzleHttp\Client();
    }

    public function request($method, $path, $options) {
        if (method_exists($this->client, 'request')) {
          // NOTICE: Guzzle 6.* support
          return $this->client->request(
              $method,
              Config::get('forest.url').'/forest'.$path,
              $options
          );
        } else {
          // NOTICE: Guzzle 5.* support
          return $this->client->{strtolower($method)}(
              Config::get('forest.url').'/forest'.$path,
              $options
          );
        }
    }
}
