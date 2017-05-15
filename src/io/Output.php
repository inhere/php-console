<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/4/23 0023
 * Time: 10:22
 */

namespace inhere\console\io;

use inhere\console\Helper;
use inhere\console\style\Style;
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
     * @var Style
     */
    protected $style;

/////////////////////////////////////////////////////////////////
/// Output Message
/////////////////////////////////////////////////////////////////

    /**
     * @inheritdoc
     * @see Interact::title()
     */
    public function title($title, array $opts = [])
    {
        Interact::title($title, $opts);
    }

    /**
     * @inheritdoc
     * @see Interact::section()
     */
    public function section($title, $body, array $opts = [])
    {
        Interact::section($title, $body, $opts);
    }

    /**
     * @inheritdoc
     * @see Interact::aList()
     */
    public function aList($data, $title, array $opts = [])
    {
        Interact::aList($data, $title, $opts);
    }

    /**
     * @inheritdoc
     * @see Interact::multiList()
     */
    public function multiList(array $data, array $opts = [])
    {
        Interact::multiList($data, $opts);
    }

    public function mList(array $data, array $opts = [])
    {
        Interact::multiList($data, $opts);
    }

    /**
     * helpPanel
     * @inheritdoc
     * @see Interact::helpPanel()
     */
    public function helpPanel(array $config, $showAfterQuit = true)
    {
        Interact::helpPanel($config, $showAfterQuit);
    }

    /**
     * @inheritdoc
     * @see Interact::panel()
     */
    public function panel(array $data, $title = 'Info panel', $borderChar = '*')
    {
        Interact::panel($data, $title, $borderChar);
    }

    /**
     * @inheritdoc
     * @see Interact::table()
     */
    public function table(array $data, $title = 'Info List', $showBorder = true)
    {
        Interact::table($data, $title, $showBorder);
    }

    /**
     * @param mixed $messages
     * @param string|null $type
     * @param string $style
     * @param int|boolean $quit If is int, setting it is exit code.
     */
    public function block($messages, $type = 'MESSAGE', $style = Style::NORMAL, $quit = false)
    {
        Interact::block($messages, $type, $style, $quit);
    }

    public function primary($messages, $quit = false)
    {
        $this->block($messages, 'IMPORTANT', Style::PRIMARY, $quit);
    }

    public function success($messages, $quit = false)
    {
        $this->block($messages, 'SUCCESS', Style::SUCCESS, $quit);
    }

    public function info($messages, $quit = false)
    {
        $this->block($messages, 'INFO', Style::INFO, $quit);
    }

    public function warning($messages, $quit = false)
    {
        $this->block($messages, 'WARNING', Style::WARNING, $quit);
    }

    public function danger($messages, $quit = false)
    {
        $this->block($messages, 'DANGER', Style::DANGER, $quit);
    }

    public function error($messages, $quit = false)
    {
        $this->block($messages, 'ERROR', Style::ERROR, $quit);
    }

    public function notice($messages, $quit = false)
    {
        $this->block($messages, 'NOTICE', Style::COMMENT, $quit);
    }

    /**
     * 读取输入信息
     * @param  string $question 若不为空，则先输出文本
     * @param  bool $nl true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public function read($question = null, $nl = false)
    {
        if ($question) {
            $this->write($question, $nl);
        }

        return trim(fgets(STDIN));
    }

    /**
     * Write a message to standard output stream.
     * @param  mixed $messages Output message
     * @param  bool $nl true 会添加换行符 false 原样输出，不添加换行符
     * @param  int|boolean $quit If is int, setting it is exit code.
     * @return static
     */
    public function write($messages = '', $nl = true, $quit = false)
    {
        if (is_array($messages)) {
            $messages = implode($nl ? PHP_EOL : '', $messages);
        }

        $messages = $this->getStyle()->format($messages);

        if (false === @fwrite($this->outputStream, $messages . ($nl ? PHP_EOL : ''))) {
            // should never happen
            throw new \RuntimeException('Unable to write output.');
        }

        if (is_int($quit) || true === $quit) {
            $code = true === $quit ? 0 : $quit;
            exit($code);
        }

        fflush($this->outputStream);

        return $this;
    }

    /**
     * Write a message to standard error output stream.
     * @param string $text
     * @param boolean $nl True (default) to append a new line at the end of the output string.
     * @return $this
     */
    public function stderr($text = '', $nl = true)
    {
        $text = $this->getStyle()->format($text);

        fwrite($this->errorStream, $text . ($nl ? "\n" : null));

        return $this;
    }

/////////////////////////////////////////////////////////////////
/// Getter/Setter
/////////////////////////////////////////////////////////////////

    /**
     * @return Style
     */
    public function getStyle()
    {
        if (!$this->style) {
            $this->style = new Style;
        }

        return $this->style;
    }

    /**
     * @return bool
     */
    public function supportColor()
    {
        return Helper::isSupportColor();
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
