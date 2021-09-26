<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console;

/**
 * class ConsoleConst
 */
class ConsoleConst
{
    public const CMD_PATH_MAX_LEN = 64;

    public const CMD_NAME_MAX_LEN = 48;

    public const REGEX_CMD_PATH = '/^[a-zA-Z][\w:-]+$/';

    public const REGEX_CMD_NAME = '/^[a-zA-z][\w-]+$/';
}
