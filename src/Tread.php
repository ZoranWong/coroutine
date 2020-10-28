<?php


namespace ZoranWong\Coroutine;


use Generator;

class Tread extends Coroutine implements Runnable
{
    const STATE_NEW = 0;
    const STATE_READY = 1;
    const STATE_RUNNING = 2;
    const STATE_BLOCKING = 3;
    const STATE_DEAD = 4;

    protected $state = 0;
    protected $sleep = 0;
    public function __construct()
    {
        $this->state = self::STATE_NEW;
        parent::__construct($this->generator());
    }

    protected function generator()
    {
        yield $this->run();
    }

    public function run()
    {
        // TODO: Implement run() method.
        if($this->valid()) {
            $this;
        }
    }

    public function start()
    {
        // TODO: Implement start() method.
    }
}