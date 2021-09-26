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
     * @param bool       $parsing
     */
    public function __construct(array $args = null, bool $parsing = true)
    {
        parent::__construct([], false);

        $this->collectInfo($args);

        if ($parsing && $args) {
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
