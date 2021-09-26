<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Exception;

use InvalidArgumentException;

/**
 * Class PromptException
 *
 * @package Inhere\Console\Exception
 */
class PromptException extends InvalidArgumentException
{
    /**
     * @param string $msg
     * @param int    $code
     *
     * @return static
     */
    public static function new(string $msg, int $code = 0): self
    {
        return new self($msg, $code);
    }
}
