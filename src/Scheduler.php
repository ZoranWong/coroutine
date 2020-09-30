<?php


namespace ZoranWong\Coroutine;


use Generator;
use SplQueue;
/**
 * 协程任务调度器
 * @Class Scheduler
 * */
class Scheduler {
    protected $maxTaskId = 0;
    protected $taskMap = [];
    protected $taskQueue;
    protected static $singleton = null;
    public static $SLEEP_INTERVAL = 100;

    protected function __construct() {
        $this->taskQueue = new SplQueue();
    }

    public static function wait() {
        mt_srand(microtime(true));
        usleep(mt_rand(self::$SLEEP_INTERVAL, self::$SLEEP_INTERVAL * 10));
    }

    public static function setTaskTimeout($timeout) {
        set_time_limit($timeout);
    }

    public static function getInstance() {
        if(self::$singleton === null) {
            self::$singleton = new static();
        }
        return self::$singleton;
    }

    public function newTask(Generator $coroutine, $prefix = '') {
        $tid = ++ $this->maxTaskId;
        $task = new Task($prefix.$tid, $coroutine, $this);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    /**
     * 杀死指定协程任务
     *
     * @param string|int $tid 任务ID
     * @return bool
     */
    public function killTask($tid) {
        if (!isset($this->taskMap[$tid])) {
            return false;
        }
        /**@var Task $task*/
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

    public function getTask($tid) {
        return isset($this->taskMap[$tid]) ? $this->taskMap[$tid] : null;
    }

    /**
     * 任务入队
     * @param Task $task
     */
    public function schedule(Task $task) {
        $this->taskQueue->enqueue($task);
    }

    /**
     * 调度器执行入口
     * */
    public function run() {
        while (!$this->taskQueue->isEmpty()) {
            self::wait();
            /**@var Task $task*/
            $task = $this->taskQueue->dequeue();
            $retVal  =$task->run();
            if($retVal instanceof SystemCall) {
                $retVal($task, $this);
                continue;
            }elseif ($retVal instanceof Generator) {
                $this->newTask($retVal, 'ret-val-');
            }
            if ($task->isFinished()) {
                $task->end();
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }
}