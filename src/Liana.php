<?php

namespace ForestAdmin\ForestLaravel;

use ForestAdmin\Liana\Adapter\EloquentAdapter;
use ForestAdmin\Liana\Exception\CollectionNotFoundException;

class Liana
{
    protected $collections;

    public function __construct()
    {
//        $this->setOrm($orm);
        $this->setCollections(array());
    }

    /**
     * @return Collection[]
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * @param Collection[] $collections
     * @return $this
     */
    public function setCollections($collections)
    {
        $this->collections = $collections;

        return $this;
    }

    /**
     * @param mixed $orm
     * @return $this
     */
    public function setOrm($orm)
    {
        $this->orm = $orm;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrm()
    {
        return $this->orm;
    }

    /**
    * Find a resource by its name and identifier
    *
    * @param string $modelName
    * @param mixed $recordId
    * @return object
    * @throws CollectionNotFoundException
    */
    public function getResource($modelName, $recordId)
    {
        $queryAdapter = $this->getQueryAdapter($modelName);
        $resource = $queryAdapter->getResource($recordId);
        return $resource;
    }


    /**
     * Find all resources by its name and filter
     * @param string $modelName
     * @param ResourceFilter $filter
     * @return array
     */
    public function listResources($modelName, $filter)
    {
        $queryAdapter = $this->getQueryAdapter($modelName);
        $resource = $queryAdapter->listResources($filter);

        return $resource;
    }

    /**
     * Returns the $associationName resources (hasMany relationship) of $modelName
     * @param string $modelName
     * @param string $recordId
     * @param string $associationName
     * @param ResourceFilter $filter
     * @return object The hasMany resources with its own relationships and a link to their many relationships
     */
    public function getHasMany($modelName, $recordId, $associationName, $filter)
    {
        $queryAdapter = $this->getQueryAdapter($modelName);
        $hasMany = $queryAdapter->getHasMany($recordId, $associationName, $filter);

        return $hasMany;
    }

    /**
     * @param string $modelName
     * @param array $postData
     * @return array The created resource
     */
    public function createResource($modelName, $postData)
    {
        $queryAdapter = $this->getQueryAdapter($modelName);
        $recordId = $queryAdapter->createResource($postData);

        if ($recordId) {
            return $queryAdapter->getResource($recordId);
        }

        return array();
    }

    /**
     * @param string $modelName
     * @param string $recordId
     * @param array $postData
     * @return array The updated resource
     */
    public function updateResource($modelName, $recordId, $postData)
    {
        $queryAdapter = $this->getQueryAdapter($modelName);
        $recordId = $queryAdapter->updateResource($recordId, $postData);

        if ($recordId) {
            return $queryAdapter->getResource($recordId);
        }

        return array();
    }

    /**
     * @param $entityName
     * @return null|Collection
     */
    protected function findCollection($entityName)
    {
        foreach ($this->getCollections() as $collection) {
            if ($collection->getName() == $entityName) {
                return $collection;
            }
        }

        return null;
    }

    /**
     * @return mixed
     */
    protected function isOrmEloquent()
    {
        // TODO: Check of to find if it's eloquent
//        return get_class($this->getOrm()) == "Doctrine\\Bundle\\DoctrineBundle\\Registry";
        return true;
    }

    /**
     * @param string $modelName
     * @return DoctrineAdapter|null
     * @throws CollectionNotFoundException
     */
    protected function getQueryAdapter($modelName)
    {
        $collection = $this->findCollection($modelName);
        if (!$collection) {
            throw new CollectionNotFoundException($modelName);
        }
        $entityName = $collection->getEntityClassName();
        $adapter = null;

        if ($this->isOrmEloquent()) {
            $adapter = new EloquentAdapter(
                $this->getCollections(),
                $collection
            );
        }

        return $adapter;
    }
}