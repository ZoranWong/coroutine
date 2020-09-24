<?php


namespace ZoranWong\Coroutine;


use Generator;
use SplQueue;

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

    public function killTask($tid) {
        if (!isset($this->taskMap[$tid])) {
            return false;
        }
        unset($this->taskMap[$tid]);
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

    public function schedule(Task $task) {
        $this->taskQueue->enqueue($task);
    }

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
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }
}