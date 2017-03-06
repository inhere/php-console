<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/4/23 0023
 * Time: 10:22
 */

namespace inhere\console\io;

use inhere\console\utils\Color;
use inhere\console\utils\Interact;

/**
 * Class Output
 * @package inhere\console\io
 */
class Output
{
    /**
     * 正常输出流
     * Property outStream.
     */
    protected $outputStream = STDOUT;

    /**
     * 错误输出流
     * Property errorStream.
     */
    protected $errorStream = STDERR;

    /**
     * 控制台窗口(字体/背景)颜色添加处理
     * window colors
     * @var Color
     */
    protected $color;

/////////////////////////////////////////////////////////////////
/// Output Message
/////////////////////////////////////////////////////////////////

    /**
     * @inheritdoc
     * @see Interact::title()
     */
    public function title($msg, $width = null)
    {
        Interact::title($msg, $width);
    }

    /**
     * @inheritdoc
     * @see Interact::section()
     */
    public function section($msg, $width = null)
    {
        Interact::section($msg, $width);
    }

    /**
     * helpPanel
     * @inheritdoc
     * @see Interact::helpPanel()
     */
    public function helpPanel(
        $usage, array $commands = [], array $options = [], array $examples = [],
        $description = '', $showAfterQuit = true
    ) {
        Interact::helpPanel($usage, $commands, $options, $examples, $description, $showAfterQuit);
    }

    /**
     * @inheritdoc
     * @see Interact::panel()
     */
    public function panel(array $data, $title='Info panel', $borderChar = '*')
    {
        Interact::panel($data, $title, $borderChar);
    }

    /**
     * @inheritdoc
     * @see Interact::table()
     */
    public function table(array $data, $title='Info List', $showBorder = true)
    {
        Interact::table($data, $title, $showBorder);
    }

    /**
     * @param mixed         $messages
     * @param string|null   $type
     * @param string        $style
     * @param int|boolean   $quit  If is int, setting it is exit code.
     */
    public function block($messages, $type = 'INFO', $style='info', $quit = false)
    {
        $messages = is_array($messages) ? array_values($messages) : array($messages);

        // add type
        if (null !== $type) {
            $messages[0] = sprintf('[%s] %s', strtoupper($type), $messages[0]);
        }

        $text = implode(PHP_EOL, $messages);

        if (is_string($style) && $this->getColor()->hasStyle($style)) {
            $text = "<{$style}>{$text}</{$style}>";
        }

        $this->write($text, true, $quit);
    }
    public function primary($messages, $quit = false)
    {
        $this->block($messages, 'IMPORTANT', 'primary', $quit);
    }
    public function success($messages, $quit = false)
    {
        $this->block($messages, 'SUCCESS', 'success', $quit);
    }
    public function info($messages, $quit = false)
    {
        $this->block($messages, 'INFO', 'info', $quit);
    }
    public function warning($messages, $quit = false)
    {
        $this->block($messages, 'WARNING', 'warning', $quit);
    }
    public function danger($messages, $quit = false)
    {
        $this->block($messages, 'DANGER', 'danger', $quit);
    }
    public function error($messages, $quit = false)
    {
        $this->block($messages, 'ERROR', 'error', $quit);
    }
    public function notice($messages, $quit = false)
    {
        $this->block($messages, 'NOTICE', 'comment', $quit);
    }

    /**
     * 读取输入信息
     * @param  string $message  若不为空，则先输出文本
     * @param  bool   $nl       true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public function read($message = null, $nl = false)
    {
        if ( $message ) {
            $this->write($message, $nl);
        }

        return trim(fgets(STDIN));
    }

    /**
     * Write a message to standard output stream.
     * @param  mixed       $messages  Output message
     * @param  bool        $nl        true 会添加换行符 false 原样输出，不添加换行符
     * @param  int|boolean $quit      If is int, setting it is exit code.
     * @return static
     */
    public function write($messages = '', $nl = true, $quit = false)
    {
        if ( is_array($messages) ) {
            $messages = implode( $nl ? PHP_EOL : '', $messages );
        }

        $messages = $this->getColor()->format($messages);

        if (false === @fwrite($this->outputStream, $messages . ($nl ? PHP_EOL : ''))) {
            // should never happen
            throw new \RuntimeException('Unable to write output.');
        }

        if ( is_int($quit) || true === $quit) {
            $code = true === $quit ? 0 : $quit;
            exit($code);
        }

        fflush($this->outputStream);

        return $this;
    }

    /**
     * Write a message to standard error output stream.
     * @param string  $text
     * @param boolean $nl True (default) to append a new line at the end of the output string.
     * @return $this
     */
    public function err($text = '', $nl = true)
    {
        $text = $this->getColor()->format($text);

        fwrite($this->errorStream, $text . ($nl ? "\n" : null));

        return $this;
    }

/////////////////////////////////////////////////////////////////
/// Getter/Setter
/////////////////////////////////////////////////////////////////

    /**
     * @return Color
     */
    public function getColor()
    {
        if (!$this->color) {
            $this->color = new Color;
        }

        return $this->color;
    }

    /**
     * @return bool
     */
    public function supportColor()
    {
        return ConsoleHelper::isSupportColor();
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
    public function setOutputStream($outStream)
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
    public function setErrorStream($errorStream)
    {
        $this->errorStream = $errorStream;

        return $this;
    }
}
