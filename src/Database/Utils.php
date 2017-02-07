<?php

namespace ForestAdmin\ForestLaravel\Database;

use Illuminate\Support\Facades\Config;

class Utils {
    public static function isMySql() {
        return Config::get('database.default') === 'mysql';
    }

    public static function separator() {
      if (self::isMySql()) {
        return '`';
      } else {
        return '"';
      }
    }
}
