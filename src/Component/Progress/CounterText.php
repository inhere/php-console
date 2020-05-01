<?php declare(strict_types=1);

namespace Inhere\Console\Component\Progress;

use Generator;
use Inhere\Console\Component\NotifyMessage;
use Inhere\Console\Console;
use Toolkit\Cli\Cli;

/**
 * Class CounterText
 *
 * @package Inhere\Console\Component\Progress
 */
class CounterText extends NotifyMessage
{
    /**
     * 与文本进度条相比，没有 total
     *
     * ```php
     *  $total = 120;
     *  $ctt = Show::counterTxt('handling ...', 'handled.');
     *  $this->write('Counter:');
     *  while ($total - 1) {
     *      $ctt->send(1);
     *      usleep(30000);
     *      $total--;
     *  }
     *  // end of the counter.
     *  $ctt->send(-1);
     * ```
     *
     * @param string $msg
     * @param string $doneMsg
     *
     * @return Generator
     */
    public static function gen(string $msg, $doneMsg = ''): Generator
    {
        $counter  = 0;
        $finished = false;

        $tpl = (Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r") . '%d %s';
        $msg = Console::style()->render($msg);

        $doneMsg = $doneMsg ? Console::style()->render($doneMsg) : '';

        while (true) {
            if ($finished) {
                break;
            }

            $step = yield;

            if ((int)$step <= 0) {
                $counter++;
                $finished = true;
                $msg      = $doneMsg ?: $msg;
            } else {
                $counter += $step;
            }

            printf($tpl, $counter, $msg);

            if ($finished) {
                echo "\n";
                break;
            }
        }

        yield false;
    }
}
