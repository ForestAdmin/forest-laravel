<?php

namespace ForestAdmin\ForestLaravel;

use ForestAdmin\Liana\Api\Map;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\ClassLoader\ClassMapGenerator;
use ForestAdmin\Liana\Model\Collection as ForestCollection;
use ForestAdmin\Liana\Model\Field as ForestField;
use ForestAdmin\Liana\Model\Pivot as ForestPivot;
use Symfony\Component\VarDumper\Cloner\Data;

class DatabaseStructure {
    /**
     * Array containing the properties of a models
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Array containing the methods of a models
     *
     * @var array
     */
    protected $methods = [];

    /**
     * Array containing the directories where to search for models
     *
     * @var array
     */
    protected $dirs = [];

    /**
     * Array containing the collections of models that make the structure of the database
     *
     * @var array
     */
    protected $collections = [];

    /**
     * @var null
     */
    private $commandPointer = null;

    /**
     * Send info message either it's called from the console or an instance in the website
     * @param $message
     */
    protected function sendInfo($message) {
        if ($this->commandPointer && method_exists($this->commandPointer, 'info')) {
            $this->commandPointer->info($message);
        } else {
            echo $message.'<br />';
        }
    }

    /**
     * DatabaseStructure constructor.
     * @param null $dirs
     */
    public function __construct($dirs, $commandPointer = null) {
        if ($dirs) {
            $this->dirs = $dirs;
        } else {
            $this->dirs = Config::get('forest.ModelLocations');
        }
        $this->commandPointer = $commandPointer;
    }

    /**
     * Static method to retrieve an array of ForestCollections
     * if the collection has already been cached we return it, otherwise we instanciate this object to create new version
     * @return mixed
     */
    public static function getCollections() {
        return unserialize(Cache::rememberForever('forestCollections', function() {
            $object = new DatabaseStructure(Config::get('forest.ModelLocations'), null);
            $collections = $object->generateCollections();
            $serialized = serialize($collections);

            if (!self::postApimap()) {
                throw Exception('Apimap was not send correctly to the ForestAdmin server');
            }

            return $serialized;
        }));
    }


    /**
     * Method to set the array of ForestCollection to the cache
     * After the new map of the database is made we generate the apimap and post it to ForestAdmin's server
     * @param $collections
     * @throws Exception
     */
    public function setCollections($collections)
    {
        // Cache array of ForestCollection
        Cache::forever('forestCollections', serialize($collections));
        // Generate and send the apimap
        if (!DatabaseStructure::postApimap()) {
            throw Exception('Apimap was not send correctly to the ForestAdmin server');
        }
    }

    /**
     * Generate collections from the models
     * @return array
     */
    public function generateCollections()
    {
        // check if the DBAL driver instance exist
        $hasDoctrine = interface_exists('Doctrine\DBAL\Driver');

        $models = $this->loadModels();

        // for each model
        foreach ($models as $name) {
            // rest of the two arrays that would contain data from the last model
            $this->properties = [];
            $this->methods = [];

            if (class_exists($name)) {
                try {
                    // Instanciate reflection on the model
                    $reflectionClass = new \ReflectionClass($name);

                    // If not an extension of the class model
                    if (!$reflectionClass->isSubclassOf('Illuminate\Database\Eloquent\Model')) {
                        continue;
                    } else {
                        $this->sendInfo('Model [ '.$name.']');
                    }

                    // If not an abstract class
                    if (!$reflectionClass->IsInstantiable()) {
                        continue;
                    }

                    // Instantiate the model
                    $model = App::make($name);

                    // If we have the doctrine driver we can retrieve properties dat
                    if ($hasDoctrine) {
                        $this->getPropertiesFromTable($model);
                    }

                    // We retreive the methos to find the relations, foreign key
                    $this->getPropertiesFromMethods($model);
                    
                    $className = explode('\\', $name);

                    // Generate the collection for this model
                    $collection = $this->generateCollection(
                        strtolower(end($className)),
                        $reflectionClass->getName(),
                        $model
                    );
                    $this->collections[] = $collection;

                } catch (\Exception $e) {
                    var_dump($e->getFile().$e->getLine().': '.$e->getMessage());
                }
            }
        }

        return $this->collections;
    }


