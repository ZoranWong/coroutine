<?php
pcntl_async_signals(true);
$pid = posix_getpid();
echo "--------------------- 启动进程： $pid ------------------\n";
$info = '';
$cid = $pid;
pcntl_signal(SIGTTIN, function ($data) use (&$info, &$cid) {
    var_dump($data);
//    $cid = 0;
    for ($i = 0; $i < 10; $i++) {
//        $cid = pcntl_fork();
        sleep(3);
        echo "--------- $i ------------\n";
    }
//    if($cid)
    echo "------ 请输入 --------\n";
});
$cmp = function ($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? 1 : 0;
};

$a = array(3, 2, 5, 6, 1);

usort($a, $cmp);

foreach ($a as $key => $value) {
    echo "$key: $value\n";
}
//sleep(100000);
//if($cid)
while (true) {
    echo "---------- 主循坏 $pid --------------\n";
//    sleep(10);
}
//function fork1() {
//    $id = pcntl_fork();
//    if($id == 0) {
//        echo "----- 子进程开始工作 1----------\n";
//    }else{
//        echo "------- fork1 -------\n";
//    }
//    return $id;
//}
//
//function fork2() {
//    $id = pcntl_fork();
//    if($id == 0) {
//        echo "----- 子进程开始工作 2----------\n";
//    }else{
//        echo "------- fork2 -------\n";
//    }
//    return $id;
//}
//
//$id = fork1();
//
//echo "--------- fork1 00000 $id ---------\n";
//echo "--------------------- 进程： ".posix_getpid()." ------------------\n";
//if($id !== 0)
//    fork2();