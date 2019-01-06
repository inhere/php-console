<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-10
 * Time: 11:59
 */

namespace Inhere\Console\Util;

use Inhere\Console\Component\Style\Style;
use Toolkit\Cli\Cli;
use Toolkit\StrUtil\Str;
use Toolkit\StrUtil\StrBuffer;
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

        $text = \implode(\PHP_EOL, $messages);
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

        $text = implode(PHP_EOL, $messages);
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
            $msg = $args[0];
            $quit = $args[1] ?? false;
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
            list($width,) = Sys::getScreenSize();
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
    public static function title(string $title, array $opts = [])
    {
        $opts = \array_merge([
            'width'      => 80,
            'char'       => self::CHAR_EQUAL,
            'titlePos'   => self::POS_LEFT,
            'indent'     => 2,
            'showBorder' => true,
        ], $opts);

        // list($sW, $sH) = Helper::getScreenSize();
        $width = (int)$opts['width'];
        $char = trim($opts['char']);
        $indent = (int)$opts['indent'] >= 0 ? $opts['indent'] : 2;
        $indentStr = Helper::strPad(self::CHAR_SPACE, $indent, self::CHAR_SPACE);

        $title = ucwords(trim($title));
        $tLength = Str::len($title);
        $width = $width > 10 ? $width : 80;

        // title position
        if ($tLength >= $width) {
            $titleIndent = Str::pad(self::CHAR_SPACE, $indent, self::CHAR_SPACE);
        } elseif ($opts['titlePos'] === self::POS_RIGHT) {
            $titleIndent = Str::pad(self::CHAR_SPACE, ceil($width - $tLength) + $indent, self::CHAR_SPACE);
        } elseif ($opts['titlePos'] === self::POS_MIDDLE) {
            $titleIndent = Str::pad(self::CHAR_SPACE, ceil(($width - $tLength) / 2) + $indent, self::CHAR_SPACE);
        } else {
            $titleIndent = Str::pad(self::CHAR_SPACE, $indent, self::CHAR_SPACE);
        }

        $titleLine = "$titleIndent<bold>$title</bold>\n";
        $border = $indentStr . str_pad($char, $width, $char);

        self::write($titleLine . $border);
    }

    /**
     * @param string       $title The title text
     * @param string|array $body The section body message
     * @param array        $opts
     */
    public static function section(string $title, $body, array $opts = [])
    {
        $opts = array_merge([
            'width'        => 80,
            'char'         => self::CHAR_HYPHEN,
            'titlePos'     => self::POS_LEFT,
            'indent'       => 2,
            'topBorder'    => true,
            'bottomBorder' => true,
        ], $opts);

        // list($sW, $sH) = Helper::getScreenSize();
        $width = (int)$opts['width'];
        $char = \trim($opts['char']);
        $indent = (int)$opts['indent'] >= 0 ? $opts['indent'] : 2;
        $indentStr = Str::pad(self::CHAR_SPACE, $indent, self::CHAR_SPACE);

        $title = \ucwords(\trim($title));
        $tLength = Str::len($title);
        $width = $width > 10 ? $width : 80;

        // title position
        if ($tLength >= $width) {
            $titleIndent = Str::pad(self::CHAR_SPACE, $indent, self::CHAR_SPACE);
        } elseif ($opts['titlePos'] === self::POS_RIGHT) {
            $titleIndent = \str_pad(self::CHAR_SPACE, \ceil($width - $tLength) + $indent, self::CHAR_SPACE);
        } elseif ($opts['titlePos'] === self::POS_MIDDLE) {
            $titleIndent = \str_pad(self::CHAR_SPACE, \ceil(($width - $tLength) / 2) + $indent, self::CHAR_SPACE);
        } else {
            $titleIndent = Str::pad(self::CHAR_SPACE, $indent, self::CHAR_SPACE);
        }

        $tpl = "%s\n%s%s\n%s";// title topBorder body bottomBorder
        $topBorder = $bottomBorder = '';
        $titleLine = "$titleIndent<bold>$title</bold>";

        $showTBorder = (bool)$opts['topBorder'];
        $showBBorder = (bool)$opts['bottomBorder'];

        if ($showTBorder || $showBBorder) {
            $border = \str_pad($char, $width, $char);

            if ($showTBorder) {
                $topBorder = "{$indentStr}$border\n";
            }

            if ($showBBorder) {
                $bottomBorder = "{$indentStr}$border\n";
            }
        }

        $body = \is_array($body) ? implode(PHP_EOL, $body) : $body;
        $body = FormatUtil::wrapText($body, 4, $opts['width']);

        self::write(sprintf($tpl, $titleLine, $topBorder, $body, $bottomBorder));
    }

    /**
     * ```php
     * $data = [
     *  'Eggs' => '$1.99',
     *  'Oatmeal' => '$4.99',
     *  'Bacon' => '$2.99',
     * ];
     * ```
     * @param array       $data
     * @param string|null $title
     * @param array       $opts
     */
    public static function padding(array $data, string $title = null, array $opts = [])
    {
        if (!$data) {
            return;
        }

        $string = $title ? Helper::wrapTag(ucfirst($title), 'comment') . ":\n" : '';
        $opts = array_merge([
            'char'       => '.',
            'indent'     => '  ',
            'padding'    => 10,
            'valueStyle' => 'info',
        ], $opts);

        $keyMaxLen = Helper::getKeyMaxWidth($data);
        $paddingLen = $keyMaxLen > $opts['padding'] ? $keyMaxLen : $opts['padding'];

        foreach ($data as $label => $value) {
            $value = Helper::wrapTag((string)$value, $opts['valueStyle']);
            $string .= $opts['indent'] . str_pad($label, $paddingLen, $opts['char']) . " $value\n";
        }

        self::write(trim($string));
    }

    /**
     * Show a single list
     * ```
     * $title = 'list title';
     * $data = [
     *      'name'  => 'value text',
     *      'name2' => 'value text 2',
     * ];
     * ```
     * @param array  $data
     * @param string $title
     * @param array  $opts More {@see FormatUtil::spliceKeyValue()}
     * @return int|string
     */
    public static function aList($data, string $title = null, array $opts = [])
    {
        $string = '';
        $opts = array_merge([
            'leftChar'    => '  ',
            // 'sepChar' => '  ',
            'keyStyle'    => 'info',
            'keyMinWidth' => 8,
            'titleStyle'  => 'comment',
            'returned'    => false,
            'lastNewline' => true,
        ], $opts);

        // title
        if ($title) {
            $title = ucwords(trim($title));
            $string .= Helper::wrapTag($title, $opts['titleStyle']) . PHP_EOL;
        }

        // handle item list
        $string .= FormatUtil::spliceKeyValue((array)$data, $opts);

        if ($opts['returned']) {
            return $string;
        }

        return self::write($string, $opts['lastNewline']);
    }

    /**
     * @see Show::aList()
     * {@inheritdoc}
     */
    public static function sList($data, string $title = null, array $opts = [])
    {
        return self::aList($data, $title, $opts);
    }

    /**
     * Show multi list
     * ```
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
    public static function multiList(array $data, array $opts = [])
    {
        self::mList($data, $opts);
    }

    /**
     * alias of the `multiList()`
     * @param array $data
     * @param array $opts
     */
    public static function mList(array $data, array $opts = [])
    {
        $buffer = [];
        $opts['returned'] = true;
        $ignoreEmpty = $opts['ignoreEmpty'] ?? true;
        $lastNewline = true;

        if (isset($opts['lastNewline'])) {
            $lastNewline = $opts['lastNewline'];
            unset($opts['lastNewline']);
        }

        foreach ($data as $title => $list) {
            if ($ignoreEmpty && !$list) {
                continue;
            }

            $buffer[] = self::aList($list, $title, $opts);
        }

        self::write(implode("\n", $buffer), $lastNewline);
    }

    /**
     * Show console help message
     * @param  array $config The config data
     * There are config structure. you can setting some or ignore some. will only render it when value is not empty.
     * [
     *  description string         The description text. e.g 'Composer version 1.3.2'
     *  usage       string         The usage message text. e.g 'command [options] [arguments]'
     *  commands    array|string   The command list. e.g:
     *      [
     *          // command => description
     *          'start'    => 'Start the app server',
     *          ... ...
     *      ]
     *  arguments   array|string   The argument list. e.g:
     *      [
     *          // argument => description
     *          'name'      => 'Your name',
     *          'city'      => 'Your city name'
     *          ... ...
     *      ]
     *  options     array|string   The option list. e.g:
     *      [
     *          // option    => description
     *          '-d'         => 'Run the server on daemon.(default: <comment>false</comment>)',
     *          '-h, --help' => 'Display this help message'
     *          ... ...
     *      ]
     *  examples    array|string  The command usage example. e.g 'php server.php {start|reload|restart|stop} [-d]'
     * ]
     * @param  bool  $showAfterQuit Show help after quit
     */
    public static function helpPanel(array $config, $showAfterQuit = true)
    {
        $parts = [];
        $option = [
            'indentDes' => '  ',
        ];
        $config = \array_merge([
            'description' => '',
            'usage'       => '',

            'commands'  => [],
            'arguments' => [],
            'options'   => [],

            'examples' => [],

            // extra
            'extras'   => [],

            '_opts' => [],
        ], $config);

        // some option for show.
        if (isset($config['_opts'])) {
            $option = \array_merge($option, $config['_opts']);
            unset($config['_opts']);
        }

        // description
        if ($config['description']) {
            $parts[] = "{$option['indentDes']}{$config['description']}\n";
            unset($config['description']);
        }

        // now, render usage,commands,arguments,options,examples ...
        foreach ($config as $section => $value) {
            if (!$value) {
                continue;
            }

            // if $value is array, translate array to string
            if (\is_array($value)) {
                // is natural key ['text1', 'text2'](like usage,examples)
                if (isset($value[0])) {
                    $value = \implode(\PHP_EOL . '  ', $value);

                    // is key-value [ 'key1' => 'text1', 'key2' => 'text2']
                } else {
                    $value = FormatUtil::spliceKeyValue($value, [
                        'leftChar' => '  ',
                        'sepChar'  => '  ',
                        'keyStyle' => 'info',
                    ]);
                }
            }

            if (\is_string($value)) {
                $value = \trim($value);
                $section = \ucfirst($section);
                $parts[] = "<comment>$section</comment>:\n  {$value}\n";
            }
        }

        if ($parts) {
            self::write(\implode("\n", $parts), false);
        }

        if ($showAfterQuit) {
            exit(0);
        }
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
        if (!$data) {
            self::write('<info>No data to display!</info>');
            return -2;
        }

        $opts = \array_merge([
            'borderChar' => '*',
            'ucFirst'    => true,
        ], $opts);

        $borderChar = $opts['borderChar'];
        $data = \is_array($data) ? array_filter($data) : [trim($data)];
        $title = trim($title);

        $panelData = []; // [ 'label' => 'value' ]
        $labelMaxWidth = 0; // if label exists, label max width
        $valueMaxWidth = 0; // value max width

        foreach ($data as $label => $value) {
            // label exists
            if (!\is_numeric($label)) {
                $width = \mb_strlen($label, 'UTF-8');
                $labelMaxWidth = $width > $labelMaxWidth ? $width : $labelMaxWidth;
            }

            // translate array to string
            if (\is_array($value)) {
                $temp = '';

                /** @var array $value */
                foreach ($value as $key => $val) {
                    if (\is_bool($val)) {
                        $val = $val ? 'True' : 'False';
                    } else {
                        $val = (string)$val;
                    }

                    $temp .= (!\is_numeric($key) ? "$key: " : '') . "<info>$val</info>, ";
                }

                $value = \rtrim($temp, ' ,');
            } elseif (\is_bool($value)) {
                $value = $value ? 'True' : 'False';
            } else {
                $value = \trim((string)$value);
            }

            // get value width
            /** @var string $value */
            $value = \trim($value);
            $width = \mb_strlen(\strip_tags($value), 'UTF-8'); // must clear style tag
            $valueMaxWidth = $width > $valueMaxWidth ? $width : $valueMaxWidth;

            $panelData[$label] = $value;
        }

        $border = null;
        $panelWidth = $labelMaxWidth + $valueMaxWidth;
        self::startBuffer();

        // output title
        if ($title) {
            $title = \ucwords($title);
            $titleLength = \mb_strlen($title, 'UTF-8');
            $panelWidth = $panelWidth > $titleLength ? $panelWidth : $titleLength;
            $indentSpace = \str_pad(' ', ceil($panelWidth / 2) - ceil($titleLength / 2) + 2 * 2, ' ');
            self::write("  {$indentSpace}<bold>{$title}</bold>");
        }

        // output panel top border
        if ($borderChar) {
            $border = \str_pad($borderChar, $panelWidth + (3 * 3), $borderChar);
            self::write('  ' . $border);
        }

        // output panel body
        $panelStr = FormatUtil::spliceKeyValue($panelData, [
            'leftChar'    => "  $borderChar ",
            'sepChar'     => ' | ',
            'keyMaxWidth' => $labelMaxWidth,
            'ucFirst'     => $opts['ucFirst'],
        ]);

        // already exists "\n"
        self::write($panelStr, false);

        // output panel bottom border
        if ($border) {
            self::write("  $border\n");
        }

        self::flushBuffer();
        unset($panelData);
        return 0;
    }

    /**
     * @todo un-completed
     * ├ ─ ─
     * └ ─
     * @param array $data
     * @param array $opts
     */
    public static function tree(array $data, array $opts = [])
    {
        static $counter = 0;
        static $started = 1;

        if ($started) {
            $started = 0;
            $opts = \array_merge([
                // 'char' => Cli::isSupportColor() ? '─' : '-', // ——
                'char'        => '-',
                'prefix'      => Cli::isSupportColor() ? '├' : '|',
                'leftPadding' => '',
            ], $opts);

            $opts['_level'] = 1;
            $opts['_is_main'] = true;

            self::startBuffer();
        }

        foreach ($data as $key => $value) {
            if (\is_scalar($value)) {
                $counter++;
                $leftString = $opts['leftPadding'] . \str_pad($opts['prefix'], $opts['_level'] + 1, $opts['char']);

                self::write($leftString . ' ' . FormatUtil::typeToString($value));
            } elseif (\is_array($value)) {
                $newOpts = $opts;
                $newOpts['_is_main'] = false;
                $newOpts['_level']++;

                self::tree($value, $newOpts);
            }
        }

        if ($opts['_is_main']) {
            self::write('node count: ' . $counter);
            // var_dump('f');
            self::flushBuffer();

            // reset.
            $counter = $started = 0;
        }
    }

    /**
     * 表格数据信息展示
     * @param  array  $data
     * @param  string $title
     * @param  array  $opts
     * @example
     * ```
     * // like from database query's data.
     * $data = [
     *  [ col1 => value1, col2 => value2, col3 => value3, ... ], // first row
     *  [ col1 => value4, col2 => value5, col3 => value6, ... ], // second row
     *  ... ...
     * ];
     * Show::table($data, 'a table');
     *
     * // use custom head
     * $data = [
     *  [ value1, value2, value3, ... ], // first row
     *  [ value4, value5, value6, ... ], // second row
     *  ... ...
     * ];
     * $opts = [
     *   'showBorder' => true,
     *   'columns' => [col1, col2, col3, ...]
     * ];
     * Show::table($data, 'a table', $opts);
     * ```
     * @return int
     */
    public static function table(array $data, string $title = 'Data Table', array $opts = []): int
    {
        if (!$data) {
            return -404;
        }

        $buf = new StrBuffer();
        $opts = \array_merge([
            'showBorder'     => true,
            'leftIndent'     => '  ',
            'titlePos'       => self::POS_LEFT,
            'titleStyle'     => 'bold',
            'headStyle'      => 'comment',
            'headBorderChar' => self::CHAR_EQUAL,   // default is '='
            'bodyStyle'      => '',
            'rowBorderChar'  => self::CHAR_HYPHEN,   // default is '-'
            'colBorderChar'  => self::CHAR_VERTICAL, // default is '|'
            'columns'        => [],                  // custom column names
        ], $opts);

        $hasHead = false;
        $rowIndex = 0;
        $head = [];
        $tableHead = $opts['columns'];
        $leftIndent = $opts['leftIndent'];
        $showBorder = $opts['showBorder'];
        $rowBorderChar = $opts['rowBorderChar'];
        $colBorderChar = $opts['colBorderChar'];

        $info = [
            'rowCount'       => \count($data),
            'columnCount'    => 0,     // how many column in the table.
            'columnMaxWidth' => [], // table column max width
            'tableWidth'     => 0,      // table width. equals to all max column width's sum.
        ];

        // parse table data
        foreach ($data as $row) {
            // collection all field name
            if ($rowIndex === 0) {
                $head = $tableHead ?: \array_keys($row);
                $info['columnCount'] = \count($row);

                foreach ($head as $index => $name) {
                    if (\is_string($name)) {// maybe no column name.
                        $hasHead = true;
                    }

                    $info['columnMaxWidth'][$index] = \mb_strlen($name, 'UTF-8');
                }
            }

            $colIndex = 0;

            foreach ((array)$row as $value) {
                // collection column max width
                if (isset($info['columnMaxWidth'][$colIndex])) {
                    $colWidth = \mb_strlen($value, 'UTF-8');

                    // If current column width gt old column width. override old width.
                    if ($colWidth > $info['columnMaxWidth'][$colIndex]) {
                        $info['columnMaxWidth'][$colIndex] = $colWidth;
                    }
                } else {
                    $info['columnMaxWidth'][$colIndex] = \mb_strlen($value, 'UTF-8');
                }

                $colIndex++;
            }

            $rowIndex++;
        }

        $tableWidth = $info['tableWidth'] = \array_sum($info['columnMaxWidth']);
        $columnCount = $info['columnCount'];

        // output title
        if ($title) {
            $tStyle = $opts['titleStyle'] ?: 'bold';
            $title = \ucwords(trim($title));
            $titleLength = \mb_strlen($title, 'UTF-8');
            $indentSpace = \str_pad(' ', \ceil($tableWidth / 2) - \ceil($titleLength / 2) + ($columnCount * 2), ' ');
            $buf->write("  {$indentSpace}<$tStyle>{$title}</$tStyle>\n");
        }

        $border = $leftIndent . \str_pad($rowBorderChar, $tableWidth + ($columnCount * 3) + 2, $rowBorderChar);

        // output table top border
        if ($showBorder) {
            $buf->write($border . "\n");
        } else {
            $colBorderChar = '';// clear column border char
        }

        // output table head
        if ($hasHead) {
            $headStr = "{$leftIndent}{$colBorderChar} ";

            foreach ($head as $index => $name) {
                $colMaxWidth = $info['columnMaxWidth'][$index];
                $name = \str_pad($name, $colMaxWidth, ' ');
                $name = Helper::wrapTag($name, $opts['headStyle']);
                $headStr .= " {$name} {$colBorderChar}";
            }

            $buf->write($headStr . "\n");

            // head border: split head and body
            if ($headBorderChar = $opts['headBorderChar']) {
                $headBorder = $leftIndent . \str_pad($headBorderChar, $tableWidth + ($columnCount * 3) + 2,
                        $headBorderChar);
                $buf->write($headBorder . "\n");
            }
        }

        $rowIndex = 0;

        // output table info
        foreach ($data as $row) {
            $colIndex = 0;
            $rowStr = "  $colBorderChar ";

            foreach ((array)$row as $value) {
                $colMaxWidth = $info['columnMaxWidth'][$colIndex];
                $value = \str_pad($value, $colMaxWidth, ' ');
                $value = Helper::wrapTag($value, $opts['bodyStyle']);
                $rowStr .= " {$value} {$colBorderChar}";
                $colIndex++;
            }

            $buf->write($rowStr . "\n");

            $rowIndex++;
        }

        // output table bottom border
        if ($showBorder) {
            $buf->write($border . "\n");
        }

        self::write($buf);
        return 0;
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
    public static function spinner(string $msg = '', $ended = false)
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
    public static function loading(string $msg = 'Loading ', $ended = false)
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
    public static function pending(string $msg = 'Pending ', $ended = false)
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
    public static function counterTxt(string $msg, $doneMsg = null)
    {
        $counter = 0;
        $finished = false;
        $tpl = (Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r") . '%d %s';
        $msg = self::getStyle()->render($msg);
        $doneMsg = $doneMsg ? self::getStyle()->render($doneMsg) : null;

        while (true) {
            if ($finished) {
                return;
            }

            $step = yield;

            if ((int)$step <= 0) {
                $counter++;
                $finished = true;
                $msg = $doneMsg ?: $msg;
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
    public static function dynamicTxt(string $doneMsg, string $fixMsg = null)
    {
        return self::dynamicText($doneMsg, $fixMsg);
    }

    /**
     * @param string      $doneMsg
     * @param string|null $fixMsg
     * @return \Generator
     */
    public static function dynamicText(string $doneMsg, string $fixMsg = null)
    {
        $counter = 0;
        $finished = false;
        // $tpl = Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r";
        $tpl = Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D";

        if ($fixMsg) {
            $tpl .= self::getStyle()->render($fixMsg);
        }

        $tpl .= '%s';
        $doneMsg = $doneMsg ? self::getStyle()->render($doneMsg) : '';

        while (true) {
            if ($finished) {
                return;
            }

            $msg = yield;

            if ($msg === false) {
                $counter++;
                $finished = true;
                $msg = $doneMsg ?: '';
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
    public static function progressTxt(int $total, string $msg, string $doneMsg = null)
    {
        $current = 0;
        $finished = false;
        $tpl = (Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r") . "%' 3d%% %s";
        $msg = self::getStyle()->render($msg);
        $doneMsg = $doneMsg ? self::getStyle()->render($doneMsg) : null;

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
                $percent = 100;
                $finished = true;
                $msg = $doneMsg ?: $msg;
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
    public static function progressBar(int $total, array $opts = [])
    {
        $current = 0;
        $finished = false;
        $tplPrefix = Cli::isSupportColor() ? "\x0D\x1B[2K" : "\x0D\r";
        $opts = array_merge([
            'doneChar' => '=',
            'waitChar' => ' ',
            'signChar' => '>',
            'msg'      => '',
            'doneMsg'  => '',
        ], $opts);

        $msg = self::getStyle()->render($opts['msg']);
        $doneMsg = self::getStyle()->render($opts['doneMsg']);
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
                $msg = $doneMsg ?: $msg;
                $percent = 100;
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
    public static function setBuffer(string $buffer)
    {
        self::$buffer = $buffer;
    }

    /**
     * start buffering
     */
    public static function startBuffer()
    {
        self::$buffering = true;
    }

    /**
     * start buffering
     */
    public static function clearBuffer()
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
    public static function stopBuffer($flush = true, $nl = false, $quit = false, array $opts = [])
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
    public static function flushBuffer($nl = false, $quit = false, array $opts = [])
    {
        self::stopBuffer(true, $nl, $quit, $opts);
    }

    /***********************************************************************************
     * Helper methods
     ***********************************************************************************/

    /**
     * Write a message to standard output stream.
     * @param string|array $messages Output message
     * @param boolean      $nl True 会添加换行符, False 原样输出，不添加换行符
     * @param int|boolean  $quit If is int, setting it is exit code. 'True' translate as code 0 and exit, 'False' will not exit.
     * @param array        $opts
     * [
     *     'color' => bool, // whether render color, default is: True.
     *     'stream' => resource, // the stream resource, default is: STDOUT
     *     'flush' => bool, // flush the stream data, default is: True
     * ]
     * @return int
     */
    public static function write($messages, $nl = true, $quit = false, array $opts = []): int
    {
        if (\is_array($messages)) {
            $messages = \implode($nl ? PHP_EOL : '', $messages);
        }

        $messages = (string)$messages;

        if (!isset($opts['color']) || $opts['color']) {
            $messages = static::getStyle()->render($messages);
        } else {
            $messages = Style::stripColor($messages);
        }

        // if open buffering
        if (self::isBuffering()) {
            self::$buffer .= $messages . ($nl ? PHP_EOL : '');

            if (!$quit) {
                return 0;
            }

            // if will quit.
            $messages = self::$buffer;
            self::clearBuffer();
        } else {
            $messages .= $nl ? PHP_EOL : '';
        }

        \fwrite($stream = $opts['stream'] ?? \STDOUT, $messages);

        if (!isset($opts['flush']) || $opts['flush']) {
            \fflush($stream);
        }

        if ($quit !== false) {
            $code = true === $quit ? 0 : (int)$quit;
            exit($code);
        }

        return 0;
    }

    /**
     * write raw data to stdout
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
     * Logs data to stdout
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

        return self::write(Helper::wrapTag($message, $style), $nl, $quit, $opts);
    }

    /**
     * Logs data to stdout
     * @param string|array $text
     * @param bool         $nl
     * @param bool|int     $quit
     */
    public static function stdout($text, $nl = true, $quit = false)
    {
        self::write($text, $nl, $quit);
    }

    /**
     * Logs data to stderr
     * @param string|array $text
     * @param bool         $nl
     * @param bool|int     $quit
     */
    public static function stderr($text, $nl = true, $quit = -200)
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
        return Style::create();
    }
}
