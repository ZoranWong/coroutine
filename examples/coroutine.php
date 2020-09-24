<?php
include "../vendor/autoload.php";
include "../src/helper.php";
#define await yield;
\ZoranWong\Coroutine\coroutine(function () {
    yield;
    $task = yield \ZoranWong\Coroutine\currentTask();
    echo "------------- 测试 1 {$task->getTaskId()}------------\n";
    yield;
    echo "========== 异步 ========\n";
    if(!file_exists('data')) {
        file_put_contents('data', '');
    }
    $fp = fopen('data','r+');
    stream_set_write_buffer($fp, filesize('coroutine.php'));
    $out = file_get_contents('coroutine.php');
    // 耗时任务可以使用yield操作符分解成多个微任务进行
    for ($i = 0; $i < 1000000000; $i++) {
        yield fwrite($fp, $out);
    }
    fclose($fp);
});

\ZoranWong\Coroutine\timer(function ($timeout) {
    echo "--------------- 测试定时器，延迟 5 秒 耗时：{$timeout}-----------\n";
}, 5);

\ZoranWong\Coroutine\timer(function ($timeout) {
    yield;
    echo "--------------- 测试定时器，延迟 3 秒耗时：{$timeout} -----------\n";
    yield \ZoranWong\Coroutine\timeout(3);
    echo "=============== 延迟  3  秒 ==========\n";
}, 3);

\ZoranWong\Coroutine\corn(function ($timeout) {
    echo "--------------- 定时任务，每 5 秒 耗时：{$timeout}-----------\n";
}, 5);

\ZoranWong\Coroutine\corn(function ($timeout) {
    echo "--------------- 定时任务，每 3 秒 耗时：{$timeout}-----------\n";
}, 3);

\ZoranWong\Coroutine\corn(function ($timeout) {
    echo "--------------- 定时任务，每 3 秒 , 加一个 耗时：{$timeout}-----------\n";
}, 3);

\ZoranWong\Coroutine\corn(function ($timeout) {
    echo "--------------- 定时任务，* * * * * *; 耗时：{$timeout}-----------\n";
}, "* * * * * *");
//
\ZoranWong\Coroutine\corn(function ($timeout) {
    $date = date('Y-m-d H:i:s');
    echo "--------------- 定时任务，10 * * * * * ; 耗时：{$timeout}; {$date}-----------\n";
}, "*/5 * * * * *");


\ZoranWong\Coroutine\run();
