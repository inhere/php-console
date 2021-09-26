<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Contract;

/**
 * Interface FormatterInterface
 *
 * @package Inhere\Console\Contract
 */
interface FormatterInterface
{
    public const FINISHED = -1;

    public const CHAR_SPACE     = ' ';

    public const CHAR_HYPHEN    = '-';

    public const CHAR_UNDERLINE = '_';

    public const CHAR_VERTICAL  = '|';

    public const CHAR_EQUAL     = '=';

    public const CHAR_STAR      = '*';

    public const POS_LEFT   = 'l';

    public const POS_MIDDLE = 'm';

    public const POS_RIGHT  = 'r';

    public function format(): string;
}
