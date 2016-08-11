<?php

namespace ForestAdmin\ForestLaravel\Http\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\ClassLoader\ClassMapGenerator;
use ForestAdmin\Liana\Model\Collection as ForestCollection;
use ForestAdmin\Liana\Model\Field as ForestField;
use ForestAdmin\Liana\Model\Pivot as ForestPivot;


class PostMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forest:postmap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * @var FileSystem
     */
    protected $files;

    protected $properties = array();
    protected $methods = array();
    protected $write = false;
    protected $dirs = array();
    protected $collections = array();

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct()
    {
        parent::__construct();
//        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->dirs = array_merge(
            Config::get('forest.ModelLocations')
//            $this->option('dir')
        );

        $this->info('It works');
        $this->generateApiMap();
        $this->info('It ends');
    }


    /**
     * Retrieve collections from the models
     */
    protected function generateApiMap()
    {
        $hasDoctrine = interface_exists('Doctrine\DBAL\Driver');

        $models = $this->loadModels();

        foreach ($models as $name) {
            $this->properties = array();
            $this->methods = array();

            if (class_exists($name)) {
                try {
                    $reflectionClass = new \ReflectionClass($name);

                    if (!$reflectionClass->isSubclassOf('Illuminate\Database\Eloquent\Model')) {
                        continue;
                    } else {
                        $this->info('Model : '.$name);
                    }

                    if (!$reflectionClass->IsInstantiable()) {
                        continue;
                    }

                    $model = $this->laravel->make($name);

                    if ($hasDoctrine) {
                        $this->getPropertiesFromTable($model);
                    }

                    $this->getPropertiesFromMethods($model);

                    $this->collections[] = $this->generateCollection(
                        $name,
                        $reflectionClass->getName()
                    );

                    $this->info('test');

                } catch (\Exception $e) {
                    dd('I caught '.$e->getMessage());
                }
            }

        }

        dd($this->collections);

    }

    protected function generateCollection($name, $entityClassName) {

        $properties = [];
        foreach($this->properties as $fieldName => $property) {
            if ($property['comment'] == "") {
                // TODO: create object pivot with constructor only with Foreign key id (library_id)
                // TODO: create references with the references to where the foreign key point (book.id)
                $properties[] = new ForestField($fieldName, $property['type']);
            } else {
                foreach ($properties as $field) {
                    $foreign = explode('>', $property['comment']);
                    list($currentProperty, $reference) = $foreign;

                    if ($field->getField() == $currentProperty) {
                        $pivot = new ForestPivot($currentProperty);
                        $field->setPivot($pivot);
                        $field->setReference($reference);
                    }
                }
            }
        }

        $collection = new ForestCollection($name, $entityClassName, '', $properties);

        return $collection;
    }

    /**
     * @return array
     */
    public function loadModels() {
        $models = array();

        foreach($this->dirs as $dir) {
            $dir = base_path(). '/' . $dir;
            if (file_exists($dir)) {
                foreach(ClassMapGenerator::createMap($dir) as $model => $path) {
                    $models[] = $model;
                }
            }
        }

        return $models;
    }

    public function getPropertiesFromTable($model) {
        $table = $model->getConnection()->getTablePrefix() . $model->getTable();
        $schema = $model->getConnection()->getDoctrineSchemaManager($table);
        $databasePlatform = $schema->getDatabasePlatform();
        $databasePlatform->registerDoctrineTypeMapping('enum', 'string');

        $platformName = $databasePlatform->getName();
        $customTypes = $this->laravel['config']->get("ide-helper.custom_db_types.{$platformName}", array());

        foreach ($customTypes as $yourTypeName => $doctrineTypeName) {
            $databasePlatform->registerDoctrineTypeMapping($yourTypeName, $doctrineTypeName);
        }

        $database = null;

        if (strpos($table, '.')) {
            list($database, $table) = explode('.', $table);
        }

        $columns = $schema->listTableColumns($table, $database);

        if ($columns) {
            foreach ($columns as $column) {
                $name = $column->getName();

                if (in_array($name, $model->getDates())) {
                    $type = '\Carbon\Carbon';
                } else {
                    $type = $column->getType()->getName();
                    switch ($type) {
                        case 'string':
                        case 'text':
                        case 'date':
                        case 'time':
                        case 'guid':
                        case 'datetimetz':
                        case 'datetime':
                            $type = 'string';
                            break;
                        case 'integer':
                        case 'bigint':
                        case 'smallint':
                            $type = 'integer';
                            break;
                        case 'decimal':
                        case 'float':
                            $type = 'float';
                            break;
                        case 'boolean':
                            $type = 'boolean';
                            break;
                        default:
                            $type = 'mixed';
                            break;
                    }
                }
                $comment = $column->getComment();
                $this->setProperty($name, $type, true, true, $comment);
                $this->setMethod(
                    Str::camel("where_" . $name),
                    '\Illuminate\Database\Query\Builder|\\' . get_class($model),
                    array('$value')
                );
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    protected function getPropertiesFromMethods($model) {
        $methods = get_class_methods($model);
        if ($methods) {
            foreach ($methods as $method) {

                if (Str::startsWith($method, 'get') && Str::endsWith(
                        $method,
                        'Attribute'
                    ) && $method !== 'getAttribute'
                ) {
                    //Magic get<name>Attribute
                    $name = Str::snake(substr($method, 3, -9));
                    if (!empty($name)) {
                        $this->setProperty($name, null, true, null);
                    }
                } elseif (Str::startsWith($method, 'set') && Str::endsWith(
                        $method,
                        'Attribute'
                    ) && $method !== 'setAttribute'
                ) {
                    //Magic set<name>Attribute
                    $name = Str::snake(substr($method, 3, -9));
                    if (!empty($name)) {
                        $this->setProperty($name, null, null, true);
                    }
                } elseif (Str::startsWith($method, 'scope') && $method !== 'scopeQuery') {
                    //Magic set<name>Attribute
                    $name = Str::camel(substr($method, 5));
                    if (!empty($name)) {
                        $reflection = new \ReflectionMethod($model, $method);
                        $args = $this->getParameters($reflection);
                        //Remove the first ($query) argument
                        array_shift($args);
                        $this->setMethod($name, '\Illuminate\Database\Query\Builder|\\' . $reflection->class, $args);
                    }
                } elseif (!method_exists('Illuminate\Database\Eloquent\Model', $method)
                    && !Str::startsWith($method, 'get')
                ) {
                    //Use reflection to inspect the code, based on Illuminate/Support/SerializableClosure.php
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
                    foreach (array(
                                 'hasMany',
                                 'hasManyThrough',
                                 'belongsToMany',
                                 'hasOne',
                                 'belongsTo',
                                 'morphOne',
                                 'morphTo',
                                 'morphMany',
                                 'morphToMany'
                             ) as $relation) {
                        $search = '$this->' . $relation . '(';
                        if ($pos = stripos($code, $search)) {
                            $this->info('This is '.$relation);
                            //Resolve the relation's model to a Relation object.
                            $relationObj = $model->$method();
                            if ($relationObj instanceof Relation) {
                                $this->info('It is an instance of Relation');
                                $relatedModel = '\\' . get_class($relationObj->getRelated());
                                $relations = ['hasManyThrough', 'belongsToMany', 'hasMany', 'morphMany', 'morphToMany'];
                                if (in_array($relation, $relations)) {
                                    $this->info('-------------> First relation');
                                    //Collection or array of models (because Collection is Arrayable)
                                    // TODO : in the case of a hasMany there's no foreign key
//                                    $this->setProperty(
//                                        $method,
//                                        $this->getCollectionClass($relatedModel) . '|' . $relatedModel . '[]',
//                                        true,
//                                        null,
//                                        $relationObj->getForeignKey().'>'.$method.'.'.$relationObj->getOtherKey()
//                                    );
                                } elseif ($relation === "morphTo") {
                                    $this->info('-------------> second relation');
                                    // Model isn't specified because relation is polymorphic
                                    $this->setProperty(
                                        $method,
                                        '\Illuminate\Database\Eloquent\Model|\Eloquent',
                                        true,
                                        null,
                                        $relationObj->getForeignKey().'>'.$method.'.'.$relationObj->getOtherKey()
                                    );
                                } else {
                                    $this->info('-------------> Third relation');
                                    //Single model is returned
                                    $this->setProperty(
                                        $method,
                                        $relatedModel,
                                        true,
                                        null,
                                        $relationObj->getForeignKey().'>'.$method.'.'.$relationObj->getOtherKey()
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $name
     * @param string|null $type
     * @param bool|null $read
     * @param bool|null $write
     * @param string|null $comment
     */
    protected function setProperty($name, $type = null, $read = null, $write = null, $comment = '') {
        if (!isset($this->properties[$name])) {
            $this->properties[$name] = array();
            $this->properties[$name]['type'] = 'mixed';
            $this->properties[$name]['read'] = false;
            $this->properties[$name]['write'] = false;
            $this->properties[$name]['comment'] = (string) $comment;
        }
        if ($type !== null) {
            $this->properties[$name]['type'] = $type;
        }
        if ($read !== null) {
            $this->properties[$name]['read'] = $read;
        }
        if ($write !== null) {
            $this->properties[$name]['write'] = $write;
        }
    }

    protected function setMethod($name, $type = '', $arguments = array()) {
        $methods = array_change_key_case($this->methods, CASE_LOWER);
        if (!isset($methods[strtolower($name)])) {
            $this->methods[$name] = array();
            $this->methods[$name]['type'] = $type;
            $this->methods[$name]['arguments'] = $arguments;
        }
    }

    /**
     * Determine a model classes' collection type.
     *
     * @see http://laravel.com/docs/eloquent-collections#custom-collections
     * @param string $className
     * @return string
     */
    private function getCollectionClass($className)
    {
        // Return something in the very very unlikely scenario the model doesn't
        // have a newCollection() method.
        if (!method_exists($className, 'newCollection')) {
            return '\Illuminate\Database\Eloquent\Collection';
        }
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $className;
        return '\\' . get_class($model->newCollection());
    }

}
