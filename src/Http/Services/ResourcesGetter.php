<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use ForestAdmin\ForestLaravel\Http\Services\ConditionSetter;
use ForestAdmin\ForestLaravel\Http\Services\SearchBuilder;

class ResourcesGetter {
    protected $tableNameModel;
    protected $model;
    protected $modelName;
    protected $collectionSchema;
    protected $params;
    protected $fieldTableNames;

    public $records;
    public $recordsCount;

    public function __construct($modelName, $model, $collectionSchema,
      $params) {
        $this->modelName = $modelName;
        $this->model = $model;
        $this->collectionSchema = $collectionSchema;
        $this->tableNameModel = $this->model->getTable();
        $this->params = $params;
    }

    public function perform() {
        $pageNumber = $this->params->page['number'] ?: 1;
        $pageSize = $this->params->page['size'] ?: 10;

        $query = $this->model->select($this->tableNameModel.'.*')
                             ->skip(($pageNumber - 1) * $pageSize)
                             ->take($pageSize);

        $this->addJoins($query);
        $this->addOrderBy($query);
        $query->where(function($query) { $this->addSearch($query); });
        $query->where(function($query) { $this->addFilters($query); });

        $this->records = $query->get();

        $queryCount = $this->model->select($this->tableNameModel.'.*');
        $this->addJoins($queryCount);
        $queryCount->where(function($query) { $this->addSearch($query); });
        $queryCount->where(function($query) { $this->addFilters($query); });

        $this->recordsCount = $queryCount->count();
    }

    public function getQueryForBatch() {
      $query = $this->model->select($this->tableNameModel.'.*');

      $this->addJoins($query);
      // $this->addOrderBy($query);
      $query->where(function($query) { $this->addSearch($query); });
      $query->where(function($query) { $this->addFilters($query); });

      return $query;
    }

    protected function getIncludes() {
        return $this->collectionSchema->getFieldNamesToOne();
    }

    protected function addJoins($query) {
        foreach($this->getIncludes() as $i => $field) {
            $tableNameAssociation = SchemaUtils::findResource(
              $field->getReferencedModelName())->getTable();
            $modelField = $this->model->{$field->getField()}();

            if ($field->getInverseOf()) {
                // NOTICE: HasOne Relationship
                if (method_exists($modelField, 'getForeignKeyName')) {
                    $foreignKey = $modelField->getForeignKeyName();
                } else {
                    // NOTICE: Support Laravel versions before 5.4
                    $foreignKey = $modelField->getForeignKey();
                }
                $query->leftJoin($tableNameAssociation.' AS t'.$i,
                  $this->tableNameModel.'.id', '=', 't'.$i.'.'.$foreignKey);
            } else {
                // NOTICE: BelongsTo relationship
                $foreignKey = $modelField->getForeignKey();
                $query->leftJoin($tableNameAssociation.' AS t'.$i,
                  $this->tableNameModel.'.'.$foreignKey, '=', 't'.$i.'.id');
            }

            $this->fieldTableNames[$field->getField()] = 't'.$i;
        }
    }

    protected function addFilters($query) {
        if ($this->params->filter) {
            $typeWhere = $this->params->filterType == 'and' ? 'where' : 'orWhere';

            foreach($this->params->filter as $field => $values) {
                if (strpos($field, ':') === false) {
                    $field = $this->tableNameModel.'.'.$field;
                } else {
                    $fieldExploded = explode(':', $field);
                    $field = $this->fieldTableNames[reset($fieldExploded)].'.'.
                      end($fieldExploded);
                }

                $values = explode(',', $values);
                foreach($values as $value) {
                    ConditionSetter::perform($query, $typeWhere, $field, $value);
                }
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
                $this->params->sort = $this->tableNameModel.'.'.$this->params->sort;
            } else {
                $sortExploded = explode('.', $this->params->sort);
                $this->params->sort = implode('s.', $sortExploded);
            }
            $query->orderBy($this->params->sort, $order);
        }
    }

    protected function addSearch($query) {
        $searchBuilder = new SearchBuilder($query, $this->collectionSchema,
            $this->tableNameModel, $this->fieldTableNames, $this->params);
        $searchBuilder->perform();
    }
}
