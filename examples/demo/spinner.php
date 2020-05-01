<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/9/6
 * Time: 下午5:15
 */

class Spinner
{
    /** @var int $speed ms */
    public $speed = 100;

    public static function create($speed)
    {
    }
}

Swoole\Runtime::enableCoroutine();

function spinner()
{
    $chars = '-\|/';
    $index = 0;

    yield function () use ($chars, $index) {
        while (1) {
            printf("\x0D\x1B[2K %s handling ...", $chars[$index]);

            if ($index+1 === mb_strlen($chars)) {
                $index = 0;
            } else {
                $index++;
            }
        }
    };
}

$y = spinner();
// $y->rewind();
sleep(4);
$y->send(false);
