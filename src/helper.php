<?php

namespace ZoranWong\Coroutine;

use Closure;
use Generator;

function newTask(Generator $coroutine)
{
    return new SystemCall(
        function (Task $task, Scheduler $scheduler) use ($coroutine) {
            $task->setSendValue($scheduler->newTask($coroutine));
            $scheduler->schedule($task);
        }
    );
}


function killTask($tid)
{
    return new SystemCall(
        function (Task $task, Scheduler $scheduler) use ($tid) {
            $task->setSendValue($scheduler->killTask($tid));
            $scheduler->schedule($task);
        }
    );
}

function getTaskId()
{
    return new SystemCall(function (Task $task, Scheduler $scheduler) {
        $task->setSendValue($task->getTaskId());
        $scheduler->schedule($task);
    });
}

function currentTask()
{
    return new SystemCall(function (Task $task, Scheduler $scheduler) {
        $task->setSendValue($task);
        $scheduler->schedule($task);
    });
}

function getTask($tid)
{
    return new SystemCall(function (Task $task, Scheduler $scheduler) use ($tid) {
        $t = $scheduler->getTask($tid);
        $task->setSendValue($t);
        $scheduler->schedule($task);
    });
}

function coroutine(Closure $coroutine)
{
    Scheduler::getInstance()->newTask($coroutine);
}


function run()
{
    Scheduler::getInstance()->run();
}

function timeout($second = 0)
{
    $end = $start = microtime(true);
    while ($end - $start < $second) {
        Scheduler::wait();
        $end = microtime(true);
        yield;
    }
    return $end - $start;
}


function timer(Closure $closure, $second, $persistence = false)
{
    $call = function () use ($second, $closure, $persistence) {
        do {
            $timeout = yield timeout($second);
            yield $closure($timeout);
            $id = yield SystemCall::getTaskId();

            if (!$persistence) {
                var_dump($id);
                yield SystemCall::killTask($id);
                break;
            }
        } while (true);
    };
    coroutine($call);
}

function cron(Closure $closure, $time, $isCrontab = false)
{
    $call = function () use ($time, $closure, $isCrontab) {
        while (1) {
            if (is_numeric($time)) {
                $timeout = yield timeout($time);
                yield $closure($timeout);
            } else {
                $crontab = new Crontab($time, $closure);
                yield $crontab();
            }
        }
    };
    coroutine($call);
}

function taskTimeout($timeout)
{
    Scheduler::setTaskTimeout($timeout);
}
