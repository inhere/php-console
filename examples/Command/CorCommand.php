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
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\Helper;

/**
 * Class CorCommand
 * @package Inhere\Console\Examples\Command
 */
class CorCommand extends Command
{
    protected static string $name = 'cor';

    protected static string $desc = 'a coroutine test command';

    protected static bool $coroutine = true;

    /**
     * @return array
     */
    public static function aliases(): array
    {
        return ['co', 'coro'];
    }

    /**
     * do execute
     * @param  Input $input
     * @param  Output $output
     */
    protected function execute(Input $input, Output $output): void
    {
        $output->aList([
            'support coroutine?' => Helper::isSupportCoroutine() ? 'Y' : 'N',
            'open coroutine running?' => self::isCoroutine() ? 'Y' : 'N',
            'running in coroutine?' => Helper::inCoroutine() ? 'Y' : 'N',
        ], 'some information');
    }
}
