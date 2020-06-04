<?php declare(strict_types=1);

namespace Inhere\Console\IO\Output;

use Inhere\Console\IO\Output;

/**
 * Class BufferOutput
 *
 * @package Inhere\Console\IO\Output
 */
class BufferOutput extends Output
{
    /**
     * @var array
     */
    private $buffer = [];

    /**
     * @param       $messages
     * @param bool  $nl
     * @param bool  $quit
     * @param array $opts
     *
     * @return int
     */
    public function write($messages, $nl = true, $quit = false, array $opts = []): int
    {
        $this->buffer[] = $messages;

        return 0;
    }
}
