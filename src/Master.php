<?php


namespace ZoranWong\Coroutine;


class Master extends Process
{
    protected $workers = null;
    protected $workerCount = 0;
    protected $workerMaxNum = 0;
    protected $configure = [];

    protected function __construct()
    {
        $this->workers = [];
    }

    public function config(array $config)
    {
        $this->configure = $config;
    }

    protected function init()
    {
        $this->workerMaxNum = $this->configure['worker_num'];
    }

    public function fork()
    {

    }
}