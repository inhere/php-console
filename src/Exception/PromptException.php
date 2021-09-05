<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-01-01
 * Time: 13:57
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
