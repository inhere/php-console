<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

class Spinner
{
    /** @var int $speed ms */
    public int $speed = 100;

    public static function create($speed): void
    {
    }
}

Swoole\Runtime::enableCoroutine();

function spinner(): Generator
{
    $chars = '-\|/';
    $index = 0;

    yield static function () use ($chars, $index): void {
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
