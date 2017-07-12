<?php

namespace ForestAdmin\ForestLaravel;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Logger {
    public static function debug($message) {
        if (Config::get('forest.debug_mode')) {
            Log::debug(self::getTitle().$message);
        }
    }

    public static function info($message) {
        Log::info(self::getTitle().$message);
    }

    public static function warning($message) {
        Log::warning(self::getTitle().$message);
    }

    public static function error($message) {
        Log::error(self::getTitle().$message);
    }

    private static function getTitle() {
        return "[".Carbon::now()."] Forest 🌳🌳🌳  ";
    }
}
