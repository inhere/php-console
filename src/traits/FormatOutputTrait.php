<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-06-20
 * Time: 15:10
 */

namespace inhere\console\traits;

use inhere\console\style\Style;
use inhere\console\utils\Show;

/**
 * Class FormatOutputTrait
 * @package inhere\console\traits
 */
trait FormatOutputTrait
{
    /**
     * @inheritdoc
     * @see Show::write()
     */
    public function write($messages = '', $nl = true, $quit = false): int
    {
        return Show::write($messages, $nl, $quit, [
            'flush' => true,
            'stream' => $this->outputStream,
        ]);
    }

    /**
     * @inheritdoc
     * @see Show::writeln()
     */
    public function writeln($text, $quit = false, array $opts = [])
    {
        return Show::writeln($text, $quit, $opts);
    }

    /**
     * @inheritdoc
     * @see Show::title()
     */
    public function title($title, array $opts = [])
    {
        Show::title($title, $opts);
    }

    /**
     * @inheritdoc
     * @see Show::section()
     */
    public function section($title, $body, array $opts = [])
    {
        Show::section($title, $body, $opts);
    }

    /**
     * @inheritdoc
     * @see Show::aList()
     */
    public function aList($data, $title = null, array $opts = [])
    {
        Show::aList($data, $title, $opts);
    }

    /**
     * @inheritdoc
     * @see Show::mList()
     */
    public function multiList(array $data, array $opts = [])
    {
        Show::mList($data, $opts);
    }

    /**
     * @inheritdoc
     * @see Show::mList()
     */
    public function mList(array $data, array $opts = [])
    {
        Show::mList($data, $opts);
    }

    /**
     * helpPanel
     * @inheritdoc
     * @see Show::helpPanel()
     */
    public function helpPanel(array $config, $showAfterQuit = true)
    {
        Show::helpPanel($config, $showAfterQuit);
    }

    /**
     * @inheritdoc
     * @see Show::panel()
     */
    public function panel(array $data, $title = 'Info panel', $borderChar = '*')
    {
        Show::panel($data, $title, $borderChar);
    }

    /**
     * @inheritdoc
     * @see Show::table()
     */
    public function table(array $data, $title = 'Info List', $showBorder = true)
    {
        Show::table($data, $title, $showBorder);
    }

    /**
     * @inheritdoc
     * @see Show::progressBar()
     */
    public function progressTxt($total, $msg, $doneMsg = '')
    {
        return Show::progressTxt($total, $msg, $doneMsg);
    }

    /**
     * @inheritdoc
     * @see Show::progressBar()
     */
    public function progressBar($total, array $opts = [])
    {
        return Show::progressBar($total, $opts);
    }

    /**
     * @param mixed $messages
     * @param string|null $type
     * @param string $style
     * @param int|boolean $quit If is int, setting it is exit code.
     * @return int
     */
    public function block($messages, $type = 'MESSAGE', $style = Style::NORMAL, $quit = false)
    {
        return Show::block($messages, $type, $style, $quit);
    }

    /**
     * @param mixed $messages
     * @param bool $quit
     * @return int
     */
    public function primary($messages, $quit = false)
    {
        return $this->block($messages, 'IMPORTANT', Style::PRIMARY, $quit);
    }

    /**
     * @param mixed $messages
     * @param bool $quit
     * @return int
     */
    public function success($messages, $quit = false)
    {
        return $this->block($messages, 'SUCCESS', Style::SUCCESS, $quit);
    }

    /**
     * @param mixed $messages
     * @param bool $quit
     * @return int
     */
    public function info($messages, $quit = false)
    {
        return $this->block($messages, 'INFO', Style::INFO, $quit);
    }

    /**
     * @param mixed $messages
     * @param bool $quit
     * @return int
     */
    public function note($messages, $quit = false)
    {
        return $this->block($messages, 'NOTE', Style::INFO, $quit);
    }

    /**
     * @param mixed $messages
     * @param bool $quit
     * @return int
     */
    public function notice($messages, $quit = false)
    {
        return $this->block($messages, 'NOTICE', Style::COMMENT, $quit);
    }

    /**
     * @param mixed $messages
     * @param bool $quit
     * @return int
     */
    public function warning($messages, $quit = false)
    {
        return $this->block($messages, 'WARNING', Style::WARNING, $quit);
    }

    /**
     * @param mixed $messages
     * @param bool $quit
     * @return int
     */
    public function danger($messages, $quit = false)
    {
        return $this->block($messages, 'DANGER', Style::DANGER, $quit);
    }

    /**
     * @param mixed $messages
     * @param bool $quit
     * @return int
     */
    public function error($messages, $quit = false)
    {
        return $this->block($messages, 'ERROR', Style::ERROR, $quit);
    }
}
