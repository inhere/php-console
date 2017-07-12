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
 *
 * @method int info($messages, $quit = false)
 * @method int note($messages, $quit = false)
 * @method int notice($messages, $quit = false)
 * @method int success($messages, $quit = false)
 * @method int primary($messages, $quit = false)
 * @method int warning($messages, $quit = false)
 * @method int danger($messages, $quit = false)
 * @method int error($messages, $quit = false)
 *
 * @method int liteInfo($messages, $quit = false)
 * @method int liteNote($messages, $quit = false)
 * @method int liteNotice($messages, $quit = false)
 * @method int liteSuccess($messages, $quit = false)
 * @method int litePrimary($messages, $quit = false)
 * @method int liteWarning($messages, $quit = false)
 * @method int liteDanger($messages, $quit = false)
 * @method int liteError($messages, $quit = false)
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
     * @see Show::block()
     */
    public function block($messages, $type = 'MESSAGE', $style = Style::NORMAL, $quit = false)
    {
        return Show::block($messages, $type, $style, $quit);
    }

    /**
     * @inheritdoc
     * @see Show::liteBlock()
     */
    public function liteBlock($messages, $type = 'MESSAGE', $style = Style::NORMAL, $quit = false)
    {
        return Show::liteBlock($messages, $type, $style, $quit);
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
     * @param string $method
     * @param array $args
     * @return int
     */
    public function __call($method, array $args = [])
    {
        $map = Show::getBlockMethods(false);

        if (isset($map[$method])) {
            $msg = $args[0];
            $quit = $args[1] ?? false;
            $style = $map[$method];

            if (0 === strpos($method, 'lite')) {
                $type = substr($method, 4);
                return Show::liteBlock($msg, $type === 'Primary' ? 'IMPORTANT' : $type, $style, $quit);
            }

            return Show::block($msg, $style === 'primary' ? 'IMPORTANT' : $style, $style, $quit);
        }

        throw new \LogicException("Call a not exists method: $method");
    }
}
