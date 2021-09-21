<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 18:58
 */

namespace Inhere\Console\Examples\Command;

use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

/**
 * Class Test
 * @package Inhere\Console\Examples\Command
 */
class TestCommand extends Command
{
    protected static $name = 'test';

    protected static $description = 'this is a test independent command';

    protected function commands(): array
    {
        return [
            'sub' => static function($in, $out) {
                $out->println('hello, this is an sub command of test.');
            },
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
     * @param $input
     * @param Output $output
     */
    public function execute(Input $input, Output $output)
    {
        $output->write('hello, this in ' . __METHOD__);
    }
}
