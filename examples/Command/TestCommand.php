<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Examples\Command;

use Inhere\Console\Command;
use Inhere\Console\Handler\CommandWrapper;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

/**
 * Class Test
 * @package Inhere\Console\Examples\Command
 */
class TestCommand extends Command
{
    protected static string $name = 'test';

    protected static string $desc = 'this is a test independent command';

    protected function subCommands(): array
    {
        return [
            'sub' => CommandWrapper::new(static function ($fs, $out): void {
                $out->println('hello, this is an sub command of test.');
            }, [
                'desc' => 'sub command of test command'
            ]),
        ];
    }

    /**
     * test text
     *
     * @usage {name} test message
     * @arguments
     *  arg1        argument description 1
     *  arg2        argument description 2
     *
     * @options
     *  --long,-s   option description 1
     *  --opt       option description 2
     *
     * @param Input $input
     * @param Output $output
     */
    public function execute(Input $input, Output $output): void
    {
        $output->write('hello, this in ' . __METHOD__);
    }
}
