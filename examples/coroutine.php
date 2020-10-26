<?php

use function ZoranWong\Coroutine\coroutine;
use function ZoranWong\Coroutine\cron;
use function ZoranWong\Coroutine\currentTask;
use function ZoranWong\Coroutine\run;
use function ZoranWong\Coroutine\timeout;
use function ZoranWong\Coroutine\timer;

include "../vendor/autoload.php";

coroutine(function () {
    $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

    if(socket_bind($socket, '127.0.0.1')) {
        echo "----------- success to bind -----\n";
        if(socket_connect($socket, 'mysql', 3306)) {
            echo "------- connented --------\n";
            socket_set_nonblock($socket);

            socket_close($socket);
        }
    }else{
        echo "--------- failed to bind ---------\n";
    }


    yield;
    $task = yield currentTask();
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

timer(function ($timeout) {
    yield;
    echo "--------------- 测试定时器，延迟 5 秒 耗时：{$timeout}-----------\n";
}, 5, true);

timer(function ($timeout) {
    yield;
    echo "--------------- 测试定时器，延迟 3 秒耗时：{$timeout} -----------\n";
    yield timeout(3);
    echo "=============== 延迟  3  秒 ==========\n";
}, 3);

cron(function ($timeout) {
    echo "--------------- 定时任务，每 5 秒 耗时：{$timeout}-----------\n";
}, 5);

cron(function ($timeout) {
    echo "--------------- 定时任务，每 3 秒 耗时：{$timeout}-----------\n";
}, 3);

cron(function ($timeout) {
    echo "--------------- 定时任务，每 3 秒 , 加一个 耗时：{$timeout}-----------\n";
}, 3);

cron(function ($timeout) {
    echo "--------------- 定时任务，* * * * * *; 耗时：{$timeout}-----------\n";
}, "* * * * * *");
//
cron(function ($timeout) {
    $date = date('Y-m-d H:i:s');
    echo "--------------- 定时任务，10 * * * * * ; 耗时：{$timeout}; {$date}-----------\n";
}, "*/5 * * * * *");



run();
