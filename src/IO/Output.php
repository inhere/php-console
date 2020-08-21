<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/4/23 0023
 * Time: 10:22
 */

namespace Inhere\Console\IO;

use Inhere\Console\Contract\OutputInterface;
use Toolkit\Cli\Style;
use Inhere\Console\Console;
use Inhere\Console\Concern\FormatOutputAwareTrait;
use Toolkit\Cli\Cli;

/**
 * Class Output
 *
 * @package Inhere\Console\IO
 */
class Output implements OutputInterface
{
    use FormatOutputAwareTrait;

    /**
     * Normal output stream. Default is STDOUT
     *
     * @var resource
     */
    protected $outputStream;

    /**
     * Error output stream. Default is STDERR
     *
     * @var resource
     */
    protected $errorStream;

    /**
     * 控制台窗口(字体/背景)颜色添加处理
     * window colors
     *
     * @var Style
     */
    protected $style;

    /**
     * Output constructor.
     *
     * @param null|resource $outputStream
     */
    public function __construct($outputStream = null)
    {
        if ($outputStream) {
            $this->outputStream = $outputStream;
        } else {
            $this->outputStream = Cli::getOutputStream();
        }

        $this->getStyle();
    }

    public function resetOutputStream(): void
    {
        $this->outputStream = Cli::getOutputStream();
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
     * @param bool  $flush
     * @param bool  $nl
     * @param bool  $quit
     * @param array $opts
     *
     * @see Console::stopBuffer()
     */
    public function stopBuffer(bool $flush = true, $nl = false, $quit = false, array $opts = []): void
    {
        Console::stopBuffer($flush, $nl, $quit, $opts);
    }

    /**
     * stop buffering and flush buffer text
     *
     * @param bool  $nl
     * @param bool  $quit
     * @param array $opts
     */
    public function flush(bool $nl = false, $quit = false, array $opts = []): void
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
     * @param bool   $nl       true 会添加换行符 false 原样输出，不添加换行符
     *
     * @return string
     */
    public function read(string $question = '', bool $nl = false): string
    {
        return Console::read($question, $nl);
    }

    /**
     * Write a message to standard error output stream.
     *
     * @param string  $text
     * @param boolean $nl True (default) to append a new line at the end of the output string.
     *
     * @return int
     */
    public function stderr(string $text = '', $nl = true): int
    {
        return Console::write($text, $nl, [
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
        if (!$this->style) {
            $this->style = Style::instance();
        }

        return $this->style;
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
        return $this->outputStream;
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
        $this->outputStream = $outStream;

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
