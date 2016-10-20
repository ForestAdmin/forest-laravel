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

        $query = $this->modelResource
          ->find($this->params->recordId)->{$this->associationName}()
          ->select($this->modelAssociation->getTable().'.*')
          ->skip(($pageNumber - 1) * $pageSize)
          ->take($pageSize);

        $this->addJoins($query);
        $this->addOrderBy($query);

        $this->records = $query->get();

        $this->recordsCount = $this->modelResource
          ->find($this->params->recordId)
          ->{$this->associationName}()
          ->count();
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

            $foreignKey = $this->modelAssociation->{$fieldName}()
              ->getForeignKey();
            if ($field->getInverseOf()) {
                // NOTICE: HasOne Relationship
                $query->leftJoin($tableNameInclude,
                  $this->modelAssociation->getTable().'.id', '=', $foreignKey);
            } else {
                // NOTICE: BelongsTo relationship
                $query->leftJoin($tableNameInclude.' AS t'.$i,
                  $this->modelAssociation->getTable().'.'.$foreignKey, '=',
                  't'.$i.'.id');
            }
        }
    }

    protected function addOrderBy($query) {
        if ($this->params->sort) {
            $order = 'ASC';

            if (substr($this->params->sort, 0, 1) === '-') {
                $this->params->sort = substr($this->params->sort, 1);
                $order = 'DESC';
            }

            if (strpos($this->params->sort, '.') === false) {
                $this->params->sort =
                  $this->modelAssociation->getTable().'.'.$this->params->sort;
            } else {
                $sortExploded = explode('.', $this->params->sort);
                $this->params->sort = implode('s.', $sortExploded);
            }
            $query->orderBy($this->params->sort, $order);
        }
    }
}
