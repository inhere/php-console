<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 16-4-1
 * Time: 上午10:08
 * Used:
 * file: Color.php
 */

namespace Inhere\Console\Utils;

/**
 * Class Helper
 * @package Inhere\Console\Utils
 */
class Helper
{
    /**
     * Returns true if the console is running on windows
     * @return boolean
     */
    public static function isOnWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Returns true if STDOUT supports colorization.
     * This code has been copied and adapted from
     * \Symfony\Component\Console\Output\OutputStream.
     * @return boolean
     */
    public static function isSupportColor()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return
                '10.0.10586' === PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . '.' . PHP_WINDOWS_VERSION_BUILD ||
                // 0 == strpos(PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . PHP_WINDOWS_VERSION_BUILD, '10.') ||
                false !== getenv('ANSICON') ||
                'ON' === getenv('ConEmuANSI') ||
                'xterm' === getenv('TERM')// || 'cygwin' === getenv('TERM')
                ;
        }

        if (!defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(STDOUT);
    }

    /**
     * @return bool
     */
    public function isSupport256Color()
    {
        return DIRECTORY_SEPARATOR === '/' && strpos(getenv('TERM'), '256color') !== false;
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     * @param  int|resource $fileDescriptor
     * @return boolean
     */
    public static function isInteractive($fileDescriptor)
    {
        return function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
    }

    /**
     * 给对象设置属性值
     * @param $object
     * @param array $options
     */
    public static function loadAttrs($object, array $options)
    {
        foreach ($options as $property => $value) {
            $object->$property = $value;
        }
    }

    /**
     * clear Ansi Code
     * @param $string
     * @return mixed
     */
    public static function stripAnsiCode($string)
    {
        return preg_replace('/\033\[[\d;?]*\w/', '', $string);
    }

    /**
     * from Symfony
     * @param $string
     * @return int
     */
    public static function strLen($string)
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }

    /**
     * @param $name
     * @return mixed|string
     */
    public static function transName($name)
    {
        $name = trim($name, '-_');

        // convert 'first-second' to 'firstSecond'
        if (strpos($name, '-')) {
            $name = ucwords(str_replace('-', ' ', $name));
            $name = str_replace(' ', '', lcfirst($name));
        }

        return $name;
    }

    /**
     * findValueByNodes
     * @param  array  $data
     * @param  array  $nodes
     * @param  mixed  $default
     * @return mixed
     */
    public static function findValueByNodes(array $data, array $nodes, $default = null)
    {
        $temp = $data;

        foreach ($nodes as $name) {
            if (isset($temp[$name])) {
                $temp = $temp[$name];
            } else {
                $temp = $default;
                break;
            }
        }

        return $temp;
    }

    /**
     * @param $string
     * @param $width
     * @return array
     */
    public static function splitStringByWidth($string, $width)
    {
        // str_split is not suitable for multi-byte characters, we should use preg_split to get char array properly.
        // additionally, array_slice() is not enough as some character has doubled width.
        // we need a function to split string not by character count but by string width
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return str_split($string, $width);
        }

        $utf8String = mb_convert_encoding($string, 'utf8', $encoding);
        $lines = array();
        $line = '';
        foreach (preg_split('//u', $utf8String) as $char) {
            // test if $char could be appended to current line
            if (mb_strwidth($line.$char, 'utf8') <= $width) {
                $line .= $char;
                continue;
            }
            // if not, push current line to array and make new line
            $lines[] = str_pad($line, $width);
            $line = $char;
        }
        if ('' !== $line) {
            $lines[] = count($lines) ? str_pad($line, $width) : $line;
        }

        mb_convert_variables($encoding, 'utf8', $lines);

        return $lines;
    }

    /**
     * @param $memory
     * @return string
     * ```
     * Helper::formatMemory(memory_get_usage(true));
     * ```
     */
    public static function formatMemory($memory)
    {
        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.1f GiB', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.1f MiB', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%d KiB', $memory / 1024);
        }

        return sprintf('%d B', $memory);
    }

    /**
     * formatTime
     * @param  int $secs
     * @return string
     */
    public static function formatTime($secs)
    {
        static $timeFormats = [
            [0, '< 1 sec'],
            [1, '1 sec'],
            [2, 'secs', 1],
            [60, '1 min'],
            [120, 'mins', 60],
            [3600, '1 hr'],
            [7200, 'hrs', 3600],
            [86400, '1 day'],
            [172800, 'days', 86400],
        ];

        foreach ($timeFormats as $index => $format) {
            if ($secs >= $format[0]) {
                if ((isset($timeFormats[$index + 1]) && $secs < $timeFormats[$index + 1][0])
                    || $index === count($timeFormats) - 1
                ) {
                    if (2 === count($format)) {
                        return $format[1];
                    }

                    return floor($secs / $format[2]).' '.$format[1];
                }
            }
        }

        return date('Y-m-d H:i:s', $secs);
    }

    /**
     * get key Max Width
     *
     * @param  array $data
     * [
     *     'key1'      => 'value1',
     *     'key2-test' => 'value2',
     * ]
     * @param bool $expectInt
     * @return int
     */
    public static function getKeyMaxWidth(array $data, $expectInt = true)
    {
        $keyMaxWidth = 0;

        foreach ($data as $key => $value) {
            // key is not a integer
            if (!$expectInt || !is_numeric($key)) {
                $width = mb_strlen($key, 'UTF-8');
                $keyMaxWidth = $width > $keyMaxWidth ? $width : $keyMaxWidth;
            }
        }

        return $keyMaxWidth;
    }

    /**
     * spliceArray
     * @param  array $data
     * e.g [
     *     'system'  => 'Linux',
     *     'version'  => '4.4.5',
     * ]
     * @param  array $opts
     * @return string
     */
    public static function spliceKeyValue(array $data, array $opts = [])
    {
        $text = '';
        $opts = array_merge([
            'leftChar' => '',   // e.g '  ', ' * '
            'sepChar' => ' ',  // e.g ' | ' => OUT: key | value
            'keyStyle' => '',   // e.g 'info','comment'
            'keyMaxWidth' => null, // if not set, will automatic calculation
            'ucfirst' => true, // if not set, will automatic calculation
        ], $opts);

        if (!is_numeric($opts['keyMaxWidth'])) {
            $opts['keyMaxWidth'] = self::getKeyMaxWidth($data);
        }

        $keyStyle = trim($opts['keyStyle']);

        foreach ($data as $key => $value) {
            $hasKey = !is_int($key);
            $text .= $opts['leftChar'];

            if ($hasKey && $opts['keyMaxWidth']) {
                $key = str_pad($key, $opts['keyMaxWidth'], ' ');
                $text .= ($keyStyle ? "<{$keyStyle}>$key</{$keyStyle}> " : $key) . $opts['sepChar'];
            }

            // if value is array, translate array to string
            if (is_array($value)) {
                $temp = '';

                /** @var array $value */
                foreach ($value as $k => $val) {
                    if (is_bool($val)) {
                        $val = $val ? 'True' : 'False';
                    } else {
                        $val = is_scalar($val) ? (string)$val : gettype($val);
                    }

                    $temp .= (!is_numeric($k) ? "$k: " : '') . "<info>$val</info>, ";
                }

                $value = rtrim($temp, ' ,');
            } else if (is_bool($value)) {
                $value = $value ? 'True' : 'False';
            } else {
                $value = (string)$value;
            }

            $value = $hasKey && $opts['ucfirst'] ? ucfirst($value) : $value;
            $text .= "$value\n";
        }

        return $text;
    }

    // next: form yii2

    /**
     * Usage: list($width, $height) = ConsoleHelper::getScreenSize();
     *
     * @param boolean $refresh whether to force checking and not re-use cached size value.
     * This is useful to detect changing window size while the application is running but may
     * not get up to date values on every terminal.
     * @return array|boolean An array of ($width, $height) or false when it was not able to determine size.
     */
    public static function getScreenSize($refresh = false)
    {
        static $size;
        if ($size !== null && !$refresh) {
            return $size;
        }

        if (self::isOnWindows()) {
            $output = [];
            exec('mode con', $output);

            if (isset($output, $output[1]) && strpos($output[1], 'CON') !== false) {
                return ($size = [
                    (int)preg_replace('~\D~', '', $output[3]),
                    (int)preg_replace('~\D~', '', $output[4])
                ]);
            }
        } else {
            // try stty if available
            $stty = [];
            if (exec('stty -a 2>&1', $stty) && preg_match('/rows\s+(\d+);\s*columns\s+(\d+);/mi', implode(' ', $stty), $matches)) {
                return ($size = [$matches[2], $matches[1]]);
            }

            // fallback to tput, which may not be updated on terminal resize
            if (($width = (int)exec('tput cols 2>&1')) > 0 && ($height = (int)exec('tput lines 2>&1')) > 0) {
                return ($size = [$width, $height]);
            }

            // fallback to ENV variables, which may not be updated on terminal resize
            if (($width = (int)getenv('COLUMNS')) > 0 && ($height = (int)getenv('LINES')) > 0) {
                return ($size = [$width, $height]);
            }
        }

        return ($size = false);
    }

    /**
     * Word wrap text with indentation to fit the screen size
     *
     * If screen size could not be detected, or the indentation is greater than the screen size, the text will not be wrapped.
     *
     * The first line will **not** be indented, so `Console::wrapText("Lorem ipsum dolor sit amet.", 4)` will result in the
     * following output, given the screen width is 16 characters:
     *
     * ```
     * Lorem ipsum
     *     dolor sit
     *     amet.
     * ```
     *
     * @param string $text the text to be wrapped
     * @param integer $indent number of spaces to use for indentation.
     * @param integer $width
     * @return string the wrapped text.
     * @from yii2
     */
    public static function wrapText($text, $indent = 0, $width = 0)
    {
        if (!$text) {
            return $text;
        }

        if ((int)$width <= 0) {
            $size = static::getScreenSize();

            if ($size === false || $size[0] <= $indent) {
                return $text;
            }

            $width = $size[0];
        }

        $pad = str_repeat(' ', $indent);
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
     * dump vars
     * @param array ...$args
     * @return string
     */
    public static function dumpVars(...$args)
    {
        ob_start();
        var_dump(...$args);
        $string = ob_get_clean();

        return preg_replace("/=>\n\s+/", '=> ', $string);
    }

    /**
     * print vars
     * @param array ...$args
     * @return string
     */
    public static function printVars(...$args)
    {
        ob_start();

        foreach ($args as $arg) {
            print_r($arg);
        }

        $string = ob_get_clean();

        return preg_replace("/Array\n\s+\(/", 'Array (', $string);
    }
}
