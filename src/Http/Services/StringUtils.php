<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

class StringUtils {
    public static function snakeCase($string) {
        return strtolower(preg_replace('/(?<!^)[A-Z]+/', '_$0', $string));
    }
}
