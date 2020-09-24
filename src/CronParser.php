<?php


namespace ZoranWong\Coroutine;


/**
 * crontab格式解析工具类
 * @author jlb <497012571@qq.com>
 */
class CronParser
{

    protected static $tags = [];

    protected static $weekMap = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];


    public static function checkTime($cron, $time) {
        $formatTime = self::formatTimestamp($time);
        $formatCron = self::formatCrontab($cron);
        return self::formatCheck($formatTime, $formatCron);
    }

    /**
     * 检查crontab格式是否支持
     * @param string $cronStr
     * @param bool $checkCount
     * @return boolean true|false
     */
    public static function check($cronStr, $checkCount = true)
    {
        $cronStr = trim($cronStr);

        $splitTags = preg_split('#\s+#', $cronStr);
        $tagCount = count($splitTags);
        if ($checkCount &&  4 < $tagCount && $checkCount < 7) {
            return false;
        }

        foreach ($splitTags as $tag) {
            $r = '#^\*(\/\d+)?|\d+([\-\/]\d+(\/\d+)?)?(,\d+([\-\/]\d+(\/\d+)?)?)*$#';
            if (preg_match($r, $tag) == false) {
                return false;
            }
        }
        return true;
    }

    /**
     * 格式化crontab格式字符串
     * @param $cronStr
     * @param int $maxSize 设置返回符合条件的时间数量, 默认为1
     * @return \Generator 返回符合格式的时间
     * @throws \Exception
     */
    public static function formatToDate($cronStr, $maxSize = 1)
    {
        $crons = self::formatCrontab($cronStr);

        $crons['week'] = array_map(function($item){
            return static::$weekMap[$item];
        }, $crons['week']);
        return yield self::getDateList($crons, $maxSize);
    }

    public static function formatCrontab($cronStr) {
        if (!static::check($cronStr)) {
            throw new \Exception("格式错误: $cronStr", 1);
        }

        self::$tags = preg_split('#\s+#', $cronStr);
        if(count(self::$tags) === 5) {
            self::$tags[] = 0;
        }
        $crons = [
            'seconds' => static::parseTag(self::$tags[0], 0, 59), //秒
            'minutes' => static::parseTag(self::$tags[1], 0, 59), //分钟
            'hours'   => static::parseTag(self::$tags[2], 0, 23), //小时
            'day'     => static::parseTag(self::$tags[3], 1, 31), //一个月中的第几天
            'month'   => static::parseTag(self::$tags[4], 1, 12), //月份
            'week'    => static::parseTag(self::$tags[5], 0, 6), // 星期
        ];
        return $crons;
    }

    public static function formatTimestamp($time) {
        return explode('-', date('s-i-G-j-n-w', $time));
    }

    public static function formatCheck(array $formatTime, array $formatCron) {

        $result = (!$formatCron['seconds'] || in_array($formatTime[0], $formatCron['seconds']))
            && (!$formatCron['minutes'] || in_array($formatTime[1], $formatCron['minutes']))
            && (!$formatCron['hours'] || in_array($formatTime[2], $formatCron['hours']))
            && (!$formatCron['day'] || in_array($formatTime[3], $formatCron['day']))
            && (!$formatCron['month'] || in_array($formatTime[4], $formatCron['month']))
            && (!$formatCron['week'] || in_array($formatTime[5], $formatCron['week']));
        return $result;
    }

    /**
     * 递归获取符合格式的日期,直到取到满足$maxSize的数为止
     * @param  array  $crons 解析crontab字符串后的数组
     * @param  interge  $maxSize 最多返回多少数据的时间
     * @param  interge $year  指定年
     * @return array|null 符合条件的日期
     */
    private static function getDateList(array $crons, $maxSize, $year = null)
    {

        $dates = [];

        // 年份基点
        $nowyear = ($year) ? $year : date('Y');

        // 时间基点已当前为准,用于过滤小于当前时间的日期
//        $nowtime = strtotime(date("Y-m-d H:i:s"));

        foreach ($crons['month'] as $month) {
            // 获取此月最大天数
            $maxDay = cal_days_in_month(CAL_GREGORIAN, $month, $nowyear);
            foreach (range(1, $maxDay) as $day) {
                foreach ($crons['hours'] as $hours) {
                    foreach ($crons['minutes'] as $minutes) {
                        foreach ($crons['seconds'] as $second) {
                            $i = mktime($hours, $minutes, $second, $month, $day, $nowyear);
                            if (time() >= $i) {
                                continue;
                            }

                            $date = getdate($i);

                            // 解析是第几天
                            if (self::$tags[3] != '*' && in_array($date['mday'], $crons['day'])) {
                                $dates[] = $i;
                            }

                            // 解析星期几
                            if (self::$tags[5] != '*' && in_array($date['weekday'], $crons['week'])) {
                                $dates[] = $i;
                            }

                            // 天与星期几
                            if (self::$tags[3] == '*' && self::$tags[5] == '*') {
                                $dates[] = $i;
                            }

                            $dates = array_unique($dates);
                            yield;
                            if (isset($dates) && count($dates) == $maxSize) {
                                break 5;
                            }
                        }
                    }
                }
            }
        }

        // 已经递归获取了.但是还是没拿到符合的日期时间,说明指定的时期时间有问题
        if ($year && !count($dates)) {
            return [];
        }

        if (count($dates) != $maxSize) {
            // 向下一年递归
            $dates = array_merge(self::getDateList($crons, $maxSize, ($nowyear + 1)), $dates);
        }

        return $dates;
    }

    /**
     * 解析元素
     * @param string $tag 元素标签
     * @param integer $tmin 最小值
     * @param integer $tmax 最大值
     * @return array
     * @throws \Exception
     */
    private static function parseTag($tag, $tmin, $tmax)
    {
        if ($tag == '*') {
            return range($tmin, $tmax);
        }

        $step = 1;
        $dateList = [];

        // x-x/2 情况
        if (false !== strpos($tag, ',')) {
            $tmp = explode(',', $tag);
            // 处理 xxx-xxx/x,x,x-x
            foreach ($tmp as $t) {
                if (self::checkExp($t)) {// 递归处理
                    $dateList = array_merge(self::parseTag($t, $tmin, $tmax), $dateList);
                } else {
                    $dateList[] = $t;
                }
            }
        }
        else if (false !== strpos($tag, '/') && false !== strpos($tag, '-')) {
            list($number, $mod) = explode('/', $tag);
            list($left, $right) = explode('-', $number);
            if ($left > $right) {
                throw new \Exception("$tag 不支持");
            }
            foreach (range($left, $right) as $n) {
                if ($n % $mod === 0) {
                    $dateList[] = $n;
                }
            }
        }
        else if (false !== strpos($tag, '/')) {
            $tmp = explode('/', $tag);
            $step = isset($tmp[1]) ? $tmp[1] : 1;
            $dateList = range($tmin, $tmax, $step);
        }
        else if (false !== strpos($tag, '-')) {
            list($left, $right) = explode('-', $tag);
            if ($left > $right) {
                throw new \Exception("$tag 不支持");
            }
            $dateList = range($left, $right, $step);
        }
        else {
            $dateList = array($tag);
        }

        // 越界判断
        foreach ($dateList as $num) {
            if ($num < $tmin || $num > $tmax) {
                throw new \Exception('数值越界');
            }
        }

        sort($dateList);

        return array_unique($dateList);

    }

    /**
     * 判断tag是否可再次切割
     * @param $tag
     * @return bool
     */
    private static function checkExp($tag)
    {
        return (false !== strpos($tag, ',')) || (false !== strpos($tag, '-')) || (false !== strpos($tag, '/'));
    }
}
