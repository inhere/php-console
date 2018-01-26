<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-26
 * Time: 17:47
 */

namespace Inhere\Console\Examples\Commands;

use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Utils\Helper;

/**
 * Class CorCommand
 * @package Inhere\Console\Examples\Commands
 */
class CorCommand extends Command
{
    protected static $name = 'cor';
    protected static $description = 'a coroutine test command';
    protected static $coroutine = true;

    /**
     * @return array
     */
    public static function aliases(): array
    {
        return ['coro'];
    }

    /**
     * do execute
     * @param  Input $input
     * @param  Output $output
     */
    protected function execute($input, $output)
    {
        $output->aList([
            'support coroutine?' => Helper::isSupportCoroutine() ? 'Y' : 'N',
            'open coroutine running?' => self::isCoroutine() ? 'Y' : 'N',
            'running in coroutine?' => Helper::inCoroutine() ? 'Y' : 'N',
        ], 'some information');
    }
}
