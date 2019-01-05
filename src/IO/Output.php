<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/4/23 0023
 * Time: 10:22
 */

namespace Inhere\Console\IO;

use Inhere\Console\Component\Style\Style;
use Inhere\Console\Traits\FormatOutputAwareTrait;
use Inhere\Console\Util\Show;
use Toolkit\Cli\Cli;

/**
 * Class Output
 * @package Inhere\Console\IO
 */
class Output implements OutputInterface
{
    use FormatOutputAwareTrait;

    /**
     * 正常输出流
     * Property outStream.
     */
    protected $outputStream = \STDOUT;

    /**
     * 错误输出流
     * Property errorStream.
     */
    protected $errorStream = \STDERR;

    /**
     * 控制台窗口(字体/背景)颜色添加处理
     * window colors
     * @var Style
     */
    protected $style;

    /**
     * Output constructor.
     * @param null|resource $outputStream
     */
    public function __construct($outputStream = null)
    {
        if ($outputStream) {
            $this->outputStream = $outputStream;
        }

        $this->getStyle();
    }

    /***************************************************************************
     * Output buffer
     ***************************************************************************/

    /**
     * start buffering
     */
    public function startBuffer()
    {
        Show::startBuffer();
    }

    /**
     * clear buffering
     */
    public function clearBuffer()
    {
        Show::clearBuffer();
    }

    /**
     * stop buffering and flush buffer text
     * {@inheritdoc}
     * @see Show::stopBuffer()
     */
    public function stopBuffer(bool $flush = true, $nl = false, $quit = false, array $opts = [])
    {
        Show::stopBuffer($flush, $nl, $quit, $opts);
    }

    /**
     * stop buffering and flush buffer text
     * {@inheritdoc}
     */
    public function flush(bool $nl = false, $quit = false, array $opts = [])
    {
        $this->stopBuffer(true, $nl, $quit, $opts);
    }

    /***************************************************************************
     * Output Message
     ***************************************************************************/

    /**
     * 读取输入信息
     * @param  string $question 若不为空，则先输出文本
     * @param  bool   $nl true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public function read($question = null, $nl = false): string
    {
        if ($question) {
            $this->write($question, $nl);
        }

        return trim(fgets(\STDIN));
    }

    /**
     * Write a message to standard error output stream.
     * @param string  $text
     * @param boolean $nl True (default) to append a new line at the end of the output string.
     * @return $this
     */
    public function stderr($text = '', $nl = true): self
    {
        $text = $this->getStyle()->format($text);

        fwrite($this->errorStream, $text . ($nl ? "\n" : null));

        return $this;
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
            $this->style = Show::getStyle();
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
     * @param $outStream
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
     * @param $errorStream
     * @return $this
     */
    public function setErrorStream($errorStream): self
    {
        $this->errorStream = $errorStream;

        return $this;
    }
}
