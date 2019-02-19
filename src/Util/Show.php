<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-10
 * Time: 11:59
 */

namespace Inhere\Console\Util;

use Inhere\Console\Component\Formatter\HelpPanel;
use Inhere\Console\Component\Formatter\MultiList;
use Inhere\Console\Component\Formatter\Padding;
use Inhere\Console\Component\Formatter\Panel;
use Inhere\Console\Component\Formatter\Section;
use Inhere\Console\Component\Formatter\SingleList;
use Inhere\Console\Component\Formatter\Table;
use Inhere\Console\Component\Formatter\Title;
use Inhere\Console\Component\Formatter\Tree;
use Inhere\Console\Component\Style\Style;
use Toolkit\Cli\Cli;
use Toolkit\Cli\ColorTag;
use Toolkit\StrUtil\Str;
use Toolkit\Sys\Sys;

/**
 * Class Show
 *  show formatted message text
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
    public const FINISHED = -1;

    public const CHAR_SPACE     = ' ';
    public const CHAR_HYPHEN    = '-';
    public const CHAR_UNDERLINE = '_';
    public const CHAR_VERTICAL  = '|';
    public const CHAR_EQUAL     = '=';
    public const CHAR_STAR      = '*';

    public const POS_LEFT   = 'l';
    public const POS_MIDDLE = 'm';
    public const POS_RIGHT  = 'r';

    /**
     * help panel keys
     */
    public const HELP_DES       = 'description';
    public const HELP_USAGE     = 'usage';
    public const HELP_COMMANDS  = 'commands';
    public const HELP_ARGUMENTS = 'arguments';
    public const HELP_OPTIONS   = 'options';
    public const HELP_EXAMPLES  = 'examples';
    public const HELP_EXTRAS    = 'extras';

    /** @var string */
    private static $buffer;

    /** @var bool */
    private static $buffering = false;

    /** @var array */
    public static $defaultBlocks = [
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
     * @param string|null $type
     * @param string      $style
     * @param int|boolean $quit If is int, setting it is exit code.
     * @return int
     */
    public static function block($messages, $type = 'MESSAGE', string $style = Style::NORMAL, $quit = false): int
    {
        $messages = \is_array($messages) ? \array_values($messages) : [$messages];

        // add type
        if (null !== $type) {
            $messages[0] = \sprintf('[%s] %s', strtoupper($type), $messages[0]);
        }

        $text  = \implode(\PHP_EOL, $messages);
        $color = static::getStyle();

        if (\is_string($style) && $color->hasStyle($style)) {
            $text = \sprintf('<%s>%s</%s>', $style, $text, $style);
        }

        return self::write($text, true, $quit);
    }

    /**
     * @param mixed       $messages
     * @param string|null $type
     * @param string      $style
     * @param int|boolean $quit If is int, setting it is exit code.
     * @return int
     */
    public static function liteBlock($messages, $type = 'MESSAGE', string $style = Style::NORMAL, $quit = false): int
    {
        $messages = \is_array($messages) ? \array_values($messages) : [$messages];

        // add type
        if (null !== $type) {
            $type = sprintf('[%s]', \strtoupper($type));
        }

        $text  = implode(PHP_EOL, $messages);
        $color = static::getStyle();

        if (\is_string($style) && $color->hasStyle($style)) {
            $type = sprintf('<%s>%s</%s> ', $style, $type, $style);
        }

        return self::write($type . $text, true, $quit);
    }

    /**
     * @var array
     */
    private static $blockMethods = [
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
     * @return int
     * @throws \LogicException
     */
    public static function __callStatic($method, array $args = [])
    {
        if (isset(self::$blockMethods[$method])) {
            $msg   = $args[0];
            $quit  = $args[1] ?? false;
            $style = self::$blockMethods[$method];

            if (0 === \strpos($method, 'lite')) {
                return self::liteBlock($msg, $style === 'primary' ? 'IMPORTANT' : $style, $style, $quit);
            }

            return self::block($msg, $style === 'primary' ? 'IMPORTANT' : $style, $style, $quit);
        }

        throw new \LogicException("Call a not exists method: $method");
    }

    /**************************************************************************************************
     * Output Format Message(section/list/helpPanel/panel/table)
     **************************************************************************************************/

    /**
     * @param string $title
     * @param string $char
     * @param int    $width
     * @return int
     */
    public static function splitLine(string $title, string $char = '-', int $width = 0): int
    {
        if ($width <= 0) {
            [$width,] = Sys::getScreenSize();
            $width -= 2;
        }

        if (!$title) {
            return self::write(\str_repeat($char, $width));
        }

        $strLen = \ceil(($width - Str::len($title) - 2) / 2);
        $padStr = $strLen > 0 ? \str_repeat($char, $strLen) : '';

        return self::write($padStr . ' ' . \ucwords($title) . ' ' . $padStr);
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
     * @param string|array $body The section body message
     * @param array        $opts
     */
    public static function section(string $title, $body, array $opts = []): void
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
     * @param array  $data
     * @param string $title
     * @param array  $opts More {@see FormatUtil::spliceKeyValue()}
     * @return int|string
     */
    public static function aList($data, string $title = '', array $opts = [])
    {
        return SingleList::show($data, $title, $opts);
    }

    /**
     * @see Show::aList()
     * {@inheritdoc}
     */
    public static function sList($data, string $title = '', array $opts = [])
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
     * @param array $data
     * @param array $opts
     */
    public static function mList(array $data, array $opts = []): void
    {
        MultiList::show($data, $opts);
    }

    /**
     * alias of the `mList()`
     * @param array $data
     * @param array $opts
     */
    public static function multiList(array $data, array $opts = []): void
    {
        MultiList::show($data, $opts);
    }


    /**
     * Render console help message
     * @param  array $config The config data
     * @see HelpPanel::show()
     */
    public static function helpPanel(array $config): void
    {
        HelpPanel::show($config);
    }

    /**
     * Show information data panel
     * @param  mixed  $data
     * @param  string $title
     * @param  array  $opts
     * @return int
     */
    public static function panel($data, string $title = 'Information Panel', array $opts = []): int
    {
        return Panel::show($data, $title, $opts);
    }

    /**
     * Render data like tree
     * ├ ─ ─
     * └ ─
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
     * @param  array  $data
     * @param  string $title
     * @param  array  $opts
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
     * @param string $msg
     * @param bool   $ended
     */
    public static function spinner(string $msg = '', $ended = false): void
    {
        static $chars = '-\|/';
        static $counter = 0;
        static $lastTime = null;

        $tpl = (Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r") . '%s';

        if ($ended) {
            printf($tpl, $msg);
            return;
        }

        $now = \microtime(true);

        if (null === $lastTime || ($lastTime < $now - 0.1)) {
            $lastTime = $now;
            // echo $chars[$counter];
            printf($tpl, $chars[$counter] . $msg);
            $counter++;

            if ($counter > \strlen($chars) - 1) {
                $counter = 0;
            }
        }
    }

    /**
     * alias of the pending()
     * @param string $msg
     * @param bool   $ended
     */
    public static function loading(string $msg = 'Loading ', $ended = false): void
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
     * @param string $msg
     * @param bool   $ended
     */
    public static function pending(string $msg = 'Pending ', $ended = false): void
    {
        static $counter = 0;
        static $lastTime = null;
        static $chars = ['', '.', '..', '...'];

        $tpl = (Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r") . '%s';

        if ($ended) {
            printf($tpl, $msg);
            return;
        }

        $now = \microtime(true);

        if (null === $lastTime || ($lastTime < $now - 0.8)) {
            $lastTime = $now;
            printf($tpl, $msg . $chars[$counter]);
            $counter++;

            if ($counter > \count($chars) - 1) {
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
     * @param string $msg
     * @param bool   $ended
     * @return int|mixed
     */
    public static function pointing(string $msg = 'handling ', $ended = false)
    {
        static $counter = 0;

        if ($ended) {
            return self::write(sprintf(' (%s %d)', $msg ?: 'Total', $counter));
        }

        if ($counter === 0 && $msg) {
            echo $msg;
        }

        $counter++;

        return print '.';
    }

    /**
     * 与文本进度条相比，没有 total
     * ```php
     *  $total = 120;
     *  $ctt = Show::counterTxt('handling ...', 'handled.');
     *  $this->write('Counter:');
     *  while ($total - 1) {
     *      $ctt->send(1);
     *      usleep(30000);
     *      $total--;
     *  }
     *  // end of the counter.
     *  $ctt->send(-1);
     * ```
     * @param string      $msg
     * @param string|null $doneMsg
     * @return \Generator
     */
    public static function counterTxt(string $msg, $doneMsg = null): ?\Generator
    {
        $counter  = 0;
        $finished = false;
        $tpl      = (Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r") . '%d %s';
        $msg      = self::getStyle()->render($msg);
        $doneMsg  = $doneMsg ? self::getStyle()->render($doneMsg) : null;

        while (true) {
            if ($finished) {
                return;
            }

            $step = yield;

            if ((int)$step <= 0) {
                $counter++;
                $finished = true;
                $msg      = $doneMsg ?: $msg;
            } else {
                $counter += $step;
            }

            printf($tpl, $counter, $msg);

            if ($finished) {
                echo "\n";
                break;
            }
        }

        yield false;
    }

    /**
     * @param string      $doneMsg
     * @param string|null $fixMsg
     * @return \Generator
     */
    public static function dynamicTxt(string $doneMsg, string $fixMsg = null): \Generator
    {
        return self::dynamicText($doneMsg, $fixMsg);
    }

    /**
     * @param string      $doneMsg
     * @param string|null $fixMsg
     * @return \Generator
     */
    public static function dynamicText(string $doneMsg, string $fixMsg = null): ?\Generator
    {
        $counter  = 0;
        $finished = false;
        // $tpl = Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r";
        $tpl = Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D";

        if ($fixMsg) {
            $tpl .= self::getStyle()->render($fixMsg);
        }

        $tpl     .= '%s';
        $doneMsg = $doneMsg ? self::getStyle()->render($doneMsg) : '';

        while (true) {
            if ($finished) {
                return;
            }

            $msg = yield;

            if ($msg === false) {
                $counter++;
                $finished = true;
                $msg      = $doneMsg ?: '';
            }

            printf($tpl, $msg);

            if ($finished) {
                echo "\n";
                break;
            }
        }

        yield $counter;
    }

    /**
     * @param int         $total
     * @param string      $msg
     * @param string|null $doneMsg
     * @return \Generator
     */
    public static function progressTxt(int $total, string $msg, string $doneMsg = null): ?\Generator
    {
        $current  = 0;
        $finished = false;
        $tpl      = (Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r") . "%' 3d%% %s";
        $msg      = self::getStyle()->render($msg);
        $doneMsg  = $doneMsg ? self::getStyle()->render($doneMsg) : null;

        while (true) {
            if ($finished) {
                return;
            }

            $step = yield;

            if ((int)$step <= 0) {
                $step = 1;
            }

            $current += $step;
            $percent = ceil(($current / $total) * 100);

            if ($percent >= 100) {
                $percent  = 100;
                $finished = true;
                $msg      = $doneMsg ?: $msg;
            }

            // printf("\r%d%% %s", $percent, $msg);
            // printf("\x0D\x2K %d%% %s", $percent, $msg);
            // printf("\x0D\r%'2d%% %s", $percent, $msg);
            printf($tpl, $percent, $msg);

            if ($finished) {
                echo "\n";
                break;
            }
        }

        yield false;
    }

    /**
     * a simple progress bar by 'yield'
     * ```php
     * $i = 0;
     * $total = 120;
     * $bar = Show::progressBar($total, [
     *     'msg' => 'Msg Text',
     *     'doneChar' => '#'
     * ]);
     * echo "progress:\n";
     * while ($i <= $total) {
     *      $bar->send(1); // 发送步进长度，通常是 1
     *      usleep(50000);
     *      $i++;
     * }
     * ```
     * @param int   $total
     * @param array $opts
     * @internal int $current
     * @return \Generator
     */
    public static function progressBar(int $total, array $opts = []): ?\Generator
    {
        $current   = 0;
        $finished  = false;
        $tplPrefix = Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r";
        $opts      = array_merge([
            'doneChar' => '=',
            'waitChar' => ' ',
            'signChar' => '>',
            'msg'      => '',
            'doneMsg'  => '',
        ], $opts);

        $msg      = self::getStyle()->render($opts['msg']);
        $doneMsg  = self::getStyle()->render($opts['doneMsg']);
        $waitChar = $opts['waitChar'];

        while (true) {
            if ($finished) {
                return;
            }

            $step = yield;

            if ((int)$step <= 0) {
                $step = 1;
            }

            $current += $step;
            $percent = ceil(($current / $total) * 100);

            if ($percent >= 100) {
                $msg      = $doneMsg ?: $msg;
                $percent  = 100;
                $finished = true;
            }

            /**
             * \r, \x0D 回车，到行首
             * \x1B ESC
             * 2K 清除本行
             */
            // printf("\r[%'--100s] %d%% %s",
            // printf("\x0D\x1B[2K[%'{$waitChar}-100s] %d%% %s",
            printf("{$tplPrefix}[%'{$waitChar}-100s] %' 3d%% %s",
                str_repeat($opts['doneChar'], $percent) . ($finished ? '' : $opts['signChar']),
                $percent,
                $msg
            );// ♥ ■ ☺ ☻ = #

            if ($finished) {
                echo "\n";
                break;
            }
        }

        yield false;
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
     * @param int  $max
     * @param bool $start
     * @return ProgressBar
     * @throws \LogicException
     */
    public static function createProgressBar($max = 0, $start = true): ProgressBar
    {
        $bar = new ProgressBar(null, $max);

        if ($start) {
            $bar->start();
        }

        return $bar;
    }

    /***********************************************************************************
     * Output buffer
     ***********************************************************************************/

    /**
     * @return bool
     */
    public static function isBuffering(): bool
    {
        return self::$buffering;
    }

    /**
     * @return string
     */
    public static function getBuffer(): string
    {
        return self::$buffer;
    }

    /**
     * @param string $buffer
     */
    public static function setBuffer(string $buffer): void
    {
        self::$buffer = $buffer;
    }

    /**
     * start buffering
     */
    public static function startBuffer(): void
    {
        self::$buffering = true;
    }

    /**
     * start buffering
     */
    public static function clearBuffer(): void
    {
        self::$buffer = null;
    }

    /**
     * stop buffering
     * @see Show::write()
     * @param bool  $flush Whether flush buffer to output stream
     * @param bool  $nl Default is False, because the last write() have been added "\n"
     * @param bool  $quit
     * @param array $opts
     * @return null|string If flush = False, will return all buffer text.
     */
    public static function stopBuffer($flush = true, $nl = false, $quit = false, array $opts = []): ?string
    {
        self::$buffering = false;

        if ($flush && self::$buffer) {
            // all text have been rendered by Style::render() in every write();
            $opts['color'] = false;

            // flush to stream
            self::write(self::$buffer, $nl, $quit, $opts);

            // clear buffer
            self::$buffer = null;
        }

        return self::$buffer;
    }

    /**
     * stop buffering and flush buffer text
     * @see Show::write()
     * @param bool  $nl
     * @param bool  $quit
     * @param array $opts
     */
    public static function flushBuffer($nl = false, $quit = false, array $opts = []): void
    {
        self::stopBuffer(true, $nl, $quit, $opts);
    }

    /***********************************************************************************
     * Helper methods
     ***********************************************************************************/

    /**
     * Format and write message to terminal
     * @param string $format
     * @param mixed  ...$args
     * @return int
     */
    public static function writef(string $format, ...$args): int
    {
        return self::write(\sprintf($format, ...$args));
    }

    /**
     * Write a message to standard output stream.
     * @param string|array $messages Output message
     * @param boolean      $nl True 会添加换行符, False 原样输出，不添加换行符
     * @param int|boolean  $quit If is int, setting it is exit code. 'True' translate as code 0 and exit, 'False' will not exit.
     * @param array        $opts
     * [
     *     'color'  => bool, // whether render color, default is: True.
     *     'stream' => resource, // the stream resource, default is: STDOUT
     *     'flush'  => bool, // flush the stream data, default is: True
     * ]
     * @return int
     */
    public static function write($messages, $nl = true, $quit = false, array $opts = []): int
    {
        if (\is_array($messages)) {
            $messages = \implode($nl ? \PHP_EOL : '', $messages);
        }

        $messages = (string)$messages;

        if (!isset($opts['color']) || $opts['color']) {
            $messages = self::getStyle()->render($messages);
        } else {
            $messages = Style::stripColor($messages);
        }

        // if open buffering
        if (self::isBuffering()) {
            self::$buffer .= $messages . ($nl ? \PHP_EOL : '');

            if (!$quit) {
                return 0;
            }

            $messages = self::$buffer;

            self::clearBuffer();
        } else {
            $messages .= $nl ? \PHP_EOL : '';
        }

        \fwrite($stream = $opts['stream'] ?? \STDOUT, $messages);

        if (!isset($opts['flush']) || $opts['flush']) {
            \fflush($stream);
        }

        // if will quit.
        if ($quit !== false) {
            $code = true === $quit ? 0 : (int)$quit;
            exit($code);
        }

        return 0;
    }

    /**
     * Write raw data to stdout, will disable color render.
     * @param string|array $message
     * @param bool         $nl
     * @param bool|int     $quit
     * @param array        $opts
     * @return int
     */
    public static function writeRaw($message, $nl = true, $quit = false, array $opts = []): int
    {
        $opts['color'] = false;
        return self::write($message, $nl, $quit, $opts);
    }

    /**
     * Write data to stdout with newline.
     * @param string|array $message
     * @param array        $opts
     * @param bool|int     $quit
     * @return int
     */
    public static function writeln($message, $quit = false, array $opts = []): int
    {
        return self::write($message, true, $quit, $opts);
    }

    /**
     * @param string|array $message
     * @param string       $style
     * @param bool         $nl
     * @param array        $opts
     * @return int
     */
    public static function color($message, string $style = 'info', $nl = true, array $opts = []): int
    {
        $quit = isset($opts['quit']) ? (bool)$opts['quit'] : false;

        return self::write(ColorTag::wrap($message, $style), $nl, $quit, $opts);
    }

    /**
     * Logs data to stdout
     * @param string|array $text
     * @param bool         $nl
     * @param bool|int     $quit
     */
    public static function stdout($text, $nl = true, $quit = false): void
    {
        self::write($text, $nl, $quit);
    }

    /**
     * Logs data to stderr
     * @param string|array $text
     * @param bool         $nl
     * @param bool|int     $quit
     */
    public static function stderr($text, $nl = true, $quit = -200): void
    {
        self::write($text, $nl, $quit, [
            'stream' => STDERR,
        ]);
    }

    /**
     * @param bool $onlyKey
     * @return array
     */
    public static function getBlockMethods($onlyKey = true): array
    {
        return $onlyKey ? \array_keys(self::$blockMethods) : self::$blockMethods;
    }

    /**
     * @return Style
     */
    public static function getStyle(): Style
    {
        return Style::instance();
    }
}
