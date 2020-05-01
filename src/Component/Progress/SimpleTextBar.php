<?php declare(strict_types=1);

namespace Inhere\Console\Component\Progress;

use Generator;
use Inhere\Console\Component\NotifyMessage;
use Inhere\Console\Console;
use Toolkit\Cli\Cli;

/**
 * Class SimpleTextBar
 *
 * @package Inhere\Console\Component\Progress
 */
class SimpleTextBar extends NotifyMessage
{
    /**
     * Render a simple text progress bar by 'yield'
     *
     * @param int    $total
     * @param string $msg
     * @param string $doneMsg
     *
     * @return Generator
     */
    public static function gen(int $total, string $msg, string $doneMsg = ''): Generator
    {
        $current  = 0;
        $finished = false;
        $tpl      = (Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r") . "%' 3d%% %s";
        $msg      = Console::style()->render($msg);
        $doneMsg  = $doneMsg ? Console::style()->render($doneMsg) : '';

        while (true) {
            if ($finished) {
                break;
            }

            $step = yield;

            if ((int)$step <= 0) {
                $step = 1;
            }

            $current += $step;
            $percent = ceil(($current / $total) * 100);

            if ($percent >= 100) {
                $percent  = 100;
                $finished = true;
                $msg      = $doneMsg ?: $msg;
            }

            // printf("\r%d%% %s", $percent, $msg);
            // printf("\x0D\x2K %d%% %s", $percent, $msg);
            // printf("\x0D\r%'2d%% %s", $percent, $msg);
            printf($tpl, $percent, $msg);

            if ($finished) {
                echo "\n";
                break;
            }
        }

        yield false;
    }
}
