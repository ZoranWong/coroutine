<?php
/**
 * PHP Version 5.6
 *
 * @category BlackStone Team
 * @package ZoranWong/Coroutine
 * @author Zoran Wong
 * @time 2020/09/25 03:28
 * @copyright 2020 BlackStone Team
 * @license MIT
 * @link skyandsunning@gmail.com
 * */

namespace ZoranWong\Coroutine;

use Generator;

/**
 * 协程任务(Task for coroutine)
 *
 * @category BlackStone Team
 * @package ZoranWong\Coroutine
 * @author Zoran Wong <skyandsunning@gmail.com>
 * @license MIT
 * @link skyandsunning@gmail.com
 * */
class Task
{
    const WAIT_RUN = 0;
    const RUNNING = 1;
    const RUN_END = 2;
    /**
     * @var Coroutine $coroutine
     * */
    protected $coroutine;
    protected $sendValue = null;
    protected $beforeFirstYield = true;
    /**
     * @var int $status 任务状态：0 - 未执行 1 - 执行中 2 - 执行结束
     **/
    protected $status = 0;

    protected $scheduler = null;

    protected $runCount = 0;

    protected $lastRuntime = 0;

    protected $totalRuntime = 0;

    protected $avgRuntime = 0;

    protected $priority = 0;
    /**
     * @var Task $parentTask
     * */
    protected $parentTask = null;

    protected $childrenTasks = [];

    public function __construct($coroutine, Scheduler $scheduler)
    {
        $this->coroutine = (!$coroutine instanceOf CTread) ? new CTread($coroutine, 'sys-thread') : $coroutine;
        $this->status = self::WAIT_RUN;
        $this->scheduler = $scheduler;
    }

    public function getTaskId()
    {
        return $this->coroutine->getId();
    }

    public function setSendValue($sendValue)
    {
        $this->coroutine->send($sendValue);
    }

    public function start()
    {
        $this->status = self::RUNNING;
    }

    public function end()
    {
        if($this->parentTask) {
            $this->parentTask->removeChild($this);
        }
        $this->status = self::RUN_END;
    }

    public function isEnd()
    {
        return $this->status === self::RUN_END;
    }

    public function run()
    {
        $start = microtime(true);
        if ($this->coroutine->valid()) {
            if ($this->beforeFirstYield) {
                $this->start();
                $this->beforeFirstYield = false;
                $result = $this->coroutine->current();
            } else {
                $result = $this->coroutine->send($this->sendValue ? $this->sendValue : $this->coroutine->getReturn());
            }
            return $result;
        }

        $end = microtime(true);
        $this->runCount += 1;
        $this->lastRuntime = $end;
        $diff = $end - $start;
        $this->totalRuntime += $diff;
        $this->avgRuntime = $this->totalRuntime / $this->runCount;
        return null;
    }

    public function isFinished()
    {
        return !$this->coroutine->valid();
    }

    public function generators()
    {
        return $this->coroutine->generators();
    }

    /**
     * @return int
     */
    public function getAvgRuntime(): int
    {
        return $this->avgRuntime;
    }

    /**
     * @return int
     */
    public function getLastRuntime(): int
    {
        return $this->lastRuntime;
    }

    /**
     * @return int
     */
    public function getRunCount(): int
    {
        return $this->runCount;
    }


    /**
     * @return int
     */
    public function getTotalRuntime(): int
    {
        return $this->totalRuntime;
    }


    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    public function removeChild(Task $task)
    {
        unset($this->childrenTasks[$task->getTaskId()]);
    }

    public function thread()
    {
        return $this->coroutine;
    }

    public function waitFor()
    {
        $this->parentTask = Scheduler::$currentTask;
        $this->parentTask->addChild($this);
    }

    public function addChild(Task $task)
    {
        $this->childrenTasks[$task->getTaskId()] = $task;
    }
}