<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-01-01
 * Time: 13:41
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
