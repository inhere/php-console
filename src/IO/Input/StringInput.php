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
     * @param bool   $parsing
     */
    public function __construct(string $line, bool $parsing = true)
    {
        parent::__construct([], false);

        if ($parsing && $line) {
            $flags = LineParser::parseIt($line);

            $this->collectInfo($flags);
            $this->doParse($this->flags);
        }
    }

    /**
     * @param array $args
     */
    protected function doParse(array $args): void
    {
        [
            $this->args,
            $this->sOpts,
            $this->lOpts
        ] = Flags::parseArray($args);

        // find command name
        $this->command = $this->findCommandName();
    }
}
