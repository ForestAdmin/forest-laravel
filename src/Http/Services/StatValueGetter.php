<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use ForestAdmin\ForestLaravel\Http\Services\ConditionSetter;

class StatValueGetter {
    protected $model;
    protected $aggregateType;
    protected $aggregateField;
    protected $filters;
    protected $filterType;
    protected $hasPreviousInterval;
    protected $collectionSchema;
    protected $fieldTableNames;

    public $values;

    public function __construct($model, $params, $collectionSchema) {
        $this->model = $model;
        $this->collectionSchema = $collectionSchema;
        $this->tableNameModel = $this->model->getTable();
        $this->aggregateType = strtolower($params['aggregate']);

        if (array_key_exists('aggregate_field', $params)) {
            $this->aggregateField = $this->tableNameModel.'.'.
              $params['aggregate_field'];
        }

        if (array_key_exists('filterType', $params)) {
            $this->filterType = $params['filterType'] == 'and' ? 'where' :
              'orWhere';
        }

        if (array_key_exists('filters', $params)) {
            $this->filters = $params['filters'];
        } else {
            $this->filters = [];
        }
    }

    public function perform() {
        $valueCurrent = 0;

        // NOTICE: The 'select' call is a hack to enable the filters setting
        $queryCurrent = $this->model->select('*');
        $this->addJoins($queryCurrent);
        $this->addFilters($queryCurrent);

        if ($this->aggregateType === 'count') {
            $valueCurrent = $queryCurrent->count();
        } else if ($this->aggregateType === 'sum') {
            $valueCurrent = $queryCurrent->sum($this->aggregateField);
        }

        $valuePrevious = null;

        $queryPrevious = $this->model->select('*');
        $this->addJoins($queryPrevious);
        $this->addFiltersForPrevious($queryPrevious);

        // NOTICE: Search for previous interval value only if the filterType is
        //         'AND', it would not be pertinent for a 'OR' filterType.
        if ($this->hasPreviousInterval && $this->filterType === 'where') {
            if ($this->aggregateType === 'count') {
                $valuePrevious = $queryPrevious->count();
            } else if ($this->aggregateType === 'sum') {
                $valuePrevious = $queryPrevious->sum($this->aggregateField);
            }
            $valuePrevious = $valuePrevious ?: 0;
        }

        $this->values = [
          'countCurrent' => $valueCurrent ?: 0,
          'countPrevious' => $valuePrevious
        ];
    }

    protected function getIncludes() {
        return $this->collectionSchema->getFieldNamesToOne();
    }

    protected function addJoins($query) {
        foreach($this->getIncludes() as $i => $field) {
            $foreignKey = $this->model->{$field->getField()}()->getForeignKey();
            $tableNameAssociation = SchemaUtils::findResource(
              $field->getReferencedModelName())->getTable();

            if ($field->getInverseOf()) {
                // NOTICE: HasOne Relationship
                $foreignKeyExploded = explode('.', $foreignKey);
                $foreignKey = end($foreignKeyExploded);
                $query->leftJoin($tableNameAssociation.' AS t'.$i,
                  $this->tableNameModel.'.id', '=', 't'.$i.'.'.$foreignKey);
            } else {
                // NOTICE: BelongsTo relationship
                $query->leftJoin($tableNameAssociation.' AS t'.$i,
                  $this->tableNameModel.'.'.$foreignKey, '=', 't'.$i.'.id');
            }

            $this->fieldTableNames[$field->getField()] = 't'.$i;
        }
    }

    protected function addFilters($query) {
        if ($this->filterType) {
            foreach($this->filters as $filter) {
                if (strpos($filter['field'], ':') === false) {
                    $field = $this->tableNameModel.'.'.$filter['field'];
                } else {
                    $fieldExploded = explode(':', $filter['field']);
                    $field = $this->fieldTableNames[reset($fieldExploded)].'.'.
                      end($fieldExploded);
                }

                $values = explode(',', $filter['value']);
                foreach($values as $value) {
                    ConditionSetter::perform($query, $this->filterType,
                      $field, $value);
                }
            }
        }
    }

    protected function addFiltersForPrevious($query) {
        $this->hasPreviousInterval = false;

        if ($this->filterType) {
            foreach($this->filters as $filter) {
                if (strpos($filter['field'], ':') === false) {
                    $field = $this->tableNameModel.'.'.$filter['field'];
                } else {
                    $fieldExploded = explode(':', $filter['field']);
                    $field = $this->fieldTableNames[reset($fieldExploded)].'.'.
                      end($fieldExploded);
                }

                $values = explode(',', $filter['value']);
                foreach($values as $value) {
                    $operatorValueParser = new OperatorDateIntervalGetter($query,
                      $this->filterType, $field, $value);
                    if ($operatorValueParser->hasPreviousInterval()) {
                        $this->hasPreviousInterval = true;
                    }

                    ConditionSetter::perform($query, $this->filterType,
                      $field, $value, true);
                }
            }
        }
    }
}
