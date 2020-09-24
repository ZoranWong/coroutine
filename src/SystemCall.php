<?php


namespace ZoranWong\Coroutine;


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
}