<?php


namespace ZoranWong\Coroutine;


use Exception;

/**
 * crontab 时间格式php解析类(PHP  >= 5.1.0)
 */
class Crontab
{
    /**
     * 任务状态 0开启 1关闭 2任务过期
     * @var integer
     */
    protected $status = 0;
    public $nextTime = 0;
    public $lastTime = 0;
    /**
     * 任务间隔标识 s@1 m@1 h@1 at@00:00
     * @var string
     */
    protected $intValTag = '';

    /**
     * 任务列表
     * @var array
     */
    public $intValDateList = [];

    protected $callable;

    /**
     * 构造函数
     * @param string $intValTag 任务间隔标识 s@1 m@1 h@1 at@00:00
     * @param $callable
     */
    public function __construct($intValTag, $callable)
    {
        $this->intValTag = $intValTag;
        $this->callable = $callable;
    }

    /**
     * 获取任务状态
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 获取任务状态
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;

        if ($this->status == 1) {
            $this->nextTime = $this->lastTime = 0;
            $this->intValDateList = [];
        }
    }

    /**
     * 任务周期校验
     * @return boolean
     * @throws Exception
     */
    public function valid()
    {
        if ($this->status !== 0) {
            return false;
        }

        $this->nextTime = time();
        if(CronParser::checkTime($this->intValTag, $this->nextTime)) {
            if($this->lastTime === 0 ) {
                $this->lastTime = $this->nextTime;
            }else if($this->lastTime < $this->nextTime) {
                return true;
            }
        }
        return false;
    }

    public function __invoke()
    {
        while (!$this->valid()) {
            Scheduler::wait();
            yield;
        }
        $call = $this->callable;
        yield $call(microtime(true) - $this->lastTime);
        $this->lastTime = $this->nextTime;
    }

    /**
     * 解析设定,计算下次运行的时间
     * @return void
     * @throws Exception
     */
    public function calcNextTime()
    {

        $this->lastTime = $this->nextTime;

        if (CronParser::check($this->intValTag) && empty($this->intValDateList)) {
            $this->intValDateList = yield CronParser::formatToDate($this->intValTag, 200);
        }
        if (!empty($this->intValDateList)) {
            $this->nextTime =  array_shift($this->intValDateList);
            return;
        }

        if (strpos($this->intValTag, '@') === false) {
            throw new \Exception("解析错误: [{$this->intValTag}]", 1);
        }

        list($tag, $timer) = explode('@', $this->intValTag);
        $this->lastTime = $this->nextTime;
        // 指定每天运行日期  格式 00:00
        if ($tag == 'at' && strlen($timer) == 5) {
            if (time() >= strtotime($timer)) {
                $this->nextTime = strtotime($timer . " +1day");
            }
            else {
                $this->nextTime = strtotime($timer);
            }
        }

        $timer = intval($timer);
        // 按秒
        if ($tag == 's' && $timer > 0) {
            $this->nextTime = time() + $timer;
        }

        // 按分钟
        if ($tag == 'i' && $timer > 0) {
            $this->nextTime = time() + $timer * 60;
        }

        // 按小时
        if ($tag == 'h' && $timer > 0) {
            $this->nextTime = time() + $timer * 60 * 60;
        }

    }
}