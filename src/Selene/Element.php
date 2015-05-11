<?php
namespace Selene;

/** Represents a tag and its attributes. */
class Element {
    public $value;
    public $params;

    public function __construct($value,$params = NULL) {
        $this->value = $value;
        $this->params = $params;
    }
}
