<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Util;

use Generator;
use Inhere\Console\Component\Formatter\HelpPanel;
use Inhere\Console\Component\Formatter\MultiList;
use Inhere\Console\Component\Formatter\Padding;
use Inhere\Console\Component\Formatter\Panel;
use Inhere\Console\Component\Formatter\Section;
use Inhere\Console\Component\Formatter\SingleList;
use Inhere\Console\Component\Formatter\Table;
use Inhere\Console\Component\Formatter\Title;
use Inhere\Console\Component\Formatter\Tree;
use Inhere\Console\Component\Progress\CounterText;
use Inhere\Console\Component\Progress\DynamicText;
use Inhere\Console\Component\Progress\SimpleBar;
use Inhere\Console\Component\Progress\SimpleTextBar;
use Inhere\Console\Console;
use LogicException;
use Toolkit\Cli\Cli;
use Toolkit\Cli\Color\ColorTag;
use Toolkit\Cli\Style;
use Toolkit\Stdlib\Helper\JsonHelper;
use Toolkit\Stdlib\Math;
use Toolkit\Stdlib\Str;
use Toolkit\Sys\Sys;
use function array_keys;
use function array_values;
use function count;
use function implode;
use function is_array;
use function is_string;
use function microtime;
use function sprintf;
use function strlen;
use function strtoupper;
use function substr;
use function ucwords;
use const PHP_EOL;

/**
 * Class Show - render and display formatted message text
 *
 * @package Inhere\Console\Util
 * @method static int info($messages, $quit = false)
 * @method static int note($messages, $quit = false)
 * @method static int notice($messages, $quit = false)
 * @method static int success($messages, $quit = false)
 * @method static int primary($messages, $quit = false)
 * @method static int warning($messages, $quit = false)
 * @method static int danger($messages, $quit = false)
 * @method static int error($messages, $quit = false)
 * @method static int liteInfo($messages, $quit = false)
 * @method static int liteNote($messages, $quit = false)
 * @method static int liteNotice($messages, $quit = false)
 * @method static int liteSuccess($messages, $quit = false)
 * @method static int litePrimary($messages, $quit = false)
 * @method static int liteWarning($messages, $quit = false)
 * @method static int liteDanger($messages, $quit = false)
 * @method static int liteError($messages, $quit = false)
 */
class Show
{
    /** @var array */
    public static array $defaultBlocks = [
        'block',
        'primary',
        'info',
        'notice',
        'success',
        'warning',
        'danger',
        'error'
    ];

    /**************************************************************************************************
     * Output block Message
     **************************************************************************************************/

    /**
     * @param mixed       $messages
     * @param string      $type
     * @param string      $style
     * @param bool|int $quit If is int, setting it is exit code.
     *
     * @return int
     */
    public static function block(mixed $messages, string $type = 'MESSAGE', string $style = Style::NORMAL, bool|int $quit = false): int
    {
        $messages = is_array($messages) ? array_values($messages) : [$messages];

        // add type
        if ($type) {
            $messages[0] = sprintf('[%s] %s', strtoupper($type), $messages[0]);
        }

        $text  = implode(PHP_EOL, $messages);
        $color = static::getStyle();

        if (is_string($style) && $color->hasStyle($style)) {
            $text = sprintf('<%s>%s</%s>', $style, $text, $style);
        }

        return self::write($text, true, $quit);
    }

    /**
     * @param mixed       $messages
     * @param string      $type
     * @param string      $style
     * @param boolean|int $quit If is int, setting it is exit code.
     *
     * @return int
     */
    public static function liteBlock(
        mixed $messages,
        string $type = 'MESSAGE',
        string $style = Style::NORMAL,
        bool|int $quit = false
    ): int {
        $fmtType  = '';
        $messages = is_array($messages) ? array_values($messages) : [$messages];

        $text  = implode(PHP_EOL, $messages);
        $color = static::getStyle();

        // format type
        if ($type) {
            $upType = strtoupper($type);
            // add style
            if ($style && $color->hasStyle($style)) {
                $fmtType = sprintf('<%s>[%s]</%s> ', $style, $upType, $style);
            } else {
                $fmtType = sprintf('[%s]', $upType);
            }
        }

        return self::write($fmtType . $text, true, $quit);
    }

