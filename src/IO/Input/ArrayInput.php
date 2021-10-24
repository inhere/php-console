<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO\Input;

use Inhere\Console\IO\Input;
use Toolkit\Cli\Flags;

/**
 * Class ArrayInput
 *
 * @package Inhere\Console\IO\Input
 */
class ArrayInput extends Input
{
    /**
     * Input constructor.
     *
     * @param null|array $args
     */
    public function __construct(array $args = null)
    {
        parent::__construct($args);
    }
}
