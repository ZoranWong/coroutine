<?php


namespace ZoranWong\Coroutine;

use Generator;

class Task
{
    const WAIT_RUN = 0;
    const RUNNING = 1;
    const RUN_END = 2;
    protected $taskId;
    protected $coroutine;
    protected $sendValue = null;
    protected $beforeFirstYield = true;
    /**
     * @var int $status 任务状态：0 - 未执行 1 - 执行中 2 - 执行结束
     **/
    protected $status = 0;

    public function __construct($taskId, Generator $generator, Scheduler $scheduler)
    {
        $this->taskId = $taskId;
        $coroutine = new CoroutineStack($generator, $this, $scheduler);
        $this->coroutine = $coroutine();
    }

    public function getTaskId()
    {
        return $this->taskId;
    }

    public function setSendValue($sendValue)
    {
        $this->sendValue = new CoroutineReturnValue($sendValue);
    }

    public function start() {
        $this->status = self::RUNNING;
    }

    public function end() {
        $this->status = self::RUN_END;
    }

    public function isEnd() {
        return $this->status === self::RUN_END;
    }

    public function run()
    {
        if ($this->beforeFirstYield) {
            $this->start();
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        } else {
            if ($this->sendValue instanceof CoroutineReturnValue) {
                return $this->coroutine->send($this->sendValue->getValue());
            }
            return $this->coroutine->send($this->sendValue);
        }
    }

    public function isFinished()
    {
        return !$this->coroutine->valid();
    }
}