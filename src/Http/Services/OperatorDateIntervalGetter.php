<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use Carbon\Carbon;

class OperatorDateIntervalGetter {
    protected $query;
    protected $typeWhere;
    protected $field;
    protected $value;
    protected $previous;

    protected $matches;
    protected $duration;
    protected $period;
    protected $toDate;
    protected $periodSub;
    protected $periodStartOf;
    protected $periodEndOf;

    protected $dateStart;
    protected $dateEnd;

    protected $periods = array(
        '$yesterday' => array('duration' => 1, 'timeUnit' => 'Day', 'period' => 'Day', 'toDate' => false),
        '$previousWeek' => array('duration' => 1, 'timeUnit' => 'Week', 'period' => 'Week', 'toDate' => false),
        '$previousMonth' => array('duration' => 1, 'timeUnit' => 'Month', 'period' => 'Month', 'toDate' => false),
        '$previousQuarter' => array('duration' => 3, 'timeUnit' => 'Month', 'period' => 'Quarter', 'toDate' => false),
        '$previousYear' => array('duration' => 1, 'timeUnit' => 'Year', 'period' => 'Year', 'toDate' => false),
        '$weekToDate' => array('duration' => 1, 'timeUnit' => 'Week', 'period' => 'Week', 'toDate' => true),
        '$monthToDate' => array('duration' => 1, 'timeUnit' => 'Month', 'period' => 'Month', 'toDate' => true),
        '$quarterToDate' => array('duration' => 3, 'timeUnit' => 'Month', 'period' => 'Quarter', 'toDate' => true),
        '$yearToDate' => array('duration' => 1, 'timeUnit' => 'Year', 'period' => 'Year', 'toDate' => true)
    );

    protected $periodsPast = '$past';
    protected $periodsFuture = '$future';
    protected $periodsToday = '$today';
    protected $periodsPreviousXDays= '/^\$previous(\d+)Days$/';
    protected $periodsXDaysToDate = '/^\$(\d+)DaysToDate$/';
    protected $periodsXHoursBefore = '/^\$(\d+)HoursBefore$/';

    public function __construct($query, $typeWhere, $field, $value,
      $previous=false) {
        $this->query = $query;
        $this->typeWhere = $typeWhere;
        $this->field = $field;
        $this->value = $value;
        $this->previous = $previous;
    }

    public function isIntervalDateValue() {
        if (in_array($this->value, array_keys($this->periods))) {
          return true;
        }

        if (in_array($this->value, array($this->periodsPast,
          $this->periodsFuture, $this->periodsToday))) {
            return true;
        }

        if (preg_match($this->periodsPreviousXDays, $this->value,
          $matches)) {
            return true;
        }

        if (preg_match($this->periodsXDaysToDate, $this->value, $matches)) {
            return true;
        }

        if (preg_match($this->periodsXHoursBefore, $this->value, $matches)) {
            return true;
        }

        return false;
    }

    public function hasPreviousInterval() {
        if (in_array($this->value, array_keys($this->periods))) {
          return true;
        }

        if ($this->value === $this->periodsToday) { return true; }

        if (preg_match($this->periodsPreviousXDays, $this->value, $matches)) {
            return true;
        }

        if (preg_match($this->periodsXDaysToDate, $this->value, $matches)) {
            return true;
        }

        return false;
    }

    public function getIntervalDateFilter() {
      if ($this->previous) {
          $this->getIntervalDateFilterForPreviousInterval();
      } else {
          $this->getIntervalDateFilterForCurrentInterval();
      }
    }

