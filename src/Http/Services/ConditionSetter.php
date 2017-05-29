<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use ForestAdmin\ForestLaravel\Http\Services\OperatorDateIntervalGetter;

class ConditionSetter {
    public static function perform($query, $typeWhere, $field, $value,
      $previous=false) {
        $comparison = null;
        $operatorDateIntervalGetter = new OperatorDateIntervalGetter($query,
          $typeWhere, $field, $value, $previous);

        if (substr($value, 0, 1) === '!' && substr($value, 1, 2) !== '*') {
            $query->{$typeWhere}($field, '!=', substr($value, 1));
        } else if (substr($value, 0, 1) === '>') {
            if ($operatorDateIntervalGetter->isIntervalDateValue()) {
                $operatorDateIntervalGetter->getIntervalDateFilter();
            } else {
                $query->{$typeWhere}($field, '>', substr($value, 1));
            }
        } else if (substr($value, 0, 1) === '<') {
            if ($operatorDateIntervalGetter->isIntervalDateValue()) {
                $operatorDateIntervalGetter->getIntervalDateFilter();
            } else {
                $query->{$typeWhere}($field, '<', substr($value, 1));
            }
        } else if (substr($value, 0, 1) === '!' &&
          substr($value, 1, 2) === '*' && substr($value, -1) === '*') {
          echo '----------------------------------------';
          $query->{$typeWhere}($field, 'NOT LIKE', '%'.substr($value, 2, -1).'%');
        } else if (substr($value, 0, 1) === '*' && substr($value, -1) === '*') {
            $query->{$typeWhere}($field, 'LIKE', '%'.substr($value, 1, -1).'%');
        } else if (substr($value, 0, 1) === '*') {
            $query->{$typeWhere}($field, 'LIKE', '%'.substr($value, 1));
        } else if (substr($value, -1) === '*') {
            $query->{$typeWhere}($field, 'LIKE', substr($value, 0, -1).'%');
        } else if ($value === '$present') {
            $query->{$typeWhere}($field, '!=', null);
        } else if ($value === '$blank') {
            $query->{$typeWhere}($field, '=', null);
        } else if ($operatorDateIntervalGetter->isIntervalDateValue()) {
            $operatorDateIntervalGetter->getIntervalDateFilter();
        } else {
            $query->{$typeWhere}($field, '=', $value);
        }
    }
}
