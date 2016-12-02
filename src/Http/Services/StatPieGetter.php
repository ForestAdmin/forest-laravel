<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use ForestAdmin\ForestLaravel\Http\Services\ConditionSetter;

class StatPieGetter {
    protected $model;
    protected $groupByField;
    protected $aggregateType;
    protected $aggregateField;
    protected $filters;
    protected $filterType;
    protected $collectionSchema;
    protected $fieldTableNames;

    public $values;

    public function __construct($model, $params, $collectionSchema) {
        $this->model = $model;
        $this->collectionSchema = $collectionSchema;
        $this->tableNameModel = $this->model->getTable();
        $this->aggregateType = strtolower($params['aggregate']);

        $this->setFieldTableNames();

        if (strpos($params['group_by_field'], ':') === false) {
            $this->groupByField = $this->tableNameModel.'.'.
              strtolower($params['group_by_field']);
        } else {
            $groupByFieldExploded = explode(':', $params['group_by_field']);
            $this->groupByField = $this->fieldTableNames[
              reset($groupByFieldExploded)].'.'.end($groupByFieldExploded);
        }

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
        $aggregation = strtoupper($this->aggregateType);
        if ($this->aggregateType === 'count') {
            $aggregation = $aggregation.'(*)';
        } else if ($this->aggregateType === 'sum') {
            $aggregation = $aggregation.'('.$this->aggregateField.')';
        }

        $query = $this->model
          ->select(\DB::raw($this->groupByField.' as key'),
            \DB::raw($aggregation.' as value'))
          ->groupBy($this->groupByField)
          ->orderBy('value', 'DESC');

        $this->addJoins($query);
        $this->addFilters($query);

        $results = $query->get();

        $this->formatResults($results);
    }

    protected function getIncludes() {
        return $this->collectionSchema->getFieldNamesToOne();
    }

    protected function setFieldTableNames() {
      foreach($this->getIncludes() as $i => $field) {
          $this->fieldTableNames[$field->getField()] = 't'.$i;
      }
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

    protected function formatResults($results) {
        $this->values = array();
        foreach($results as $group) {
            $this->values[] = [ 'key' => $group->key, 'value' => $group->value ];
        }
    }
}
