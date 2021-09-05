<?php declare(strict_types=1);


namespace Inhere\Console\IO\Output;

use Inhere\Console\IO\AbstractOutput;
use function strlen;
use const PHP_EOL;

/**
 * Class BufferedOutput
 * @package Inhere\Console\IO\Output
 */
class BufferedOutput extends AbstractOutput
{
    /**
     * @var string
     */
    private $buffer = '';

    /**
     * @param bool $reset
     *
     * @return string
     */
    public function fetch(bool $reset = true): string
    {
        $str = $this->buffer;

        if ($reset) {
            $this->buffer = '';
        }

        return $str;
    }

    public function __toString(): string
    {
        return $this->fetch();
    }

    /**
     * @param string $content
     *
     * @return int
     */
    public function write(string $content): int
    {
        $this->buffer .= $content;
        return strlen($content);
    }

    /**
     * @param string $content
     *
     * @return int
     */
    public function writeln(string $content): int
    {
        $this->buffer .= $content . PHP_EOL;

        return strlen($content) + 1;
    }

    public function reset(): void
    {
        $this->buffer = '';
    }
}