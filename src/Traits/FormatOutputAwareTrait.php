<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-06-20
 * Time: 15:10
 */

namespace Inhere\Console\Traits;

use Inhere\Console\Component\Style\Style;
use Inhere\Console\Util\Show;
use Toolkit\PhpUtil\Php;

/**
 * Class FormatOutputAwareTrait
 * @package Inhere\Console\Traits
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
 *
 * @method int color($message, $style = 'info', $nl = true, array $opts = [])
 *
 * @method padding(array $data, string $title = null, array $opts = [])
 *
 * @method splitLine(string $title, string $char = '-', int $width = 0)
 * @method spinner($msg = '', $ended = false)
 * @method loading($msg = 'Loading ', $ended = false)
 * @method pending($msg = 'Pending ', $ended = false)
 * @method pointing($msg = 'handling ', $ended = false)
 *
 * @method \Generator counterTxt($msg = 'Pending ', $ended = false)
 */
trait FormatOutputAwareTrait
{
    /**
     * @inheritdoc
     * @see Show::write()
     */
    public function write($messages, $nl = true, $quit = false, array $opts = []): int
    {
        return Show::write($messages, $nl, $quit, array([
            'flush'  => true,
            'stream' => $this->outputStream,
        ], $opts));
    }

    /**
     * @inheritdoc
     * @see Show::writeln()
     */
    public function writeln($text, $quit = false, array $opts = []): int
    {
        return Show::writeln($text, $quit, $opts);
    }

    /**
     * @inheritdoc
     * @see Show::writeRaw()
     */
    public function writeRaw($text, $nl = true, $quit = false, array $opts = []): int
    {
        return Show::writeRaw($text, $nl, $quit, $opts);
    }

    /**
     * @inheritdoc
     * @see Show::block()
     */
    public function block($messages, $type = 'MESSAGE', $style = Style::NORMAL, $quit = false): int
    {
        return Show::block($messages, $type, $style, $quit);
    }

    /**
     * @inheritdoc
     * @see Show::liteBlock()
     */
    public function liteBlock($messages, $type = 'MESSAGE', $style = Style::NORMAL, $quit = false): int
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
    public function panel(array $data, $title = 'Information panel', array $opts = [])
    {
        Show::panel($data, $title, $opts);
    }

    /**
     * @inheritdoc
     * @see Show::table()
     */
    public function table(array $data, $title = 'Data Table', $showBorder = true)
    {
        Show::table($data, $title, ['showBorder' => $showBorder]);
    }

    /**
     * @inheritdoc
     * @see Show::progressBar()
     */
    public function progressTxt($total, $msg, $doneMsg = ''): \Generator
    {
        return Show::progressTxt($total, $msg, $doneMsg);
    }

    /**
     * @inheritdoc
     * @see Show::progressBar()
     */
    public function progressBar($total, array $opts = []): \Generator
    {
        return Show::progressBar($total, $opts);
    }

    /**
     * @param string $method
     * @param array  $args
     * @return int
     * @throws \LogicException
     */
    public function __call($method, array $args = [])
    {
        $map = Show::getBlockMethods(false);

        if (isset($map[$method])) {
            $msg = $args[0];
            $quit = $args[1] ?? false;
            $style = $map[$method];

            if (0 === \strpos($method, 'lite')) {
                $type = \substr($method, 4);
                return Show::liteBlock($msg, $type === 'Primary' ? 'IMPORTANT' : $type, $style, $quit);
            }

            return Show::block($msg, $style === 'primary' ? 'IMPORTANT' : $style, $style, $quit);
        }

        if (\method_exists(Show::class, $method)) {
            return Show::$method(...$args);
        }

        throw new \LogicException("Call a not exists method: $method of the " . static::class);
    }

    /**
     * @param mixed $data
     * @param bool  $echo
     * @param int   $flags
     * @return int|string
     */
    public function json(
        $data,
        $echo = true,
        int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) {
        $string = \json_encode($data, $flags);

        if ($echo) {
            return Show::write($string);
        }

        return $string;
    }

    /**
     * @param array ...$vars
     */
    public function dump(...$vars)
    {
        Show::write(Php::dumpVars(...$vars));
    }

    /**
     * @param array ...$vars
     */
    public function prints(...$vars)
    {
        Show::write(Php::printVars(...$vars));
    }
}
