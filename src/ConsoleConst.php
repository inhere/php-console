<?php declare(strict_types=1);

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
