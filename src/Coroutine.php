<?php


namespace ZoranWong\Coroutine;


use Exception;
use Generator;
use Iterator;
use SplStack;
use Traversable;

class Coroutine implements Iterator
{
    protected $returnValue = null;
    protected $currentGen = null;
    protected $oldGen = null;
    protected $value = null;
    protected $stack = null;
    protected $name = '';

    public function __construct(Generator $generator)
    {
        $this->stack = new SplStack();
        $this->stack->push($generator);
        $this->currentGen = $generator;
        $this->generatorStack();
    }

    protected function generatorStack()
    {
        if ($this->currentGen) {
            while (($gen = $this->currentGen->current()) instanceof Generator && $gen !== $this->oldGen) {
                $this->stack->push($gen);
                $this->currentGen = $gen;
            }
            return $gen;
        }
        return  null;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        // TODO: Implement getIterator() method.
        return $this->stack;
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        // TODO: Implement current() method.
        if (!$this->valid() && !$this->stack->isEmpty()) {
            $this->stack->pop();
            if (!$this->stack->isEmpty()) {
                $this->currentGen = $this->stack->top();
            }
        }
        return $this->generatorStack();
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        // TODO: Implement next() method.
        if ($this->returnValue === null) {
            $this->currentGen->next();
            $this->nextGen();
        } else {
            $this->send($this->returnValue);
        }
    }

    protected function nextGen()
    {
        if (!$this->currentGen->valid()) {
            $this->returnValue = $this->currentGen->getReturn();
            $this->stack->pop();
            $this->oldGen = $this->currentGen;
            if(!$this->stack->isEmpty())
                $this->currentGen = $this->stack->top();
            return $this->generatorStack();
        }elseif(!$this->stack->isEmpty()){
            $this->stack->push($this->currentGen);
            return $this->generatorStack();
        }
        return null;
    }

    public function send($value)
    {
        $this->returnValue = $value;

        $val = $this->currentGen->send($value);
        $this->nextGen();
        if (!$this->currentGen->valid()) {
            $this->stack->pop();
            $this->currentGen = $this->stack->top();
        }
        return $val;
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        // TODO: Implement key() method.
        return $this->stack->key();
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        // TODO: Implement valid() method.
        return $this->currentGen->valid();
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        // TODO: Implement rewind() method.
        $this->currentGen->rewind();
        $this->current();
    }

    public function getReturn()
    {
        if ($this->stack->isEmpty() && !$this->currentGen->valid()) {
            return $this->currentGen->getReturn();
        }
    }

}