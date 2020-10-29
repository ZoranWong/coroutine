<?php


namespace ZoranWong\Coroutine;


use Generator;

//协程
class CTread extends Coroutine implements Runnable
{
    const STATE_NEW = 0;
    const STATE_READY = 1;
    const STATE_RUNNING = 2;
    const STATE_BLOCKING = 3;
    const STATE_DEAD = 4;

    protected $state = 0;
    protected $runnable = null;
    protected $started = false;
    protected $interrupt = false;

    public function __construct(Runnable $runnable = null, string $name = '')
    {
        $this->state = self::STATE_NEW;
        $this->runnable = $runnable ? $runnable : $this;
        $name = $name ? $name : uniqid(get_class($this->runnable));
        parent::__construct($this->generator(), $name);
    }

    /**
     * @return Generator
     * */
    protected function generator()
    {
        yield $this->runnable->run();
    }

    public function run()
    {

    }


    public function checkState()
    {
        if (!$this->isFinished()) {
            if ($this->interrupt) {
                $this->state = self::STATE_BLOCKING;
            } elseif ($this->started) {
                $this->state = self::STATE_RUNNING;
            } else {
                $this->state = self::STATE_READY;
            }
        } else {
            $this->state = self::STATE_DEAD;
        }
    }

    public function ready()
    {
        if ($this->valid()) {
            $this->state = self::STATE_RUNNING;
        } else {
            $this->state = self::STATE_DEAD;
        }
    }

    public function block()
    {
        if ($this->valid()) {
            if ($this->interrupt)
                $this->state = self::STATE_BLOCKING;
        } else {
            $this->state = self::STATE_DEAD;
        }
    }

    public function start()
    {
        if ($this->started) {
            if ($this->valid()) {
                $this->state = self::STATE_RUNNING;
                $this->started = true;
            } else {
                $this->state = self::STATE_DEAD;
                $this->started = true;
            }
        }
    }
}