    public function getIntervalDateFilterForCurrentInterval() {
        if (!$this->isIntervalDateValue()) { return; }

        if ($this->value == $this->periodsPast) {
            return $this->query->{$this->typeWhere}($this->field, '<',
              Carbon::now());
        }

        if ($this->value == $this->periodsFuture) {
            return $this->query->{$this->typeWhere}($this->field, '>',
              Carbon::now());
        }

        if ($this->value == $this->periodsToday) {
            return $this->query->{$this->typeWhere}(function ($query) {
                $query->where($this->field, '>=', Carbon::now()->startOfDay())
                      ->where($this->field, '<=', Carbon::now()->endOfDay());
            });
        }

        if (preg_match($this->periodsPreviousXDays, $this->value,
          $this->matches)) {
            return $this->query->{$this->typeWhere}(function ($query) {
                $query->where($this->field, '>=', Carbon::now()
                         ->subDays($this->matches[1][0])->startOfDay())
                      ->where($this->field, '<=', Carbon::now()
                         ->subDay()->endOfDay());
            });
        }

        if (preg_match($this->periodsXDaysToDate, $this->value,
          $this->matches)) {
            return $this->query->{$this->typeWhere}(function ($query) {
                $query->where($this->field, '>=', Carbon::now()
                         ->subDays($this->matches[1][0] - 1)->startOfDay())
                      ->where($this->field, '<=', Carbon::now());
            });
        }

        if (preg_match($this->periodsXHoursBefore, $this->value,
          $this->matches)) {
            return $this->query->{$this->typeWhere}(function ($query) {
                $query->where($this->field, '<', Carbon::now()
                         ->subDays($this->matches[1][0]));
            });
        }

        $this->duration = $this->periods[$this->value]['duration'];
        $this->timeUnit = $this->periods[$this->value]['timeUnit'];
        $this->period = $this->periods[$this->value]['period'];
        $this->toDate = $this->periods[$this->value]['toDate'];
        $this->periodSub = 'sub'.$this->timeUnit.'s';

        // NOTICE: Carbon does not have the startOfQuarter and
        //         endOfQuarter methods.
        if (in_array($this->period, ['Month', 'Quarter', 'Year'])) {
            $this->periodStartOf = 'firstOf'.$this->period;
            $this->periodEndOf = 'lastOf'.$this->period;
        }

        $this->unitStartOf = 'startOf'.$this->timeUnit;
        $this->unitEndOf = 'endOf'.$this->timeUnit;

        if ($this->toDate) {
            return $this->query->{$this->typeWhere}(function ($query) {
                $this->dateStart = Carbon::now();
                if ($this->periodStartOf) {
                  $this->dateStart = $this->dateStart->{$this->periodStartOf}();
                }
                $this->dateStart = $this->dateStart->{$this->unitStartOf}();

                $query->where($this->field, '>=', $this->dateStart)
                      ->where($this->field, '<=', Carbon::now());
            });
        } else {
            $this->dateStart = Carbon::now()->{$this->periodSub}($this->duration);
            if ($this->periodStartOf) {
              $this->dateStart = $this->dateStart->{$this->periodStartOf}();
            }
            $this->dateStart = $this->dateStart->{$this->unitStartOf}();

            $this->dateEnd = Carbon::now()->{$this->periodSub}($this->duration);
            if ($this->periodEndOf) {
              $this->dateEnd = $this->dateEnd->{$this->periodEndOf}();
            }
            $this->dateEnd = $this->dateEnd->{$this->unitEndOf}();

            return $this->query->{$this->typeWhere}(function ($query) {
                $query->where($this->field, '>=', $this->dateStart)
                      ->where($this->field, '<=', $this->dateEnd);
            });
        }
    }

    public function getIntervalDateFilterForPreviousInterval() {
        if (!$this->hasPreviousInterval()) { return; }

        if ($this->value == $this->periodsToday) {
            return $this->query->{$this->typeWhere}(function ($query) {
                $query->where($this->field, '>=', Carbon::now()->subDay()
                        ->startOfDay())
                      ->where($this->field, '<=', Carbon::now()->subDay()
                        ->endOfDay());
            });
        }

        if (preg_match($this->periodsPreviousXDays, $this->value,
          $this->matches)) {
            return $this->query->{$this->typeWhere}(function ($query) {
                $query->where($this->field, '>=', Carbon::now()
                         ->subDays($this->matches[1][0] * 2)->startOfDay())
                      ->where($this->field, '<=', Carbon::now()
                         ->subDays($this->matches[1][0] + 1)->endOfDay());
            });
        }

        if (preg_match($this->periodsXDaysToDate, $this->value,
          $this->matches)) {
            return $this->query->{$this->typeWhere}(function ($query) {
                $query->where($this->field, '>=', Carbon::now()
                         ->subDays(($this->matches[1][0] * 2) - 1)
                         ->startOfDay())
                      ->where($this->field, '<=', Carbon::now()
                         ->subDays($this->matches[1][0]));
            });
        }

        $this->duration = $this->periods[$this->value]['duration'];
        $this->timeUnit = $this->periods[$this->value]['timeUnit'];
        $this->period = $this->periods[$this->value]['period'];
        $this->toDate = $this->periods[$this->value]['toDate'];
        $this->periodSub = 'sub'.$this->timeUnit.'s';

        // NOTICE: Carbon does not have the startOfQuarter and
        //         endOfQuarter methods.
        if (in_array($this->period, ['Month', 'Quarter', 'Year'])) {
            $this->periodStartOf = 'firstOf'.$this->period;
            $this->periodEndOf = 'lastOf'.$this->period;
        }

        $this->unitStartOf = 'startOf'.$this->timeUnit;
        $this->unitEndOf = 'endOf'.$this->timeUnit;

        if ($this->toDate) {
            $this->dateStart = Carbon::now()->{$this->periodSub}($this->duration);
            if ($this->periodStartOf) {
              $this->dateStart = $this->dateStart->{$this->periodStartOf}();
            }
            $this->dateStart = $this->dateStart->{$this->unitStartOf}();

            return $this->query->{$this->typeWhere}(function ($query) {
                $query->where($this->field, '>=', $this->dateStart)
                      ->where($this->field, '<=', Carbon::now()
                          ->{$this->periodSub}($this->duration));
            });
        } else {
            $this->dateStart = Carbon::now()->{$this->periodSub}($this->duration * 2);
            if ($this->periodStartOf) {
              $this->dateStart = $this->dateStart->{$this->periodStartOf}();
            }
            $this->dateStart = $this->dateStart->{$this->unitStartOf}();

            $this->dateEnd = Carbon::now()->{$this->periodSub}($this->duration * 2);
            if ($this->periodEndOf) {
              $this->dateEnd = $this->dateEnd->{$this->periodEndOf}();
            }
            $this->dateEnd = $this->dateEnd->{$this->unitEndOf}();

            return $this->query->{$this->typeWhere}(function ($query) {
                $query->where($this->field, '>=', $this->dateStart)
                      ->where($this->field, '<=', $this->dateEnd);
            });
        }
    }
}