    /**
     * Generate a collection from an eloquent model
     * @param $name
     * @param $entityClassName
     * @param $model
     * @return ForestCollection
     */
    protected function generateCollection($name, $entityClassName, $model) {

        $properties = [];

        // For each properties
        foreach($this->properties as $fieldName => $property) {
            // Check if it's really a property
            if ($property['comment'] == "") {
                // Instantiation of a field for the property
                $properties[] = new ForestField($fieldName, $property['type']);
                // Else it's a relation, foreign key
            } else {
                // Go through the existing properties
                // (since the properties are first in the array and relation after)
                $foreign = explode('>', $property['comment']);
                // retrieve the foreign_key name and where it points to
                list($currentProperty, $table, $reference, $relation, $className) = $foreign;

                if ($relation == 'hasOne') {
                    // If it's a hasOne relation then we create a new ForestField
                    $field = new ForestField($fieldName, ['String']);
                    $pivot = new ForestPivot($currentProperty, $table, $className); // FieldName is the name of the method
                    $field->setPivot($pivot);
                    $field->setReference($currentProperty);
                    $properties[] = $field;
                } elseif($relation == 'hasMany' || $relation == 'belongsToMany') {
                    // If it's a hasMany relation then we create a new ForestField
                    $field = new ForestField($fieldName, ['String']);
                    $pivot = new ForestPivot($currentProperty, $table, $className);
                    $field->setPivot($pivot);
                    $field->setReference($currentProperty);
                    $properties[] = $field;
                } else {
                    foreach ($properties as $field) {
                        $currentProperty = explode('.', $currentProperty);
                        $currentProperty = end($currentProperty);

                        // If this field is the foreign key we attach the data
                        if ($field->getField() == $currentProperty) {
                            $pivot = new ForestPivot($currentProperty, $table, $className);
                            $field->setPivot($pivot);
                            $field->setReference($reference);
                        }
                    }
                }

            }
        }

        // Instatiation of a Collection for the model
        $primaryKey = [$model->getKeyName()];
        $collection = new ForestCollection($name, $entityClassName, $primaryKey, $properties);

        return $collection;
    }

