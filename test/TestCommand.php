<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-31
 * Time: 02:07
 */

namespace Inhere\ConsoleTest;

use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

/**
 * Class TestCommand
 *
 * @package Inhere\ConsoleTest
 */
class TestCommand extends Command
{
    protected static $name = 'test1';

    protected static $description = 'command description message';

    /**
     * do execute command
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return int|mixed
     */
    protected function execute($input, $output)
    {
        return __METHOD__;
    }
}
