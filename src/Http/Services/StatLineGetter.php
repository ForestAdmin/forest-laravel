<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use Carbon\Carbon;

class StatLineGetter {
    protected $model;
    protected $groupByDateField;
    protected $timeRange;
    protected $aggregateType;
    protected $aggregateField;
    protected $filters;
    protected $filterType;

    private $currentDate;
    public $values;

    public function __construct($model, $params) {
        $this->model = $model;
        $this->tableNameModel = $this->model->getTable();
        $this->groupByDateField = $params['group_by_date_field'];
        $this->timeRange = strtolower($params['time_range']);
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
          ->select($this->getGroupByDateInterval(),
            \DB::raw($aggregation.' as value'))
          ->groupBy('date')
          ->orderBy('date', 'ASC');

        $this->addFilters($query);

        $results = $query->get();

        $this->formatResults($results);
    }

    protected function getGroupByDateInterval() {
        // TODO: Support the date interval on mySQL.
        return \DB::raw('to_char(date_trunc(\''.$this->timeRange.'\', "'.
          $this->groupByDateField.'"), \'YYYY-MM-DD 00:00:00\') as date');
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
            $this->values[] = [
              'label' => $group->date,
              'values' => ['value' => $group->value ]
            ];
        }

        $this->fillEmptyDateIntervals();
    }

    protected function fillEmptyDateIntervals() {
        if (sizeof($this->values)) {
            $firstDate = new Carbon($this->values[0]['label']);
            $lastDate = new Carbon($this->values[sizeof($this->values) - 1]['label']);
            $timeRange = $this->timeRange;
            $incrementMethod = 'add'.ucfirst($timeRange);

            for($this->currentDate = $firstDate; $this->currentDate < $lastDate;
              $this->currentDate = $this->currentDate->{$incrementMethod}()) {
                $similars = array_filter($this->values, function($value) {
                  return $this->currentDate->eq(new Carbon($value['label']));
                });

                if (sizeof($similars) === 0) {
                    $this->values[] = [
                      'label' => (string) $this->currentDate,
                      'values' => ['value' => 0 ]
                    ];
                }

                usort($this->values, function($v1, $v2) {
                  return $v1['label'] > $v2['label'];
                });
            }
        }
    }
}
