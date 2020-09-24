<?php
namespace ZoranWong\Coroutine;

use Closure;
use Generator;

function newTask(Generator $coroutine) {
    return new SystemCall(
        function(Task $task, Scheduler $scheduler) use ($coroutine) {
            $task->setSendValue($scheduler->newTask($coroutine));
            $scheduler->schedule($task);
        }
    );
}


function killTask($tid) {
    return new SystemCall(
        function(Task $task, Scheduler $scheduler) use ($tid) {
            $task->setSendValue($scheduler->killTask($tid));
            $scheduler->schedule($task);
        }
    );
}

function getTaskId() {
    return new SystemCall(function(Task $task, Scheduler $scheduler) {
        $task->setSendValue($task->getTaskId());
        $scheduler->schedule($task);
    });
}

function currentTask() {
    return new SystemCall(function (Task $task, Scheduler $scheduler) {
        $task->setSendValue($task);
        $scheduler->schedule($task);
    });
}

function coroutine(Closure $coroutine) {
    Scheduler::getInstance()->newTask($coroutine());
}


function run() {
    Scheduler::getInstance()->run();
}

function timeout($second = 0) {
    $end = $start = microtime(true);
    while ($end - $start < $second) {
        $end = microtime(true);
        yield;
    }
    return $end - $start;
}


function timer(Closure $closure, $second) {
    $call = function () use ($second, $closure) {
        $timeout = yield timeout($second);
        yield $closure($timeout);
    };
    coroutine($call);
}

function corn(Closure $closure, $time, $isCrontab = false) {
    $call = function () use ($time, $closure, $isCrontab){
        $crontab = null;
        if(!is_numeric($time)) {
            $crontab = new Crontab($time);
        }
        while (1) {
            $timeout = null;
            if(is_numeric($time)) {
                $timeout = yield timeout($time);
            } else {
                $start = $end = microtime(true);

                while(!($crontab->valid())) {
                    $end = microtime(true);
                    yield;
                }
                $timeout = $end - $start;
//                yield $crontab->calcNextTime();
            }
            yield $closure($timeout);
        }
    };
    coroutine($call);
}
