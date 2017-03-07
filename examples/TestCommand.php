<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 18:58
 */

use inhere\console\Command;

/**
 * Class Test
 * @package app\console\commands
 */
class TestCommand extends Command
{
    public function execute()
    {
        $this->output->write('hello, this in ' . __METHOD__);

        $this->output->panel($_SERVER, 'Server information', '');
    }
}