    /**
     * @var array
     */
    private static array $blockMethods = [
        // method => style
        'info'        => 'info',
        'note'        => 'note',
        'notice'      => 'notice',
        'success'     => 'success',
        'primary'     => 'primary',
        'warning'     => 'warning',
        'danger'      => 'danger',
        'error'       => 'error',

        // lite style
        'liteInfo'    => 'info',
        'liteNote'    => 'note',
        'liteNotice'  => 'notice',
        'liteSuccess' => 'success',
        'litePrimary' => 'primary',
        'liteWarning' => 'yellow',
        'liteDanger'  => 'danger',
        'liteError'   => 'red',
    ];

    /**
     * @param string $method
     * @param array  $args
     *
     * @return int
     * @throws LogicException
     */
    public static function __callStatic(string $method, array $args = [])
    {
        if (isset(self::$blockMethods[$method])) {
            $msg   = $args[0];
            $quit  = $args[1] ?? false;
            $style = self::$blockMethods[$method];

            if (str_starts_with($method, 'lite')) {
                $type = substr($method, 4);

                return self::liteBlock($msg, $type === 'primary' ? 'IMPORTANT' : $type, $style, $quit);
            }

            return self::block($msg, $style === 'primary' ? 'IMPORTANT' : $style, $style, $quit);
        }

        throw new LogicException("Call a not exists method: $method");
    }

    /**************************************************************************************************
     * Output Format Message(section/list/helpPanel/panel/table)
     **************************************************************************************************/

    /**
     * Print JSON
     *
     * @param mixed $data
     * @param string $title
     */
    public static function prettyJSON(mixed $data, string $title = 'JSON:'): void
    {
        if ($title) {
            Console::colored($title, 'ylw0');
        }

        Console::write(JsonHelper::prettyJSON($data));
    }

    /**
     * @param string $title
     * @param string $char
     * @param int    $width
     *
     * @return int
     */
    public static function splitLine(string $title, string $char = '-', int $width = 0): int
    {
        if ($width <= 0) {
            [$width,] = Sys::getScreenSize();
            $width -= 2;
        }

        if (!$title) {
            return self::write(Str::repeat($char, $width));
        }

        $strLen = Math::ceil(($width - Str::len($title) - 2) / 2);
        $padStr = $strLen > 0 ? Str::repeat($char, $strLen) : '';

        return self::write($padStr . ' ' . ucwords($title) . ' ' . $padStr);
    }

    /**
     * @param string $title The title text
     * @param array  $opts
     */
    public static function title(string $title, array $opts = []): void
    {
        Title::show($title, $opts);
    }

    /**
     * @param string       $title The title text
     * @param array|string $body  The section body message
     * @param array        $opts
     */
    public static function section(string $title, array|string $body, array $opts = []): void
    {
        Section::show($title, $body, $opts);
    }

    /**
     * ```php
     * $data = [
     *  'Eggs' => '$1.99',
     *  'Oatmeal' => '$4.99',
     *  'Bacon' => '$2.99',
     * ];
     * ```
     *
     * @param array  $data
     * @param string $title
     * @param array  $opts
     */
    public static function padding(array $data, string $title = '', array $opts = []): void
    {
        Padding::show($data, $title, $opts);
    }

    /**
     * Show a single list
     *
     * ```
     * $title = 'list title';
     * $data = [
     *      'name'  => 'value text',
     *      'name2' => 'value text 2',
     * ];
     * ```
     *
     * @param object|array $data
     * @param string $title
     * @param array  $opts More {@see FormatUtil::spliceKeyValue()}
     *
     * @return int|string
     */
    public static function aList(object|array $data, string $title = '', array $opts = []): int|string
    {
        return SingleList::show($data, $title, $opts);
    }

    /**
     * @param mixed  $data
     * @param string $title
     * @param array  $opts
     *
     * @return int|string
     */
    public static function sList(mixed $data, string $title = '', array $opts = []): int|string
    {
        return SingleList::show($data, $title, $opts);
    }

    /**
     * Format and render multi list
     *
     * ```php
     * [
     *   'list1 title' => [
     *      'name' => 'value text',
     *      'name2' => 'value text 2',
     *   ],
     *   'list2 title' => [
     *      'name' => 'value text',
     *      'name2' => 'value text 2',
     *   ],
     *   ... ...
     * ]
     * ```
     *
     * @param array $data
     * @param array $opts
     */
    public static function mList(array $data, array $opts = []): void
    {
        MultiList::show($data, $opts);
    }

