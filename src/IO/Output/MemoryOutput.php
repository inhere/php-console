<?php declare(strict_types=1);

namespace Inhere\Console\IO\Output;

use function fopen;

/**
 * Class BufferOutput
 *
 * @package Inhere\Console\IO\Output
 */
class MemoryOutput extends StreamOutput
{
    public function __construct()
    {
        parent::__construct(fopen('php://memory', 'rwb'));
    }

    public function getBuffer(): string
    {
        return '';
    }
}
