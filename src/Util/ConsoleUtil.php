<?php declare(strict_types=1);

namespace Inhere\Console\Util;

use Inhere\Console\Command;
use function method_exists;

/**
 * class ConsoleUtil
 *
 * @author inhere
 */
class ConsoleUtil
{
    /**
     * @param object $handler
     *
     * @return bool
     */
    public static function isValidCmdObject(object $handler): bool
    {
        if ($handler instanceof Command) {
            return true;
        }

        return method_exists($handler, '__invoke');
    }

}
