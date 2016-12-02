<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use ForestAdmin\ForestLaravel\Bootstraper;
use ForestAdmin\ForestLaravel\Http\Services\ConditionSetter;

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
        $pageNumber = $this->params->page['number'];
        $pageSize = $this->params->page['size'];

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
        if ($this->params->search) {
            foreach($this->collectionSchema->getFields() as $field) {
                if ($field->isAttribute()) {
                    // TODO: Ignore Smart field.
                    // TODO: Ignore integration field.
                    // TODO: Support enums in the search
                    if ($field->getType() === 'Number' &&
                      intval($this->params->search) !== 0) {
                        $query->orWhere($this->tableNameModel.'.'.
                          $field->getField(), intval($this->params->search));
                    } else if ($field->getType() === 'String') {
                        $query->orWhereRaw('LOWER("'.$this->tableNameModel.'"."'.
                          $field->getField().'") LIKE LOWER(\'%'.
                          $this->params->search.'%\')');
                    }
                } else if ($field->isTypeToOne()) {
                    $modelAssociation = $this->getCollectionSchema(
                      $field->getReferencedModelName());
                    $tableNameAssociation = SchemaUtils::findResource(
                      $field->getReferencedModelName())->getTable();
                    $tableAssociation = $this->fieldTableNames[$field->getField()];

                    foreach($modelAssociation->getFields() as $fieldAssociation) {
                        if ($fieldAssociation->isAttribute()) {
                            if ($fieldAssociation->getType() === 'Number' &&
                              intval($this->params->search) !== 0) {
                                $query->orWhere($tableAssociation.'.'.
                                  $fieldAssociation->getField(),
                                  intval($this->params->search));
                            } else if ($fieldAssociation->getType() === 'String') {
                                $query->orWhereRaw('LOWER("'.$tableAssociation.'"."'.
                                  $fieldAssociation->getField().'") LIKE LOWER(\'%'.
                                  $this->params->search.'%\')');
                            }
                        }
                    }
                }
            }
        }
    }

    // TODO: Factorise this code (duplicated in ApplicationController)
    protected function getCollectionSchema($collectionSchemaName) {
        $collections = (new Bootstraper())->getCollections();

        foreach($collections as $collection) {
            if ($collection->getName() == $collectionSchemaName) {
                return $collection;
            }
        }

        return;
    }
}