    /**
     * alias of the `mList()`
     *
     * @param array $data
     * @param array $opts
     */
    public static function multiList(array $data, array $opts = []): void
    {
        MultiList::show($data, $opts);
    }

    /**
     * Render console help message
     *
     * @param array $config The config data
     *
     * @see HelpPanel::show()
     */
    public static function helpPanel(array $config): void
    {
        HelpPanel::show($config);
    }

    /**
     * Show information data panel
     *
     * @param mixed  $data
     * @param string $title
     * @param array  $opts
     *
     * @return int
     */
    public static function panel(mixed $data, string $title = 'Information Panel', array $opts = []): int
    {
        return Panel::show($data, $title, $opts);
    }

    /**
     * Render data like tree
     * ├ ─ ─
     * └ ─
     *
     * @param array $data
     * @param array $opts
     */
    public static function tree(array $data, array $opts = []): void
    {
        Tree::show($data, $opts);
    }

    /**
     * Tabular data display
     *
     * @param array  $data
     * @param string $title
     * @param array  $opts
     *
     * @return int
     * @see Table::show()
     */
    public static function table(array $data, string $title = 'Data Table', array $opts = []): int
    {
        return Table::show($data, $title, $opts);
    }

    /***********************************************************************************
     * Output progress message
     ***********************************************************************************/

