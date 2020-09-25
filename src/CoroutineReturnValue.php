<?php


namespace ZoranWong\Coroutine;

/**
 * @Class CoroutineReturnValue 协程堆栈返回数据
 * */
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