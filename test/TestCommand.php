<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\ConsoleTest;

use Inhere\Console\Command;
use Inhere\Console\Handler\CommandWrapper;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

/**
 * Class TestCommand
 *
 * @package Inhere\ConsoleTest
 */
class TestCommand extends Command
{
    protected static string $name = 'test1';

    protected static string $desc = 'command description message';

    protected function subCommands(): array
    {
        return [
            CommandWrapper::new(static function () {
                return __METHOD__;
            })->withConfig([
               'name' => 'sub1',
               'desc' => 'desc for sub1 in test1',
            ]),
        ];
    }

    /**
     * do execute command
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return mixed
     */
    protected function execute(Input $input, Output $output): mixed
    {
        return __METHOD__;
    }
}
