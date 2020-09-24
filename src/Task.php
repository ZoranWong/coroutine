<?php


namespace ZoranWong\Coroutine;

use Generator;

class Task {
    protected $taskId;
    protected $coroutine;
    protected $sendValue = null;
    protected $beforeFirstYield = true;
    public function __construct($taskId, Generator $generator, Scheduler $scheduler) {
        $this->taskId = $taskId;
        $coroutine = new CoroutineStack($generator, $this, $scheduler);
        $this->coroutine = $coroutine();
    }
    public function getTaskId() {
        return $this->taskId;
    }

    public function setSendValue($sendValue) {
        $this->sendValue = new CoroutineReturnValue($sendValue);
    }

    public function run() {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        } else {
            if($this->sendValue instanceof CoroutineReturnValue) {
                return $this->coroutine->send($this->sendValue->getValue());
            }
            return $this->coroutine->send($this->sendValue);
        }
    }
    public function isFinished() {
        return !$this->coroutine->valid();
    }
}