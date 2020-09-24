<?php


namespace ZoranWong\Coroutine;


class CoroutineReturnValue {
    protected $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->getValue();
    }
}