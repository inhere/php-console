<?php declare(strict_types=1);

namespace Inhere\Console\IO\Input;

use Inhere\Console\IO\Input;
use Toolkit\Cli\Flags;

/**
 * Class AloneInput
 *
 * @package Inhere\Console\IO\Input
 */
class AloneInput extends Input
{
    /**
     * @param array $args
     */
    protected function doParse(array $args): void
    {
        [
            $this->args,
            $this->sOpts,
            $this->lOpts
        ] = Flags::parseArgv($args);
    }
}
