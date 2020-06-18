<?php declare(strict_types=1);

namespace Inhere\Console\IO\Output;

use Inhere\Console\IO\Output;
use function fopen;

/**
 * Class BufferOutput
 *
 * @package Inhere\Console\IO\Output
 */
class BufferOutput extends Output
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
