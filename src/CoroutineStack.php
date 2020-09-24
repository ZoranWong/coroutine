<?php


namespace ZoranWong\Coroutine;

use Generator;
use SplStack;

class CoroutineStack
{
    protected $stack = null;
    /**
     * @var Generator
     */
    private $generator = null;
    /**
     * @var Task
     */
    private $task;
    /**
     * @var Scheduler
     */
    private $scheduler;


    public function __construct(Generator $generator, Task $task, Scheduler $scheduler)
    {
        $this->stack = new SplStack();
        $this->generator = $generator;

        $this->task = $task;
        $this->scheduler = $scheduler;
    }

    public function __invoke()
    {
        // TODO: Implement __invoke() method.
        if($this->generator){
            $this->stack->push($this->generator);
            $send = null;
            /**@var Generator $gen*/
            while ($this->stack->count() > 0) {
                $gen = $this->stack->pop();
                $value = $gen->current();
                if($value instanceof Generator) {
                    $this->stack->push($gen);
                    $this->stack->push($value);
                    continue;
                }
                if (!$gen->valid()) {//如果当前协程执行完了或者当前协程返回的值是CoroutineReturnValue对象（即该值是要返回给父协程的），则结束当前协程的执行
                    if ($this->stack->isEmpty()) {//如果栈空了，那么整个协程栈执行完毕
                        $gen->send($send);
                        return;
                    }
                    $isReturnValue = $value instanceof CoroutineReturnValue;
                    $send = $isReturnValue ? $value->getValue() : $gen->getReturn();
                    $gen = $this->stack->pop();
                    $gen->send($send);
                    $this->stack->push($gen);
                    continue;
                }
                $this->stack->push($gen);
                $gen->send(yield $gen->key() => $value);
            }
        }else{
            yield;
        }
    }
}