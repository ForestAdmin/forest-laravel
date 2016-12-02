<?php

namespace ForestAdmin\ForestLaravel;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\ClassLoader\ClassMapGenerator;
use ForestAdmin\ForestLaravel\Serializer\ApimapSerializer;
use ForestAdmin\ForestLaravel\Model\Collection;
use ForestAdmin\ForestLaravel\Model\Field;
use ForestAdmin\ForestLaravel\Http\Services\SchemaUtils;
use ForestAdmin\ForestLaravel\Http\Services\StringUtils;

class Bootstraper {
    protected $models = [];
    protected $collections = [];

    public function getCollections() {
        // QUESTION: Doctrine is installed in the package, so why do we check
        //           the Doctrine presence?
        $hasDoctrine = interface_exists('Doctrine\DBAL\Driver');

        $this->models = SchemaUtils::fetchModels();

        foreach ($this->models as $name) {
            if (class_exists($name)) {
                try {
                    $reflectionClass = new \ReflectionClass($name);

                    $isModel = $reflectionClass->isSubclassOf(
                                'Illuminate\Database\Eloquent\Model');
                    $isInstantiable = $reflectionClass->IsInstantiable();

                    if ($isModel && $isInstantiable) {
                        // Instantiate the model
                        $model = App::make($name);
                        $className = explode('\\', $name);
                        $primaryKey = [$model->getKeyName()];
                        $fields = [];

                        if ($hasDoctrine) {
                            $fields = $this->getFieldsFromTable($model);
                        }

                        $fields = $this->updateFieldsFromMethods($model, $fields,
                          strtolower(end($className)));

                        $collection = new Collection(
                            strtolower(end($className)),
                            $reflectionClass->getName(),
                            $primaryKey,
                            $fields
                        );

                        $this->collections[] = $collection;
                    }
                } catch (\Exception $e) {
                    var_dump($e->getFile().$e->getLine().': '.$e->getMessage());
                }
            }
        }

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

    protected function updateFieldsFromMethods($model, $fields, $modelName) {
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
                        $relationObj = $model->{$method}();
                        if ($relationObj instanceof Relation) {
                            $relatedModel = '\\'.get_class($relationObj
                              ->getRelated());
                            $entityClassNameExploded = explode('\\',
                              get_class($relationObj->getRelated()));
                            $nameClass = strtolower(
                              end($entityClassNameExploded));

                            if (in_array($relation, ['belongsToMany',
                              'hasMany'])) {
                                $fields[] = new Field($method, ["Number"],
                                  $nameClass.".id");
                            } elseif ($relation == 'hasOne') {
                                $fields[] = new Field($method, "Number",
                                  $nameClass.".id", $modelName);
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
        $response = $client->request(
            'POST',
            Config::get('forest.url').'/forest/apimaps',
            $options
        );

        return ($response->getStatusCode() == 204);
    }

    protected function createApimap() {
        $serializer = new ApimapSerializer($this->getCollections(),
                        $this->getApimapMeta());
        return $serializer->serialize();
    }

    protected function getApimapMeta() {
        // NOTICE: Retrieve version of the package from the composer file
        $pathFile = getcwd().'/vendor/forestadmin/forest-laravel/composer.json';
        $composerFileContent = file_get_contents($pathFile);
        $composerFileContent = json_decode($composerFileContent);
        return [
            'liana' => 'forest-laravel',
            'liana_version' => $composerFileContent->version
        ];
    }
}
