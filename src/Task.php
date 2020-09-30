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
        $this->status = self::WAIT_RUN;
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