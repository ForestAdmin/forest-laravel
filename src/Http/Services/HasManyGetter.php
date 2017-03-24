<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

class HasManyGetter {
    protected $modelResource;
    protected $associationName;
    protected $modelAssociation;
    protected $schemaAssociation;
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
    }

    public function perform() {
        if (array_key_exists('number', $this->params->page)) {
            $pageNumber = $this->params->page['number'];
        } else {
            $pageNumber = 0;
        }
        $pageSize = $this->params->page['size'];

        $relationObj = SchemaUtils::getRelationship(
            $this->modelResource->find($this->params->recordId),
            $this->associationName);

        $query = $relationObj
          ->select($this->modelAssociation->getTable().'.*')
          ->skip(($pageNumber - 1) * $pageSize)
          ->take($pageSize);

        $this->addJoins($query);
        $this->addOrderBy($query);

        $this->records = $query->get();

        $query = SchemaUtils::getRelationship(
            $this->modelResource->find($this->params->recordId),
            $this->associationName);

        $this->recordsCount = $query->count();
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
                $query->leftJoin($tableNameInclude,
                  $this->modelAssociation->getTable().'.id', '=', $foreignKey);
            } else {
                // NOTICE: BelongsTo relationship
                $foreignKey = $modelField->getForeignKey();
                $query->leftJoin($tableNameInclude.' AS t'.$i,
                  $this->modelAssociation->getTable().'.'.$foreignKey, '=',
                  't'.$i.'.id');
            }
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
