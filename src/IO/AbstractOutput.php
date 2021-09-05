<?php declare(strict_types=1);


namespace Inhere\Console\IO;

use Inhere\Console\Contract\OutputInterface;

/**
 * Class AbstractOutput
 * @package Inhere\Console\IO
 */
abstract class AbstractOutput implements OutputInterface
{
    /**
     * @return bool
     */
    public function isInteractive(): bool
    {
        return false;
    }
}
