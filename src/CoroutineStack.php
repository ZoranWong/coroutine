<?php


namespace ZoranWong\Coroutine;

use Generator;
use SplStack;
/**
 * @Class CoroutineStack 协程堆栈
 * */
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

    /**
     *堆栈构造函数
     * @param Generator $generator 当前协程的栈底（根）协程（生成器）
     * @param Task $task 执行当前协程的任务对象
     * @param Scheduler $scheduler 任务调度器
     */
    public function __construct(Generator $generator, Task $task, Scheduler $scheduler)
    {
        $this->stack = new SplStack();
        $this->generator = $generator;

        $this->task = $task;
        $this->scheduler = $scheduler;
    }

    /**
     * 协程堆栈的自我调用
     * */
    public function __invoke()
    {
        // TODO: Implement __invoke() method.
        if($this->generator){
            $this->stack->push($this->generator);
            $send = null;
            /**@var Generator $gen*/
            while ($this->stack->count() > 0) {
                // 任务结束或者被终止协程堆栈直接返回不再执行
                if($this->task->isEnd()) {
                    return;
                }
                $gen = $this->stack->pop();
                $value = $gen->current();
                //
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
                    // 当前协程执行完成，获取栈顶下一个协程发送当前执行结果
                    $gen = $this->stack->top();
                    $gen->send($send);
                    continue;
                }
                // 向下一个协程发送数据
                $gen->send(yield $gen->key() => $value);
                //协程未执行结束，需要继续遍历下一个协程，将$gen重新压入栈顶部，进入下一个执行周期
                $this->stack->push($gen);
            }
        }else{
            yield;
        }
    }
}