<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO;

use Inhere\Console\Decorate\FormatOutputAwareTrait;
use Inhere\Console\Console;
use Inhere\Console\IO\Output\StreamOutput;
use Toolkit\Cli\Cli;
use Toolkit\Cli\Style;

/**
 * Class Output
 *
 * @package Inhere\Console\IO
 */
class Output extends StreamOutput
{
    use FormatOutputAwareTrait;

    /**
     * Error output stream. Default is STDERR
     *
     * @var resource
     */
    protected $errorStream;

    /**
     * Output constructor.
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['stream'])) {
            $config['stream'] = Cli::getOutputStream();
        }

        parent::__construct($config);
    }

    /***************************************************************************
     * Output buffer
     ***************************************************************************/

    /**
     * start buffering
     */
    public function startBuffer(): void
    {
        Console::startBuffer();
    }

    /**
     * clear buffering
     */
    public function clearBuffer(): void
    {
        Console::clearBuffer();
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
        Console::stopBuffer($flush, $nl, $quit, $opts);
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
        Console::flushBuffer($nl, $quit, $opts);
    }

    /***************************************************************************
     * Output Message
     ***************************************************************************/

    /**
     * Read input information
     *
     * @param string $question 若不为空，则先输出文本
     * @param bool $nl true 会添加换行符 false 原样输出，不添加换行符
     *
     * @return string
     */
    public function read(string $question = '', bool $nl = false): string
    {
        return Console::read($question, $nl);
    }

    /**
     * Read input information
     *
     * @param string $question
     * @param bool $nl
     *
     * @return string
     */
    public function readln(string $question = '', bool $nl = false): string
    {
        return Console::readln($question, $nl);
    }

    /**
     * Write a message to standard error output stream.
     *
     * @param string $text
     * @param boolean $nl True (default) to append a new line at the end of the output string.
     *
     * @return int
     */
    public function stderr(string $text = '', bool $nl = true): int
    {
        return Console::write($text, $nl, false, [
            'steam' => $this->errorStream,
        ]);
    }

    /***************************************************************************
     * Getter/Setter
     ***************************************************************************/

    /**
     * @return Style
     */
    public function getStyle(): Style
    {
        return Style::global();
    }

    /**
     * @return bool
     */
    public function supportColor(): bool
    {
        return Cli::isSupportColor();
    }

    /**
     * getOutStream
     */
    public function getOutputStream()
    {
        return $this->stream;
    }

    public function resetOutputStream(): void
    {
        $this->stream = Cli::getOutputStream();
    }

    /**
     * setOutStream
     *
     * @param $outStream
     *
     * @return $this
     */
    public function setOutputStream($outStream): self
    {
        $this->stream = $outStream;
        return $this;
    }

    /**
     * Method to get property ErrorStream
     */
    public function getErrorStream()
    {
        return $this->errorStream;
    }

    /**
     * Method to set property errorStream
     *
     * @param $errorStream
     *
     * @return $this
     */
    public function setErrorStream($errorStream): self
    {
        $this->errorStream = $errorStream;
        return $this;
    }
}
