<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use ForestAdmin\ForestLaravel\Http\Services\SearchBuilder;

class HasManyGetter {
    protected $modelResource;
    protected $relationObject;
    protected $associationName;
    protected $modelAssociation;
    protected $schemaAssociation;
    protected $fieldTableNames;
    protected $params;
    public $records;
    public $recordsCount;

    public function __construct($modelResource, $modelAssociation,
      $schemaAssociation, $associationName, $params) {
        $this->associationName = $associationName;
        $this->modelResource = $modelResource;
        $this->modelAssociation = $modelAssociation;
        $this->schemaAssociation = $schemaAssociation;
        $this->params = $params;

        $this->relationObject = SchemaUtils::getRelationship(
            $this->modelResource->find($this->params->recordId),
            $this->associationName);
    }

    public function perform() {
        if (array_key_exists('number', $this->params->page)) {
            $pageNumber = $this->params->page['number'];
        } else {
            $pageNumber = 0;
        }
        $pageSize = $this->params->page['size'];

        $query = $this->getBaseQuery()->skip(($pageNumber - 1) * $pageSize)
                                      ->take($pageSize);

        $this->addOrderBy($query);

        $this->records = $query->get();
    }

    public function count() {
        $query = $this->getCountQuery();

        $this->recordsCount = $query->count();
    }

    protected function getBaseQuery() {
        $query = $this->relationObject
            ->select($this->modelAssociation->getTable().'.*');
        $query = $this->addSearch($query);

        return $query;
    }

    protected function getCountQuery() {
        $query = $this->relationObject;
        $query = $this->addSearch($query);

        return $query;
    }

    protected function addSearch($query) {
        $this->addJoins($query);

        $query->where(function($query) {
            $searchBuilder = new SearchBuilder($query, $this->schemaAssociation,
                $this->modelAssociation->getTable(), $this->fieldTableNames,
                $this->params);
            $searchBuilder->perform();
        });

        return $query;
    }

    protected function getIncludes() {
        return $this->schemaAssociation->getFieldNamesToOne();
    }

    protected function addJoins($query) {
        foreach($this->getIncludes() as $i => $field) {
            $fieldName = $field->getField();
            $tableNameInclude = $this->modelAssociation
              ->{$fieldName}()
              ->getRelated()
              ->getTable();
            $modelField = $this->modelAssociation->{$fieldName}();

            if ($field->getInverseOf()) {
                // NOTICE: HasOne Relationship
                if (method_exists($modelField, 'getForeignKeyName')) {
                    $foreignKey = $modelField->getForeignKeyName();
                } else {
                    // NOTICE: Support Laravel versions before 5.4
                    $foreignKey = $modelField->getForeignKey();
                }
                $query->leftJoin($tableNameInclude.' AS t'.$i,
                  $this->modelAssociation->getTable().'.id', '=',
                  't'.$i.'.'.$foreignKey);
            } else {
                // NOTICE: BelongsTo relationship
                $foreignKey = $modelField->getForeignKey();
                $query->leftJoin($tableNameInclude.' AS t'.$i,
                  $this->modelAssociation->getTable().'.'.$foreignKey, '=',
                  't'.$i.'.id');
            }

            $this->fieldTableNames[$field->getField()] = 't'.$i;
        }
    }

    protected function addOrderBy($query) {
        if ($this->params->sort) {
            $order = 'ASC';
            $sort = $this->params->sort;

            if (substr($this->params->sort, 0, 1) === '-') {
                $sort = substr($sort, 1);
                $order = 'DESC';
            }

            if (strpos($sort, '.') === false) {
                $sort = $this->modelAssociation->getTable().'.'.$sort;
            } else {
                $sortExploded = explode('.', $sort);
                $sort = implode('s.', $sortExploded);
            }
            $query->orderBy($sort, $order);
        }
    }
}
