<?php


namespace ZoranWong\Coroutine;


use Generator;
use InvalidArgumentException;

class SystemCall
{
    protected $callback = null;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(Task $task, Scheduler $scheduler)
    {
        $callback = $this->callback;
        return $callback($task, $scheduler);
    }

    public static function getTaskId()
    {
        return new self(function (Task $task, Scheduler $scheduler) {
            $task->setSendValue($task->getTaskId());
            $scheduler->schedule($task);
        });
    }

    public static function killTask($id)
    {
        return new self(function (Task $task, Scheduler $scheduler) use ($id) {
            if ($scheduler->killTask($id)) {
                $scheduler->schedule($task);
            } else {
                throw new InvalidArgumentException('Invalid task id');
            }
        });
    }

    public static function newTask(Generator $generator)
    {
        return new self(function (Task $task, Scheduler $scheduler) use ($generator) {
            $task->setSendValue($scheduler->newTask($generator));
            $scheduler->schedule($task);
        });
    }

    public static function retVal($value)
    {
        return new CoroutineReturnValue($value);
    }

    public static function wait()
    {
        Scheduler::wait();
    }
}