<?php

namespace ForestAdmin\ForestLaravel\Model;

use Doctrine\DBAL\Types\Type as Type;

class Field {

    protected $field;
    protected $type;
    protected $reference;
    protected $inverseOf;

    public function __construct($fieldName, $type, $reference = null,
      $inverseOf = null, $pivot = null) {
        $this->setField($fieldName);
        $this->setType($type);
        $this->setReference($reference);
        $this->setInverseOf($inverseOf);
    }

    public function setField($field) {
        $this->field = $field;
    }

    public function getField() {
        return $this->field;
    }

    public function setType($type) {
        switch ($type) {
            case Type::INTEGER:
            case Type::SMALLINT:
            case Type::BIGINT:
            case Type::FLOAT:
            case Type::DECIMAL:
                $this->type = 'Number';
                break;
            case Type::STRING:
            case Type::TEXT:
            case Type::GUID:
            case Type::TARRAY:
                $this->type = 'String';
                break;
            case 'json': // NOTICE: Type::JSON raises an error
            case Type::JSON_ARRAY:
                $this->type = 'Json';
                break;
            case Type::BOOLEAN:
                $this->type = 'Boolean';
                break;
            case Type::DATE:
            case Type::DATETIME:
            case Type::DATETIMETZ:
                $this->type = 'Date';
                break;
            default:
                // NOTICE: Array type are set without changes
                $this->type = $type;
        }
    }

    public function getType() {
        return $this->type;
    }

    public function isAttribute() {
        return !$this->isTypeToOne() && !$this->isTypeToMany();
    }

    public function isTypeToMany() {
        return is_array($this->type) && $this->type[0] == 'Number' &&
          $this->getReference();
    }

    public function isTypeToOne() {
        return is_string($this->type) && $this->type == 'Number' &&
          $this->getReference();
    }

    public function setInverseOf($inverseOf) {
        $this->inverseOf = $inverseOf;
    }

    public function getInverseOf() {
        return $this->inverseOf;
    }

    public function setReference($reference) {
        $this->reference = $reference;
    }

    public function getReference() {
        return $this->reference;
    }

    public function getReferencedModelName() {
        $ref = $this->getReferenceElements();

        if (is_array($ref) && count($ref) == 2) {
            return $ref[0];
        }

        return null;
    }

    public function getReferencedField() {
        $ref = $this->getReferenceElements();

        if (is_array($ref) && count($ref) == 2) {
            return $ref[1];
        }

        return null;
    }

    public function getForeignKey() {
        if($this->getPivot()) {
            if(!$this->getPivot()->getIntermediaryTableName()) {
                return $this->getPivot()->getSourceIdentifier();
            }
        }

        return null;
    }

    public function toArray() {
        $ret = array(
            'field' => $this->getField(),
            'type' => $this->getType()
        );
        if($this->getReference()) {
            $ret['reference'] = $this->getReference();
        }
        if($this->getInverseOf()) {
            $ret['inverseOf'] = $this->getInverseOf();
        }

        return $ret;
    }

    protected function getReferenceElements() {
        return $this->getReference() ? explode('.', $this->getReference()) :
          null;
    }
}
