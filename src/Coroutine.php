<?php


namespace ZoranWong\Coroutine;


use Exception;
use Generator;
use Iterator;
use SplStack;
use Throwable;
use Traversable;

class Coroutine implements Iterator
{
    protected $returnValue = null;
    protected $currentGen = null;
    protected $oldGen = null;
    protected $value = null;
    protected $stack = null;
    protected $name = '';
    protected $cid = null;
    public static $maxCId = 0;
    protected $dependencyCoroutines = [];
    protected $scheduler = null;
    /**
     * @var Task $task
     * */
    protected $task = null;
    public function __construct(Generator $generator, Task $task = null, string $name = '')
    {
        $this->task = $task;
        $this->stack = new SplStack();
        $this->name = $name;
        $this->cid = spl_object_hash($this);
        $this->resetStack($generator);
        $this->scheduler = Scheduler::getInstance();
    }

    protected function resetStack($generator)
    {
        while ($this->stack && !$this->stack->isEmpty())
            $this->stack->pop();
        $this->stack->push($generator);
        $this->currentGen = $generator;
        $this->generatorStack();
    }

    public function getId()
    {
        return $this->cid;
    }

    public function generators()
    {
        return $this->stack;
    }

    protected function generatorStack()
    {
        if ($this->currentGen) {
            while (($gen = $this->currentGen->current()) instanceof Generator && $gen !== $this->oldGen) {
                $this->stack->push($gen);
                $this->currentGen = $gen;
            }
            return $gen instanceof Generator ? $this : $gen;
        }
        return $this;
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
            $returnValue = $this->currentGen->getReturn();
            $this->returnValue = $returnValue ? $returnValue : $this->returnValue;
            $this->stack->pop();
            $this->oldGen = $this->currentGen;
            if (!$this->stack->isEmpty())
                $this->currentGen = $this->stack->top();
            return $this->generatorStack();
        } else {
            if ($this->stack->isEmpty())
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
            if (!$this->stack->isEmpty())
                $this->stack->pop();
            if (!$this->stack->isEmpty())
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
        if ($this->currentGen && !$this->currentGen->valid()) {
            $this->returnValue = $this->currentGen->getReturn();
        }
        return $this->returnValue;
    }

    public function isFinished()
    {
        return !$this->valid();
    }

    public function throw(Throwable $throwable)
    {
        if ($this->currentGen)
            return $this->currentGen->throw($throwable);
    }

    public function dependency($id)
    {
        if ($this->hasDependency() && !$this->isDependency($id)) {
            $this->dependencyCoroutines[] = $id;
            $this->scheduler->dependency($id, $this);
        }
    }

    public function isDependency($id)
    {
        return array_search($id, $this->dependencyCoroutines);
    }

    public function hasDependency()
    {
        return empty($this->dependencyCoroutines);
    }

    public function releaseDependency($id) {
        if ($this->hasDependency() && ($idx = array_search($id, $this->dependencyCoroutines))) {
            array_splice($this->dependencyCoroutines, $idx, 1);
        }
    }
}