<?php

namespace ForestAdmin\ForestLaravel;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Logger {
    public static function info($message) {
        return Log::info(self::getTitle().$message);
    }

    public static function warning($message) {
        return Log::warning(self::getTitle().$message);
    }

    public static function error($message) {
        return Log::error(self::getTitle().$message);
    }

    private static function getTitle() {
        return "[".Carbon::now()."] Forest 🌳🌳🌳  ";
    }
}
