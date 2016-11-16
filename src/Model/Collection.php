<?php

namespace ForestAdmin\ForestLaravel\Model;

use ForestAdmin\Liana\Exception\RelationshipNotFoundException;

class Collection {

    protected $name;
    protected $fields;
    protected $actions;

    public function __construct($name, $entityClassName, $identifier, $fields,
      $actions = null) {
        $this->setName($name);
        $this->setFields($fields);
        $this->setActions($actions);
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function setActions($actions = null) {
        $this->actions = is_null($actions) ? array() : $actions;
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
