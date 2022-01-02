<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO\Output;

use Inhere\Console\IO\Output;
use function implode;
use function is_array;
use function sprintf;
use function strlen;
use const PHP_EOL;

/**
 * Class BufferedOutput
 * @package Inhere\Console\IO\Output
 */
class BufferedOutput extends Output
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

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->fetch();
    }

    public function reset(): void
    {
        $this->buffer = '';
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->fetch();
    }

    /**
     * @param mixed $messages
     * @param bool $nl
     * @param bool $quit
     * @param array $opts
     *
     * @return int
     */
    public function write($messages, $nl = true, $quit = false, array $opts = []): int
    {
        if (is_array($messages)) {
            $str = implode($nl ? PHP_EOL : '', $messages);
        } else {
            $str = (string)$messages;
        }

        if ($nl) {
            $str .= PHP_EOL;
        }

        $this->buffer .= $str;
        return strlen($str);
    }

    /**
     * @param mixed $messages
     * @param bool $quit
     * @param array $opts
     *
     * @return int
     */
    public function writeln($messages, bool $quit = false, array $opts = []): int
    {
        return $this->write($messages, true, $quit, $opts);
    }

    /**
     * Write a message to output with format.
     *
     * @param string $format
     * @param mixed ...$args
     *
     * @return int
     */
    public function writef(string $format, ...$args): int
    {
        return $this->write(sprintf($format, ...$args));
    }

    /**
     * start buffering
     */
    public function startBuffer(): void
    {
    }

    /**
     * clear buffering
     */
    public function clearBuffer(): void
    {
        $this->reset();
    }

    /**
     * stop buffering and flush buffer text
     *
     * @param bool $flush
     * @param bool $nl
     * @param bool $quit
     * @param array{quitCode:int} $opts
     *
     * @see Console::stopBuffer()
     */
    public function stopBuffer(bool $flush = true, bool $nl = false, bool $quit = false, array $opts = []): void
    {

    }

    /**
     * stop buffering and flush buffer text
     *
     * @param bool $nl
     * @param bool $quit
     * @param array $opts
     */
    public function flush(bool $nl = false, bool $quit = false, array $opts = []): void
    {
    }
}
