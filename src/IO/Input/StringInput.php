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
use Toolkit\Cli\Util\LineParser;

/**
 * Class StringInput
 *
 * @package Inhere\Console\IO\Input
 */
class StringInput extends Input
{
    /**
     * Input constructor.
     *
     * @param string $line
     */
    public function __construct(string $line)
    {
        $flags = LineParser::parseIt($line);

        parent::__construct($flags);
    }
}