    /**
     * Load an array of the models from the directories
     *
     * @return array
     */
    public function loadModels() {
        $models = [];

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

    /**
     * Extract the properties from a model
     *
     * @param $model
     */
    protected function getPropertiesFromTable($model) {
        $table = $model->getConnection()->getTablePrefix() . $model->getTable();
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
                $name = $column->getName();

                $type = $column->getType()->getName();
                switch ($type) {
                    case 'datetimetz':
                    case 'date':
                    case 'time':
                    case 'datetime':
                        $type = 'Date';
                        break;
                    case 'integer':
                    case 'bigint':
                    case 'smallint':
                    case 'decimal':
                    case 'float':
                        $type = 'Number';
                        break;
                    case 'boolean':
                        $type = 'Boolean';
                        break;
                    case 'guid':
                    case 'string':
                    case 'text':
                    default:
                        $type = 'String';
                        break;
                }

                $this->setProperty($name, $type, '');
            }
        }
    }

    /**
     * Retrieve the methods from a model
     *
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
                        $this->setProperty($name, null, '');
                    }
                } elseif (Str::startsWith($method, 'set') && Str::endsWith(
                        $method,
                        'Attribute'
                    ) && $method !== 'setAttribute'
                ) {
                    //Magic set<name>Attribute
                    $name = Str::snake(substr($method, 3, -9));
                    if (!empty($name)) {
                        $this->setProperty($name, null, '');
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
                            $this->sendInfo('has relation : '.$relation);
                            //Resolve the relation's model to a Relation object.
                            $relationObj = $model->$method();

                            if ($relationObj instanceof Relation) {
                                $relatedModel = '\\' . get_class($relationObj->getRelated());
                                $entityClassNameExploded = explode('\\', get_class($relationObj->getRelated()));
                                $nameClass = strtolower(end($entityClassNameExploded));

                                $tableAndForeignKey = $relationObj->getForeignKey();
                                $foreignKey = explode('.', $tableAndForeignKey);
                                $foreignKey = end($foreignKey);

                                $relations = ['hasManyThrough', 'belongsToMany', 'hasMany', 'morphMany', 'morphToMany'];
                                if ($relation === "hasMany" || $relation == "belongsToMany") {
                                    $this->sendInfo('Case 0');

                                    $this->setProperty(
                                        $method,
                                        $this->getCollectionClass($relatedModel) . '|' . $relatedModel . '[]',
                                        $nameClass.'.id>'.$relationObj->getModel()->getTable().'>'.$method.'>'.$relation.'>'.$nameClass
                                    );
                                } elseif (in_array($relation, $relations)) {
                                    $this->sendInfo('Case 1');
                                    //Collection or array of models (because Collection is Arrayable)
                                    // in the case of a hasMany there's no foreign key
                                     /*$this->setProperty(
                                         $method,
                                         $this->getCollectionClass($relatedModel) . '|' . $relatedModel . '[]',
//                                         $relationObj->getForeignKey().'>'.$method.'.'.$relationObj->getOtherKey()
                                         $relationObj->getForeignKey().'>'.$relationObj->getModel()->getTable().'>'.$method.'>'.$relation
                                     );*/
                                } elseif ($relation === "morphTo") {
                                    $this->sendInfo('Case 2');
                                    // Model isn't specified because relation is polymorphic
//                                    $this->setProperty(
//                                        $method,
//                                        '\Illuminate\Database\Eloquent\Model|\Eloquent',
//                                        $relationObj->getForeignKey().'>'.$relationObj->getModel()->getTable().'>'.$method.'>'.$relation
//                                    );
                                } else {
                                    $this->sendInfo('Case 3');
                                    //Single model is returned
                                    $comment = '';

                                    if ($relation === "hasOne") {
                                        $comment = $nameClass.'.'.$foreignKey.'>'.$relationObj->getModel()->getTable().'>'.$method.'>'.$relation.'>'.$nameClass;
                                    } else {
                                        $comment = $nameClass.'.'.$foreignKey.'>'.$relationObj->getModel()->getTable().'>'.$method.'.'.$relationObj->getOtherKey().'>'.$relation.'>'.$nameClass;
                                    }

                                    $this->setProperty(
                                        $method,
                                        $relatedModel,
                                        $comment
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
     * Add a property extracted form the model to the properties array
     *
     * @param string $name
     * @param string|null $type
     * @param string|null $comment
     */
    protected function setProperty($name, $type = null, $comment = '') {
        if (!isset($this->properties[$name])) {
            $this->properties[$name] = [];
            $this->properties[$name]['type'] = 'mixed';
            $this->properties[$name]['comment'] = (string) $comment;
        }
        if ($type !== null) {
            $this->properties[$name]['type'] = $type;
        }
    }

    /**
     * Add a method extracted from the model to the methods array
     *
     * @param $name
     * @param string $type
     * @param array $arguments
     */
    protected function setMethod($name, $type = '', $arguments = []) {
        $methods = array_change_key_case($this->methods, CASE_LOWER);
        if (!isset($methods[strtolower($name)])) {
            $this->methods[$name] = [];
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

    /**
     * Send the Apimap to the ForestAdmin's server
     * @return bool
     */
    public static function postApimap()
    {
        $map = self::getApimap();
        // Removed PHP_EOL at the end of the files because it make some bug
        // didn't correct it in forest-php package because maybe it's needed there
        $map = str_replace(PHP_EOL, '', $map);

        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'forest-secret-key' => Config::get('forest.SecretKey')
            ],
            'body' => $map
        ];

        $client = new Client();

        // Send the Apimap
        $response = $client->request('POST', Config::get('forest.ApiMap'), $options);


        if ($response->getStatusCode() != 204) {
            return false;
        }

        return true;
    }

    /**
     * Generate and retrieve Apimap
     * @return string
     */
    public static function getApimap()
    {
        $map = new Map(DatabaseStructure::getCollections(), self::getApimapMeta());
        return $map->getApimap();
    }

    /**
     * Retrieve Apimap metas
     * @return array
     */
    protected static function getApimapMeta()
    {
        // Retrieve version of the package from the composer file
        $path = getcwd().'/vendor/forestadmin/forest-laravel/';
        $composerFile = file_get_contents($path.'composer.json');
        $composerFile = json_decode($composerFile);

        return [
            'liana' => 'forest-laravel',
            'liana_version' => $composerFile->version
        ];
    }
}