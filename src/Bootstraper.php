<?php

namespace ForestAdmin\ForestLaravel;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use ForestAdmin\ForestLaravel\Logger;
use ForestAdmin\ForestLaravel\Serializer\ApimapSerializer;
use ForestAdmin\ForestLaravel\Model\Collection;
use ForestAdmin\ForestLaravel\Model\Field;
use ForestAdmin\ForestLaravel\Http\Services\SchemaUtils;
use ForestAdmin\ForestLaravel\Http\Services\StringUtils;
use ForestAdmin\ForestLaravel\Http\Utils\Client;

class Bootstraper {
    protected $models = [];
    protected $collections = [];

    public function getCollections() {
        // QUESTION: Doctrine is installed in the package, so why do we check
        //           the Doctrine presence?
        $hasDoctrine = interface_exists('Doctrine\DBAL\Driver');
        $this->models = SchemaUtils::fetchModels();

        Logger::debug('[Apimap] => '.sizeof($this->models).
          ' potential models detected.');

        foreach ($this->models as $name) {
            Logger::debug('[Apimap]   => start '.$name.' class inspection.');
            if (class_exists($name)) {
                try {
                    $reflectionClass = new \ReflectionClass($name);
                    $isModel = $reflectionClass->isSubclassOf(
                                'Illuminate\Database\Eloquent\Model');
                    $isInstantiable = $reflectionClass->IsInstantiable();

                    if ($isModel && $isInstantiable) {
                        Logger::debug('[Apimap]     => '.$name.
                          ' model detected.');

                        // NOTICE: Instantiate the model
                        $model = App::make($name);
                        $primaryKey = [$model->getKeyName()];
                        $fields = [];

                        if ($hasDoctrine) {
                            $fields = $this->getFieldsFromTable($model);
                        }

                        $fields = $this->updateFieldsFromMethods($model,
                          $fields);

                        $collection = new Collection(
                            $model->getTable(),
                            $reflectionClass->getName(),
                            $primaryKey,
                            $fields
                        );
                        Logger::debug('[Apimap]     => Collection '.
                          $model->getTable().' created.');

                        $this->collections[] = $collection;
                    }
                } catch (\Exception $exception) {
                    var_dump($exception->getFile().$exception->getLine().
                      ': '.$exception->getMessage());
                }
            }
        }

        Logger::debug('[Apimap] => '.sizeof($this->collections).
          ' collections created in the apimap.');
        return $this->collections;
    }

    protected function getFieldsFromTable($model) {
        $fields = [];
        $table = $model->getConnection()->getTablePrefix().$model->getTable();
        $schema = $model->getConnection()->getDoctrineSchemaManager($table);
        $databasePlatform = $schema->getDatabasePlatform();
        $databasePlatform->registerDoctrineTypeMapping('enum', 'string');
        $database = null;

        if (strpos($table, '.')) {
            list($database, $table) = explode('.', $table);
        }

        $columns = $schema->listTableColumns($table, $database);
        if ($columns) {
            foreach ($columns as $column) {
                $fields[] = new Field(
                    $column->getName(),
                    $column->getType()->getName()
                );
            }
        }

        return $fields;
    }

    protected function updateFieldsFromMethods($model, $fields) {
        $methods = get_class_methods($model);
        if ($methods) {
            foreach ($methods as $method) {
                if (!method_exists('Illuminate\Database\Eloquent\Model', $method)
                    && !Str::startsWith($method, 'get')) {

                    $reflection = new \ReflectionMethod($model, $method);
                    $file = new \SplFileObject($reflection->getFileName());
                    $file->seek($reflection->getStartLine() - 1);
                    $code = '';

                    while ($file->key() < $reflection->getEndLine()) {
                        $code .= $file->current();
                        $file->next();
                    }
                    $code = trim(preg_replace('/\s\s+/', '', $code));
                    $begin = strpos($code, 'function(');
                    $code = substr($code, $begin, strrpos($code, '}') - $begin + 1);

                    preg_match('/\$this->(\w+)\(/', $code, $matches);

                    // TODO: Support morphOne, morphTo, morphMany, morphToMany
                    if ($matches && $matches[1] && in_array($matches[1], [
                      'hasMany', 'hasManyThrough', 'belongsToMany', 'hasOne',
                      'belongsTo', 'morphOne', 'morphTo', 'morphMany',
                      'morphToMany'])) {
                        $relation = $matches[1];
                        $relationObj = SchemaUtils::getRelationship($model, $method);
                        if ($relationObj instanceof Relation) {
                            $nameClass = $relationObj->getRelated()->getTable();

                            if (in_array($relation, ['belongsToMany',
                              'hasMany'])) {
                                $fields[] = new Field($method, ["Number"],
                                  $nameClass.".id");
                            } elseif ($relation == 'hasOne') {
                                $fields[] = new Field($method, "Number",
                                  $nameClass.".id", $model->getTable());
                            } elseif ($relation == 'belongsTo') {
                                $fields[] = new Field($method, "Number",
                                  $nameClass.".id");

                                // NOTICE: Remove dedicated belongTo id field
                                foreach($fields as $id => $field) {
                                    $foreignKey = $relationObj->getForeignKey();
                                    if ($field->getField() == $foreignKey) {
                                        array_splice($fields, $id, 1);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $fields;
    }

    public function sendApimap() {
        $apimap = $this->createApimap();
        Logger::debug('[Apimap] => Generated Apimap: '.$apimap);

        // NOTICE: Removed PHP_EOL at the end of the files
        $apimap = str_replace(PHP_EOL, '', $apimap);

        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'forest-secret-key' => Config::get('forest.secret_key')
            ],
            'body' => $apimap
        ];

        $client = new Client();
        $response = $client->request('POST', '/apimaps', $options);

        $body = json_decode($response->getBody(), true);
        $message = $body['warning'];

        if ($message) {
          // TODO: Create a dedicated logger to improve the logging experience.
          Logger::warning($message);
        }

        $httpStatusCodesValid = array(200, 202, 204);
        return (in_array($response->getStatusCode(), $httpStatusCodesValid));
    }

    protected function createApimap() {
        $serializer = new ApimapSerializer($this->getCollections(),
          $this->getApimapMeta());
        return $serializer->serialize();
    }

    protected function getApimapMeta() {
        // NOTICE: Retrieve version of the package from the composer file
        $pathFile = join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'composer.json']);
        $composerFileContent = file_get_contents($pathFile);
        $composerFileContent = json_decode($composerFileContent);
        return [
            'liana' => 'forest-laravel',
            'liana_version' => $composerFileContent->version
        ];
    }
}
