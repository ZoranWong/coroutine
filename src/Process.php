<?php


namespace ZoranWong\Coroutine;


abstract class Process
{
    protected $pid = null;
    protected $ppid = null;
    protected $status = 0;
    protected $runningTime = 0;
    protected $startAt = null;
    protected $endAt = null;
    protected $restartAt = null;
}