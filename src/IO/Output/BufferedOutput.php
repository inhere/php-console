<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

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
    private string $buffer = '';

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
     * @param bool $quit
     * @param array $opts
     *
     * @return int
     */
    public function writeln($content, bool $quit = false, array $opts = []): int
    {
        $this->buffer .= $content . PHP_EOL;
        return strlen($content) + 1;
    }

    public function reset(): void
    {
        $this->buffer = '';
    }
}
