<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\ClassLoader\ClassMapGenerator;

class SchemaUtils {
    public static function fetchModels() {
        $models = [];
        $dir = base_path().'/'.Config::get('forest.models_path');

        if (file_exists($dir)) {
            foreach(ClassMapGenerator::createMap($dir) as $model => $path) {
                $models[] = $model;
            }
        }
        return $models;
    }

    public static function findResource($modelName) {
        try {
            $modelClassNames = self::fetchModels();
            foreach ($modelClassNames as $modelClassName) {
                if (class_exists($modelClassName)) {
                    $classNamePath = explode('\\', $modelClassName);
                    if (strtolower(end($classNamePath)) == $modelName) {
                        return App::make($modelClassName);
                    }
                }
            }

            return;
        } catch (\Exception $exception) {
            return;
        }
    }

    public static function findResourceName($model) {
        $className = get_class($model);
        $className = explode('\\', $className);
        return strtolower(end($className));
    }
}
