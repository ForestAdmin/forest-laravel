<?php

namespace ForestAdmin\ForestLaravel\Model;

use Illuminate\Support\Facades\Config;
use ForestAdmin\ForestLaravel\Logger;
use ForestAdmin\Liana\Exception\RelationshipNotFoundException;

class Collection {

    protected $name;
    protected $fields;
    protected $actions;

    public function __construct($name, $nameOld, $entityClassName, $identifier, $fields,
      $actions = null) {
        $this->setName($name);
        // TODO: Remove nameOld attribute once the lianas versions older than 0.1.4 are minority.
        $this->setNameOld($nameOld);
        $this->setFields($fields);
        $this->setActions();
    }

    // TODO: Remove nameOld attribute once the lianas versions older than 0.1.4 are minority.
    public function setNameOld($nameOld) {
        $this->nameOld = $nameOld;
    }

    // TODO: Remove nameOld attribute once the lianas versions older than 0.1.4 are minority.
    public function getNameOld() {
        return $this->nameOld;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function setActions() {
        $this->actions = array();
        $collectionName = $this->getName();
        $collectionNameOld = $this->getNameOld();
        $collectionActions = $this->getActionsFromConfig($collectionName, $collectionNameOld);

        if (!is_null($collectionActions)) {
            foreach($collectionActions as $action) {
                $this->actions[] = new Action($this, $action);
            }
        }
    }

    private function getActionsFromConfig($collectionName, $collectionNameOld) {
        $actions = null;
        $oldForestLianaApiActions = Config::get('forest.actions');

        if (!is_null($oldForestLianaApiActions)) {
            if (array_key_exists($collectionName, $oldForestLianaApiActions)) {
                $collectionActions = $oldForestLianaApiActions[$collectionName];
                if (!is_null($collectionActions)) {
                    $actions = $collectionActions;
                }
            // TODO: Remove nameOld attribute once the lianas versions older than 0.1.4 are
            //       minority.
            } else if (array_key_exists($collectionNameOld, $oldForestLianaApiActions)) {
                Logger::warning('DEPRECATION WARNING: Collection names are now based on the '.
                    'models names. Please rename the collection "'.$collectionNameOld.'" of your '.
                    'Forest customisation in "'.$collectionName.'".');
                $collectionActions = $oldForestLianaApiActions[$collectionNameOld];
                if (!is_null($collectionActions)) {
                    $actions = $collectionActions;
                }
            }
        }

        $collectionsConfig = Config::get('forest.collection');

        if (!is_null($collectionsConfig) && array_key_exists($collectionName, $collectionsConfig)) {
            $collectionConfig = $collectionsConfig[$collectionName];

            if (!is_null($collectionConfig) && array_key_exists('actions', $collectionConfig)) {
                $collectionActions = $collectionConfig['actions'];

                if (!is_null($collectionActions)) {
                    if (is_null($actions)) {
                        $actions = $collectionActions;
                    } else {
                        $actions = array_merge($actions, $collectionActions);
                    }
                }
            }
        }

        return $actions;
    }

    public function getActions() {
        return $this->actions;
    }

    public function setFields($fields) {
        $this->fields = array();

        foreach($fields as $field) {
            $this->fields[$field->getField()] = $field;
        }
    }

    public function getFields() {
        return $this->fields;
    }

    public function getFieldNamesToOne() {
        $fields = array();

        foreach($this->getFields() as $field) {
            if ($field->isTypeToOne()) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function hasField($name) {
        return array_key_exists($name, $this->fields);
    }

    public function getField($name) {
        if($this->hasField($name)) {
            return $this->fields[$name];
        }

        throw new FieldNotFoundException($name,
          array_keys($this->relationships));
    }

    public function convertForApimap() {
        $fields = array();
        foreach($this->getFields() as $field) {
            $fields[] = $field->toArray();
        }
        $this->fields = $fields;
    }
}
