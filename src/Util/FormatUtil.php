<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-18
 * Time: 19:34
 */

namespace Inhere\Console\Util;

use Toolkit\Cli\ColorTag;
use Toolkit\Stdlib\Helper\Format;
use Toolkit\Stdlib\Helper\JsonHelper;
use Toolkit\Stdlib\Str;
use Toolkit\Sys\Sys;
use function array_keys;
use function array_merge;
use function explode;
use function implode;
use function is_array;
use function is_bool;
use function is_int;
use function is_numeric;
use function is_scalar;
use function rtrim;
use function str_repeat;
use function str_replace;
use function strpos;
use function trim;
use function ucfirst;
use function wordwrap;
use const STR_PAD_RIGHT;

/**
 * Class FormatUtil
 *
 * @package Inhere\Console\Util
 */
final class FormatUtil
{
    /**
     * @param mixed $val
     *
     * @return string
     */
    public static function typeToString($val): string
    {
        if (null === $val) {
            return '(Null)';
        }

        if (is_bool($val)) {
            return $val ? '(True)' : '(False)';
        }

        return (string)$val;
    }

    /**
     * @param string $string
     * @param int    $indent
     * @param string $indentChar
     *
     * @return string
     */
    public static function applyIndent(string $string, int $indent = 2, string $indentChar = ' '): string
    {
        if (!$string || $indent <= 0) {
            return $string;
        }

        $new       = '';
        $list      = explode("\n", $string);
        $indentStr = str_repeat($indentChar ?: ' ', $indent);

        foreach ($list as $value) {
            $new .= $indentStr . trim($value) . "\n";
        }

        return $new;
    }

    /**
     * Word wrap text with indentation to fit the screen size
     *
     * If screen size could not be detected, or the indentation is greater than the screen size, the text will not be wrapped.
     *
     * The first line will **not** be indented, so `wrapText("Lorem ipsum dolor sit amet.", 4)` will result in the
     * following output, given the screen width is 16 characters:
     *
     * ```
     * Lorem ipsum
     *     dolor sit
     *     amet.
     * ```
     *
     * @param string  $text   the text to be wrapped
     * @param integer $indent number of spaces to use for indentation.
     * @param integer $width
     *
     * @return string the wrapped text.
     * @from yii2
     */
    public static function wrapText($text, $indent = 0, $width = 0): string
    {
        if (!$text) {
            return $text;
        }

        if ((int)$width <= 0) {
            $size = Sys::getScreenSize();

            if ($size === false || $size[0] <= $indent) {
                return $text;
            }

            $width = $size[0];
        }

        $pad   = str_repeat(' ', $indent);
        $lines = explode("\n", wordwrap($text, $width - $indent, "\n", true));
        $first = true;

        foreach ($lines as $i => $line) {
            if ($first) {
                $first = false;
                continue;
            }
            $lines[$i] = $pad . $line;
        }

        return $pad . '  ' . implode("\n", $lines);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public static function alignOptions(array $options): array
    {
        // e.g '-h, --help'
        $hasShort = (bool)strpos(implode('', array_keys($options)), ',');

        if (!$hasShort) {
            return $options;
        }

        $formatted = [];
        foreach ($options as $name => $des) {
            if (!$name = trim($name, ', ')) {
                continue;
            }

            if (!strpos($name, ',')) {
                // padding length equals to '-h, '
                $name = '    ' . $name;
            } else {
                $name = str_replace([' ', ','], ['', ', '], $name);
            }

            $formatted[$name] = $des;
        }

        return $formatted;
    }

    /**
     * ```
     * FormatUtil::memoryUsage(memory_get_usage(true));
     * ```
     *
     * @param float|int $memory
     *
     * @return string
     * @deprecated use Format::memory($secs);
     */
    public static function memoryUsage($memory): string
    {
        return Format::memory($memory);
    }

    /**
     * format timestamp to how long ago
     *
     * @param int $secs
     *
     * @return string
     * @deprecated use Format::howLongAgo($secs);
     */
    public static function howLongAgo(int $secs): string
    {
        return Format::howLongAgo($secs);
    }

    /**
     * Splice array
     *
     * @param array $data
     *     e.g [
     *     'system'  => 'Linux',
     *     'version'  => '4.4.5',
     *     ]
     * @param array $opts
     *
     * @return string
     */
    public static function spliceKeyValue(array $data, array $opts = []): string
    {
        $text = '';
        $opts = array_merge([
            'leftChar'    => '',   // e.g '  ', ' * '
            'sepChar'     => ' ',  // e.g ' | ' OUT: key | value
            'keyStyle'    => '',   // e.g 'info','comment'
            'valStyle'    => '',   // e.g 'info','comment'
            'keyPadPos'   => STR_PAD_RIGHT,
            'keyMinWidth' => 8,
            'keyMaxWidth' => 0, // if not set, will automatic calculation
            'ucFirst'     => true,  // upper first char for value
        ], $opts);

        if ($opts['keyMaxWidth'] < 1) {
            $opts['keyMaxWidth'] = Helper::getKeyMaxWidth($data);
        }

        // compare
        if ((int)$opts['keyMinWidth'] > $opts['keyMaxWidth']) {
            $opts['keyMaxWidth'] = $opts['keyMinWidth'];
        }

        $keyStyle  = trim($opts['keyStyle']);
        $keyPadPos = (int)$opts['keyPadPos'];

        foreach ($data as $key => $value) {
            $hasKey = !is_int($key);
            $text   .= $opts['leftChar'];

            if ($hasKey && $opts['keyMaxWidth']) {
                $key  = Str::pad((string)$key, $opts['keyMaxWidth'], ' ', $keyPadPos);
                $text .= ColorTag::wrap($key, $keyStyle) . $opts['sepChar'];
            }

            // if value is array, translate array to string
            if (is_array($value)) {
                $temp = '[';

                foreach ($value as $k => $val) {
                    if (is_bool($val)) {
                        $val = $val ? '(True)' : '(False)';
                    } else {
                        $val = is_scalar($val) ? (string)$val : JsonHelper::unescaped($val);
                    }

                    $temp .= (!is_numeric($k) ? "$k: " : '') . "$val, ";
                }

                $value = rtrim($temp, ' ,') . ']';
            } elseif (is_bool($value)) {
                $value = $value ? '(True)' : '(False)';
            } else {
                $value = (string)$value;
            }

            $value = $hasKey && $opts['ucFirst'] ? ucfirst($value) : $value;
            $text  .= ColorTag::wrap($value, $opts['valStyle']) . "\n";
        }

        return $text;
    }
}
