<?php

namespace Inhere\Console\Component\Notify;

use Inhere\Console\Component\NotifyMessage;
use Inhere\Console\Component\Style\Style;
use Toolkit\Cli\Cli;

/**
 * Class DynamicText
 * @package Inhere\Console\Component\Notify
 */
class DynamicText extends NotifyMessage
{
    /**
     * @param string $doneMsg
     * @param string $fixedMsg
     * @return \Generator
     */
    public static function gen(string $doneMsg, string $fixedMsg = ''): \Generator
    {
        $counter  = 0;
        $finished = false;
        // $template = Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r";
        $template = Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D";

        if ($fixedMsg) {
            $template .= Style::instance()->render($fixedMsg);
        }

        $template .= '%s';
        $doneMsg  = $doneMsg ? Style::instance()->render($doneMsg) : '';

        while (true) {
            if ($finished) {
                break;
            }

            $msg = yield;

            if ($msg === false) {
                $msg = $doneMsg ?: '';
                $counter++;
                $finished = true;
            }

            \printf($template, $msg);

            if ($finished) {
                echo "\n";
                break;
            }
        }

        yield $counter;
    }

    public function display(): void
    {

    }
}
