<?php

namespace ForestAdmin\ForestLaravel\Http\Services;

use ForestAdmin\ForestLaravel\Bootstraper;
use ForestAdmin\ForestLaravel\Database;
use Illuminate\Support\Facades\Config;

class SearchBuilder {
    protected $collectionSchema;
    protected $tableNameModel;
    protected $fieldTableNames;
    protected $params;
    protected $searchFields;

    public function __construct($query, $collectionSchema, $tableNameModel,
      $fieldTableNames, $params) {
        $this->query = $query;
        $this->collectionSchema = $collectionSchema;
        $this->tableNameModel = $tableNameModel;
        $this->fieldTableNames = $fieldTableNames;
        $this->params = $params;

        $collectionName = $collectionSchema->getName();
        $config = Config::get('forest.collection');

        if (array_key_exists($collectionName, $config)) {
            $collectionConfig = $config[$collectionName];

            if (array_key_exists('search_fields', $collectionConfig)) {
                $this->searchFields =
                    $this->processSearchFieldsConfig($collectionConfig['search_fields']);
            }
        }
    }

    private function processSearchFieldsConfig($searchFieldsConfig) {
        $searchFields = array_reduce($searchFieldsConfig, function ($carry, $item) {
            $fields = explode('.', $item);
            $count = count($fields);

            if ($count == 1) {
                if (!property_exists($carry, 'default')) {
                    $carry->default = array();
                }
                array_push($carry->default, $fields[0]);
            } else if ($count == 2) {
                if (!property_exists($carry, $fields[0])) {
                    $carry->{$fields[0]} = array();
                }
                array_push($carry->{$fields[0]}, $fields[1]);
            }

            return $carry;
        }, (object)array());

        return $searchFields;
    }

    public function perform() {
        $s = Database\Utils::separator();
        if ($this->params->search) {
            foreach($this->collectionSchema->getFields() as $field) {
                if ($field->isAttribute()) {
                    if ($this->isSearchableField($field->getField())) {
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
                    }
                } else if ($field->isTypeToOne() &&
                  (int)$this->params->searchExtended) {

                    $modelAssociation = $this->getCollectionSchema(
                      $field->getReferencedModelName());
                    $tableNameAssociation = SchemaUtils::findResource(
                      $field->getReferencedModelName())->getTable();
                    $tableAssociation = $this->fieldTableNames[$field->getField()];

                    // NOTICE: Prevent errors on search if the modelAssociation
                    //         is not found.
                    if ($modelAssociation) {
                        foreach($modelAssociation->getFields() as $fieldAssociation) {
                            if (
                                $this->isSearchableField(
                                    $fieldAssociation->getField(),
                                    $tableNameAssociation
                                )
                            ) {
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
    }

    private function isSearchableField($fieldName, $table = 'default') {
        if (!isset($this->searchFields)) {
            return true;
        }

        return in_array($fieldName, $this->searchFields->{$table});
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
