<?php


class HelloWorld
{
    public static function main()
    {
        echo "Hello world !\n";
        yield \ZoranWong\Coroutine\timeout(1000);
        echo "wait for 1 s!\n";
    }
}