    /**
     * show a spinner icon message
     * ```php
     *  $total = 5000;
     *  while ($total--) {
     *      Show::spinner();
     *      usleep(100);
     *  }
     *  Show::spinner('Done', true);
     * ```
     *
     * @param string $msg
     * @param bool   $ended
     */
    public static function spinner(string $msg = '', bool $ended = false): void
    {
        static $chars = '-\|/';
        static $counter = 0;
        static $lastTime = null;

        $tpl = (Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r") . '%s';

        if ($ended) {
            printf($tpl, $msg);
            return;
        }

        $now = microtime(true);

        if (null === $lastTime || ($lastTime < $now - 0.1)) {
            $lastTime = $now;
            // echo $chars[$counter];
            printf($tpl, $chars[$counter] . $msg);
            $counter++;

            if ($counter > strlen($chars) - 1) {
                $counter = 0;
            }
        }
    }

    /**
     * alias of the pending()
     *
     * @param string $msg
     * @param bool   $ended
     */
    public static function loading(string $msg = 'Loading ', bool $ended = false): void
    {
        self::pending($msg, $ended);
    }

    /**
     * show a pending message
     * ```php
     *  $total = 8000;
     *  while ($total--) {
     *      Show::pending();
     *      usleep(200);
     *  }
     *  Show::pending('Done', true);
     * ```
     *
     * @param string $msg
     * @param bool   $ended
     */
    public static function pending(string $msg = 'Pending ', bool $ended = false): void
    {
        static $counter = 0;
        static $lastTime = null;
        static $chars = ['', '.', '..', '...'];

        $tpl = (Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r") . '%s';

        if ($ended) {
            printf($tpl, $msg);
            return;
        }

        $now = microtime(true);

        if (null === $lastTime || ($lastTime < $now - 0.8)) {
            $lastTime = $now;
            printf($tpl, $msg . $chars[$counter]);
            $counter++;

            if ($counter > count($chars) - 1) {
                $counter = 0;
            }
        }
    }

    /**
     * show a pending message
     * ```php
     *  $total = 8000;
     *  while ($total--) {
     *      Show::pointing();
     *      usleep(200);
     *  }
     *  Show::pointing('Total', true);
     * ```
     *
     * @param string $msg
     * @param bool   $ended
     *
     * @return int
     */
    public static function pointing(string $msg = 'handling ', bool $ended = false): int
    {
        static $counter = 0;

        if ($ended) {
            return Console::writef(' (%s %d)', $msg ?: 'Total', $counter);
        }

        if ($counter === 0 && $msg) {
            echo $msg;
        }

        $counter++;

        print '.';
        return 0;
    }

    /**
     * 与文本进度条相比，没有 total
     *
     * @param string      $msg
     * @param string $doneMsg
     *
     * @return Generator
     */
    public static function counterTxt(string $msg, string $doneMsg = ''): Generator
    {
        return CounterText::gen($msg, $doneMsg);
    }

    /**
     * @param string      $doneMsg
     * @param string $fixMsg
     *
     * @return Generator
     */
    public static function dynamicTxt(string $doneMsg, string $fixMsg = ''): Generator
    {
        return self::dynamicText($doneMsg, $fixMsg);
    }

    /**
     * @param string      $doneMsg
     * @param string $fixedMsg
     *
     * @return Generator
     */
    public static function dynamicText(string $doneMsg, string $fixedMsg = ''): Generator
    {
        return DynamicText::gen($doneMsg, $fixedMsg);
    }

    /**
     * Render a simple text progress bar by 'yield'
     *
     * @param int    $total
     * @param string $msg
     * @param string $doneMsg
     *
     * @return Generator
     */
    public static function progressTxt(int $total, string $msg, string $doneMsg = ''): Generator
    {
        return SimpleTextBar::gen($total, $msg, $doneMsg);
    }

    /**
     * Render a simple progress bar by 'yield'
     *
     * @param int   $total
     * @param array $opts
     *
     * @return Generator|null
     * @internal int $current
     */
    public static function progressBar(int $total, array $opts = []): ?Generator
    {
        return SimpleBar::gen($total, $opts);
    }

    /**
     * create ProgressBar
     * ```php
     * $max = 200;
     * $bar = Show::createProgressBar($max);
     * while ($i <= $total) {
     *   $bar->advance();
     *   usleep(50000);
     *   $i++;
     * }
     * $bar->finish();
     * ```
     *
     * @param int $max
     * @param bool $start
     *
     * @return ProgressBar
     * @throws LogicException
     */
    public static function createProgressBar(int $max = 0, bool $start = true): ProgressBar
    {
        $bar = new ProgressBar(null, $max);

        if ($start) {
            $bar->start();
        }

        return $bar;
    }

    /***********************************************************************************
     * Helper methods
     ***********************************************************************************/

    /**
     * Format and write message to terminal
     *
     * @param string $format
     * @param mixed  ...$args
     *
     * @return int
     */
    public static function writef(string $format, ...$args): int
    {
        return self::write(sprintf($format, ...$args));
    }

    /**
     * Write a message to standard output stream.
     *
     * @param array|string $messages Output message
     * @param boolean      $nl       True 会添加换行符, False 原样输出，不添加换行符
     * @param bool $quit     If is int, setting it is exit code. 'True' translate as code 0 and exit, 'False' will not exit.
     * @param array{color:bool,stream:resource,flush:bool,quit:bool,quitCode:int}  $opts Some options for write
     * @return int
     */
    public static function write(array|string $messages, bool $nl = true, bool $quit = false, array $opts = []): int
    {
        return Console::write($messages, $nl, $quit, $opts);
    }

    /**
     * Write raw data to stdout, will disable color render.
     *
     * @param array|string $message
     * @param bool $nl
     * @param bool $quit
     * @param array{color:bool,stream:resource,flush:bool,quit:bool,quitCode:int} $opts
     *
     * @return int
     */
    public static function writeRaw(array|string $message, bool $nl = true, bool $quit = false, array $opts = []): int
    {
        $opts['color'] = false;
        return Console::write($message, $nl, $quit, $opts);
    }

    /**
     * Write data to stdout with newline.
     *
     * @param array|string $message
     * @param bool $quit
     * @param array{color:bool,stream:resource,flush:bool,quit:bool,quitCode:int} $opts
     *
     * @return int
     */
    public static function writeln(array|string $message, bool $quit = false, array $opts = []): int
    {
        return Console::write($message, true, $quit, $opts);
    }

    /**
     * @param array|string $message
     * @param string       $style
     * @param bool         $nl
     * @param array{quit:bool,quitCode:int}  $opts
     *
     * @return int
     */
    public static function colored(array|string $message, string $style = 'info', bool $nl = true, array $opts = []): int
    {
        $quit = isset($opts['quit']) && $opts['quit'];

        if (is_array($message)) {
            $message = implode($nl ? PHP_EOL : '', $message);
        }

        return self::write(ColorTag::wrap($message, $style), $nl, $quit, $opts);
    }

    /**
     * @param bool $onlyKey
     *
     * @return array
     */
    public static function getBlockMethods(bool $onlyKey = true): array
    {
        return $onlyKey ? array_keys(self::$blockMethods) : self::$blockMethods;
    }

    /**
     * @return Style
     */
    public static function getStyle(): Style
    {
        return Style::instance();
    }
}
