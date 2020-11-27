<?php


namespace ZoranWong\Coroutine;


use Generator;
use SplQueue;

/**
 * 协程任务调度器
 * @Class Scheduler
 * */
class Scheduler
{
    protected $maxTaskId = 0;
    /**
     * @var Task[] $taskMap
     * */
    protected $taskMap = [];
    protected $taskQueue;
    /**
     * @var Scheduler $singleton
     * */
    protected static $singleton = null;
    public static $SLEEP_INTERVAL = 50;
    public static $TASK_INTERVAL_SECOND = 3;
    public static $TASK_RUN_RATE = 0.5;
    protected $blockingQueue = null;
    protected $maxSchedulerTasks = 1024;
    protected $dependencies = [];

    public static $currentTaskId = null;

    /**
     * @var Task $currentTask
     * */
    public static $currentTask = null;

    protected function __construct()
    {
        $this->taskQueue = new SplQueue();
    }

    public static function wait()
    {
        mt_srand(microtime(true));
        $interval = self::$SLEEP_INTERVAL / (self::$singleton->taskNum() + 1);
        $time = mt_rand((int)$interval, (int)($interval * 10));

        if (self::$SLEEP_INTERVAL * 11 / 2 > $time)
            usleep($time);
        else
            time_nanosleep(0, $time * 100);
    }

    public static function setTaskTimeout($timeout)
    {
        set_time_limit($timeout);
    }

    public static function getInstance()
    {
        if (self::$singleton === null) {
            self::$singleton = new static();
        }
        return self::$singleton;
    }

    public function newTask(&$coroutine)
    {
        if ($coroutine instanceof CTread && isset($this->taskMap[$coroutine->getId()])) {
            return $this->taskMap[$coroutine->getId()];
        }
        ++$this->maxTaskId;
        $task = new Task($coroutine, $this);
        $this->taskMap[$task->getTaskId()] = $task;
        return $task;
    }

    public function schedulers()
    {
        $time = microtime(true) * 1000;
        foreach ($this->taskMap as $task) {
            $task->updateWaitTasks($time);
             if (!$task->hasWait()) {
                 $this->schedule($task);
             }
        }
    }

    /**
     * 杀死指定协程任务
     *
     * @param string|int $tid 任务ID
     * @return bool
     */
    public function killTask($tid)
    {
        if (!isset($this->taskMap[$tid])) {
            return false;
        }
        /**@var Task $task */
        $task = $this->taskMap[$tid];
        unset($this->taskMap[$tid]);
        $task->end();
        // This is a bit ugly and could be optimized so it does not have to walk the queue,
        // but assuming that killing tasks is rather rare I won't bother with it now
        foreach ($this->taskQueue as $i => $task) {
            if ($task->getTaskId() === $tid) {
                unset($this->taskQueue[$i]);
                break;
            }
        }
        return true;
    }

    public function getTask($tid)
    {
        return isset($this->taskMap[$tid]) ? $this->taskMap[$tid] : null;
    }

    /**
     * 任务入队
     * @param Task $task
     */
    public function schedule(Task $task)
    {
        $this->taskQueue->enqueue($task);
    }

    /**
     * 调度器执行入口
     * */
    public function run()
    {
        while (count($this->taskMap) > 0) {
            $this->schedulers();
            while (!$this->taskQueue->isEmpty()) {
                /**@var Task $task */
                $task = $this->taskQueue->dequeue();
                self::$currentTask = $task;
                self::$currentTaskId = $task->getTaskId();
                if (!$task->hasWait()) {
                    $retVal = $task->run();
                    if ($retVal instanceof SystemCall) {
                        $retVal($task, $this);
                        continue;
                    }
                }
                if ($task->isFinished()) {
                    $task->end();
                    unset($this->taskMap[$task->getTaskId()]);
                } else {
//                    $this->schedule($task);
                }
            }
        }

    }

    protected function sort(array &$tasks)
    {
        uasort($tasks, $this->prioritySort());
    }

    protected function prioritySort()
    {
        return function (Task $task1, Task $task2) {
            $diffTime = $task2->getLastRuntime() - $task1->getLastRuntime();
            $agvTime = $task2->getAvgRuntime() / $task1->getAvgRuntime();
            $runCount = $task2->getRunCount() / $task1->getRunCount();
            return $task1->getPriority() < $task2->getPriority() ? 1 :
                ($diffTime > self::$TASK_INTERVAL_SECOND ? 1 :
                    ($agvTime < self::$TASK_RUN_RATE ? 1 : ($runCount < 1 ? 1 : -1)));
        };
    }

    public function taskNum()
    {
        return count($this->taskMap);
    }

    public static function join(Task $task, int $end = 0)
    {
        self::$currentTask->waitFor($task, $end);
        self::getInstance()->dependency($task->getTaskId(), self::$currentTask->getTaskId());
    }

    public function dependency($id, $taskId)
    {
        if (!isset($this->dependencies[$id])) {
            $this->dependencies[$id] = [];
        }

        $this->dependencies[$id][] = $taskId;
    }

    /**
     * @param $id
     * @return Task[]
     */
    public function dependencyTasks($id)
    {
        return $this->dependencies[$id] ?? [];
    }

    public static function add(&$coroutine)
    {
        return self::getInstance()->newTask($coroutine);
    }

    public function task($id)
    {
        return isset($this->taskMap[$id]) ? $this->taskMap[$id] : null;
    }
}