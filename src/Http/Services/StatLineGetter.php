<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use Carbon\Carbon;
use ForestAdmin\ForestLaravel\Database;

class StatLineGetter {
    protected $model;
    protected $groupByDateField;
    protected $timeRange;
    protected $aggregateType;
    protected $aggregateField;
    protected $filters;
    protected $filterType;
    protected $collectionSchema;

    private $currentDate;
    public $values;

    public function __construct($model, $params, $collectionSchema) {
        $this->model = $model;
        $this->collectionSchema = $collectionSchema;
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

        $this->addJoins($query);
        $this->addFilters($query);

        $results = $query->get();

        $this->formatResults($results);
    }

    protected function getGroupByDateInterval() {
        if (Database\Utils::isMySql()) {
            $this->groupByDateField = '`'.$this->tableNameModel.'`.`'.
              $this->groupByDateField.'`';
            switch ($this->timeRange) {
                case 'day':
                    return \DB::raw('DATE_FORMAT('.$this->groupByDateField.
                      ', \'%Y-%m-%d 00:00:00\') as date');
                    break;
                case 'week':
                    return \DB::raw('DATE_FORMAT(DATE_SUB('.
                      $this->groupByDateField.', INTERVAL ((7 + WEEKDAY'.
                      '('.$this->groupByDateField.')) % 7) DAY), '.
                      '\'%Y-%m-%d 00:00:00\') as date');
                    break;
                case 'month':
                    return \DB::raw('DATE_FORMAT('.$this->groupByDateField.
                      ', \'%Y-%m-01 00:00:00\') as date');
                    break;
                case 'year':
                    return \DB::raw('DATE_FORMAT('.$this->groupByDateField.
                      ', \'%Y-01-01 00:00:00\') as date');
                    break;
            }
        } else {
            $this->groupByDateField = '"'.$this->tableNameModel.'"."'.
              $this->groupByDateField.'"';
            return \DB::raw('to_char(date_trunc(\''.$this->timeRange.'\', '.
              $this->groupByDateField.'), \'YYYY-MM-DD 00:00:00\') as date');
        }
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
        if ($this->filterType) {
            foreach($this->filters as $filter) {
                if (strpos($filter['field'], ':') === false) {
                    $field = $this->tableNameModel.'.'.$filter['field'];
                } else {
                    $fieldExploded = explode(':', $filter['field']);
                    $field = $this->fieldTableNames[reset($fieldExploded)].
                      '.'.end($fieldExploded);
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
