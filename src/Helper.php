<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 16-4-1
 * Time: 上午10:08
 * Used:
 * file: Color.php
 */

namespace inhere\console;

/**
 * Class Helper
 * @package inhere\console
 */
class Helper
{
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
     * Returns true if STDOUT supports colorization.
     * This code has been copied and adapted from
     * \Symfony\Component\Console\Output\OutputStream.
     * @return boolean
     */
    public static function isSupportColor()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return
                '10.0.10586' === PHP_WINDOWS_VERSION_MAJOR.'.'.PHP_WINDOWS_VERSION_MINOR.'.'.PHP_WINDOWS_VERSION_BUILD
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM')
               // || 'cygwin' === getenv('TERM')
                ;
        }

        if (!defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(STDOUT);
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
            if ( !$expectInt || !is_numeric($key) ) {
                $width = mb_strlen($key, 'UTF-8');
                $keyMaxWidth = $width > $keyMaxWidth ? $width : $keyMaxWidth;
            }
        }

        return $keyMaxWidth;
    }

    /**
     * spliceArray
     * @param  array  $data
     * e.g [
     *     'system'  => 'Linux',
     *     'version'  => '4.4.5',
     * ]
     * @param  array  $opts
     * @return string
     */
    public static function spliceKeyValue(array $data, array $opts = [])
    {
        $text = '';
        $opts = array_merge([
            'leftChar'    => '',   // e.g '  ', ' * '
            'sepChar'     => ' ',  // e.g ' | ' => OUT: key | value
            'keyStyle'    => '',   // e.g 'info','comment'
            'keyMaxWidth' => null, // if not set, will automatic calculation
        ], $opts);

        if ( !is_numeric($opts['keyMaxWidth']) ) {
            $opts['keyMaxWidth'] = self::getKeyMaxWidth($data);
        }

        $keyStyle = trim($opts['keyStyle']);

        foreach ($data as $key => $value) {
            $text .= $opts['leftChar'];

            if ($opts['keyMaxWidth'] && !is_int($key)) {
                $key = str_pad($key, $opts['keyMaxWidth'], ' ');
                $text .= ( $keyStyle ? "<{$keyStyle}>$key</{$keyStyle}> " : $key ) . $opts['sepChar'];
            }

            // if value is array, translate array to string
            if ( is_array($value) ) {
                $temp = '';

                foreach ($value as $k => $val) {
                    if (is_bool($val)) {
                        $val = $val ? 'True' : 'False';
                    } else {
                        $val = (string)$val;
                    }

                    $temp .= (!is_numeric($k) ? "$k: " : '') . "<info>$val</info>, ";
                }

                $value = rtrim($temp, ' ,');
            } else if (is_bool($value)) {
                $value = $value ? 'True' : 'False';
            } else {
                $value = (string)$value;
            }

            $value = ucfirst($value);
            $text .= "$value\n";
        }

        return $text;
    }

    // next: form yii2

    /**
     * Returns true if the console is running on windows
     * @return boolean
     */
    public static function isOnWindows()
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

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

        if (static::isOnWindows()) {
            $output = [];
            exec('mode con', $output);
            if (isset($output, $output[1]) && strpos($output[1], 'CON') !== false) {
                return $size = [(int) preg_replace('~\D~', '', $output[3]), (int) preg_replace('~\D~', '', $output[4])];
            }
        } else {
            // try stty if available
            $stty = [];
            if (exec('stty -a 2>&1', $stty) && preg_match('/rows\s+(\d+);\s*columns\s+(\d+);/mi', implode(' ', $stty), $matches)) {
                return $size = [$matches[2], $matches[1]];
            }

            // fallback to tput, which may not be updated on terminal resize
            if (($width = (int) exec('tput cols 2>&1')) > 0 && ($height = (int) exec('tput lines 2>&1')) > 0) {
                return $size = [$width, $height];
            }

            // fallback to ENV variables, which may not be updated on terminal resize
            if (($width = (int) getenv('COLUMNS')) > 0 && ($height = (int) getenv('LINES')) > 0) {
                return $size = [$width, $height];
            }
        }

        return $size = false;
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
     * @since 2.0.4
     */
    public static function wrapText($text, $indent = 0, $width = 0)
    {
        if (!$text) {
            return $text;
        }

        if ( (int)$width <= 0 ) {
            $size = static::getScreenSize();

            if ( $size === false || $size[0] <= $indent) {
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
}
