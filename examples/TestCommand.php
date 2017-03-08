<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 18:58
 */

use inhere\console\Command;
use inhere\console\utils\AnsiCode;

/**
 * Class Test
 * @package app\console\commands
 */
class TestCommand extends Command
{
    public function execute()
    {
        $this->output->write('hello, this in ' . __METHOD__);

        // $this->output->panel($_SERVER, 'Server information', '');

        $this->write('this is a message text.', false);

        sleep(1);
        AnsiCode::make()->cursor(AnsiCode::CURSOR_BACKWARD, 6);

        sleep(1);
        AnsiCode::make()->cursor(AnsiCode::CURSOR_FORWARD, 3);

        sleep(1);
        AnsiCode::make()->cursor(AnsiCode::CURSOR_BACKWARD, 2);

        sleep(2);

        AnsiCode::make()->screen(AnsiCode::CLEAR_LINE, 3);

        $this->write('after 2s scroll down 3 row.');

        sleep(2);

        AnsiCode::make()->screen(AnsiCode::SCROLL_DOWN, 3);

        $this->write('after 3s clear screen.');

        sleep(3);

        AnsiCode::make()->screen(AnsiCode::CLEAR);
    }
}
