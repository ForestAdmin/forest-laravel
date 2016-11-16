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

    public $values;

    public function __construct($model, $params) {
        $this->model = $model;
        $this->tableNameModel = $this->model->getTable();
        $this->groupByField = strtolower($params['group_by_field']);
        $this->aggregateType = strtolower($params['aggregate']);

        if (array_key_exists('aggregate_field', $params)) {
            $this->aggregateField = $params['aggregate_field'];
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

        $this->addFilters($query);

        $results = $query->get();

        $this->formatResults($results);
    }

    protected function addFilters($query) {
        if ($this->filterType) {
            foreach($this->filters as $filter) {
                if (strpos($filter['field'], ':') === false) {
                    $field = $this->tableNameModel.'.'.$filter['field'];
                } else {
                    $fieldExploded = explode(':', $filter['field']);
                    $field = implode('s.', $fieldExploded);
                }

                $values = explode(',', $filter['value']);
                foreach($values as $value) {
                    ConditionSetter::perform($query, $this->filterType,
                      $filter['field'], $value);
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
