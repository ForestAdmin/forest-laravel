<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\ClassLoader\ClassMapGenerator;
use ForestAdmin\ForestLaravel\Logger;

class SchemaUtils {
    public static function fetchModels() {
        $models = [];
        $directory = base_path().'/'.Config::get('forest.models_path');

        if (file_exists($directory)) {
            foreach(ClassMapGenerator::createMap($directory) as $model => $path) {
                $models[] = $model;
            }
        } else {
            Logger::error('Cannot find the models directory: '.$directory);
        }
        return $models;
    }

    public static function findResource($modelNameToFind) {
        try {
            $modelClassNames = self::fetchModels();
            foreach ($modelClassNames as $modelClassName) {
                if (class_exists($modelClassName)) {
                    $model = App::make($modelClassName);
                    $modelName = $model->getTable();

                    if ($modelName == $modelNameToFind) {
                        return $model;
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

    public static function getRelationship($model, $method) {
      $reflection = new \ReflectionMethod($model, $method);

      if ($reflection->getNumberOfRequiredParameters() > 0) {
          // $params = [];
          // foreach ($reflection->getParameters() as $parameter) {
          //     $params[] = '';
          // }
          // return call_user_func_array(array($model, $method), $params);
          return null;
      } else {
          return $model->{$method}();
      }
    }
}
