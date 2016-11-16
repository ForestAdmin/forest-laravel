<?php

namespace ForestAdmin\ForestLaravel\Serializer\JsonApi;

class Base {
    const STATUS_OK = response::STATUS_OK;
    const STATUS_CREATED = response::STATUS_CREATED;
    const STATUS_NO_CONTENT = response::STATUS_NO_CONTENT;
    const STATUS_NOT_MODIFIED = response::STATUS_NOT_MODIFIED;
    const STATUS_TEMPORARY_REDIRECT = response::STATUS_TEMPORARY_REDIRECT;
    const STATUS_PERMANENT_REDIRECT = response::STATUS_PERMANENT_REDIRECT;
    const STATUS_BAD_REQUEST = response::STATUS_BAD_REQUEST;
    const STATUS_UNAUTHORIZED = response::STATUS_UNAUTHORIZED;
    const STATUS_FORBIDDEN = response::STATUS_FORBIDDEN;
    const STATUS_NOT_FOUND = response::STATUS_NOT_FOUND;
    const STATUS_METHOD_NOT_ALLOWED = response::STATUS_METHOD_NOT_ALLOWED;
    const STATUS_UNPROCESSABLE_ENTITY = response::STATUS_UNPROCESSABLE_ENTITY;
    const STATUS_INTERNAL_SERVER_ERROR = response::STATUS_INTERNAL_SERVER_ERROR;
    const STATUS_SERVICE_UNAVAILABLE = response::STATUS_SERVICE_UNAVAILABLE;

    const CONTENT_TYPE_OFFICIAL = response::CONTENT_TYPE_OFFICIAL;
    const CONTENT_TYPE_DEBUG = response::CONTENT_TYPE_DEBUG;

    const ENCODE_DEFAULT = response::ENCODE_DEFAULT;
    const ENCODE_DEBUG = response::ENCODE_DEBUG;

    public static $debug = null;

    public function __construct() {
        if (is_null(self::$debug)) {
            self::$debug = (bool) ini_get('display_errors');
        }
    }

    protected static function convert_object_to_array($object) {
        if (is_object($object) == false) {
            throw new \Exception('can only convert objects');
        }

        if ($object instanceof \Resource) {
            return $object->get_array();
        }

        return get_object_vars($object);
    }
}
