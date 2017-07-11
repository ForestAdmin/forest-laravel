<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use ForestAdmin\ForestLaravel\Bootstraper;
use ForestAdmin\ForestLaravel\Database;

class SearchBuilder {
    protected $collectionSchema;
    protected $tableNameModel;
    protected $fieldTableNames;
    protected $params;

    public function __construct($query, $collectionSchema, $tableNameModel,
      $fieldTableNames, $params) {
        $this->query = $query;
        $this->collectionSchema = $collectionSchema;
        $this->tableNameModel = $tableNameModel;
        $this->fieldTableNames = $fieldTableNames;
        $this->params = $params;
    }

    public function perform() {
        $s = Database\Utils::separator();
        if ($this->params->search) {
            foreach($this->collectionSchema->getFields() as $field) {
                if ($field->isAttribute()) {
                    // TODO: Ignore Smart field.
                    // TODO: Ignore integration field.
                    // TODO: Support enums in the search
                    if ($field->getType() === 'Number' &&
                      intval($this->params->search) !== 0) {
                        $this->query->orWhere($this->tableNameModel.'.'.
                          $field->getField(), intval($this->params->search));
                    } else if ($field->getType() === 'String') {
                        $this->query->orWhereRaw('LOWER('.$s.
                          $this->tableNameModel.$s.'.'.$s.$field->getField().
                          $s.') LIKE LOWER(\'%'.$this->params->search.'%\')');
                    }
                } else if ($field->isTypeToOne()) {
                    $modelAssociation = $this->getCollectionSchema(
                      $field->getReferencedModelName());
                    $tableNameAssociation = SchemaUtils::findResource(
                      $field->getReferencedModelName())->getTable();
                    $tableAssociation = $this->fieldTableNames[$field->getField()];

                    // NOTICE: Prevent errors on search if the modelAssociation
                    //         is not found.
                    // TODO: Make the search feature evolve with 2 kinds of
                    //       search: simple search and a deep search.
                    if ($modelAssociation) {
                      foreach($modelAssociation->getFields() as
                        $fieldAssociation) {
                          if ($fieldAssociation->isAttribute()) {
                              if ($fieldAssociation->getType() === 'Number' &&
                                intval($this->params->search) !== 0) {
                                  $this->query->orWhere($tableAssociation.'.'.
                                    $fieldAssociation->getField(),
                                    intval($this->params->search));
                              } else if ($fieldAssociation->getType() ===
                                'String') {
                                  $this->query->orWhereRaw('LOWER('.$s.
                                    $tableAssociation.$s.'.'.$s.
                                    $fieldAssociation->getField().$s.
                                    ') LIKE LOWER(\'%'.$this->params->search.
                                    '%\')');
                              }
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
