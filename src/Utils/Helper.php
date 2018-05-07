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

use Inhere\Console\Traits\RuntimeProfileTrait;
use Swoole\Coroutine;

/**
 * Class Helper
 * @package Inhere\Console\Utils
 */
class Helper
{
    use RuntimeProfileTrait;

    /**
     * @return bool
     */
    public static function isSupportCoroutine(): bool
    {
        return class_exists(Coroutine::class, false);
    }

    /**
     * @return bool
     */
    public static function inCoroutine(): bool
    {
        if (self::isSupportCoroutine()) {
            return Coroutine::getuid() > 0;
        }

        return false;
    }

    /**
     * 给对象设置属性值
     * @param $object
     * @param array $options
     */
    public static function init($object, array $options)
    {
        foreach ($options as $property => $value) {
            $object->$property = $value;
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isAbsPath(string $path): bool
    {
        return $path{0} === '/' || 1 === preg_match('#^[a-z]:[\/|\\\]{1}.+#i', $path);
    }

    /**
     * @param string $dir
     * @param int $mode
     * @throws \RuntimeException
     */
    public static function mkdir($dir, $mode = 0775)
    {
        if (!file_exists($dir) && !mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }

    /**
     * @param string $srcDir
     * @param callable $filter
     * @param int $flags
     * @return \RecursiveIteratorIterator
     * @throws \InvalidArgumentException
     */
    public static function directoryIterator(
        string $srcDir,
        callable $filter,
        $flags = \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO
    ): \RecursiveIteratorIterator
    {
        if (!$srcDir || !file_exists($srcDir)) {
            throw new \InvalidArgumentException('Please provide a exists source directory.');
        }

        $directory = new \RecursiveDirectoryIterator($srcDir, $flags);
        $filterIterator = new \RecursiveCallbackFilterIterator($directory, $filter);

        return new \RecursiveIteratorIterator($filterIterator);
    }

    /**
     * @param string $command
     * @param array $map
     */
    public static function commandSearch(string $command, array $map)
    {

    }

    /**
     * wrap a style tag
     * @param string $string
     * @param string $tag
     * @return string
     */
    public static function wrapTag($string, $tag): string
    {
        if (!$string) {
            return '';
        }

        if (!$tag) {
            return $string;
        }

        return "<$tag>$string</$tag>";
    }

    /**
     * clear Ansi Code
     * @param string $string
     * @return string
     */
    public static function stripAnsiCode($string): string
    {
        return (string)preg_replace('/\033\[[\d;?]*\w/', '', $string);
    }

    /**
     * @param $string
     * @return int
     */
    public static function strUtf8Len($string): int
    {
        // strlen: one chinese is 3 char.
        // mb_strlen: one chinese is 1 char.
        // mb_strwidth: one chinese is 2 char.
        return mb_strlen($string, 'utf-8');
    }

    /**
     * from Symfony
     * @param $string
     * @return int
     */
    public static function strLen($string): int
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return \strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }

    /**
     * @param string $string
     * @param int $indent
     * @param string $padStr
     * @return string
     */
    public static function strPad(string $string, $indent, $padStr): string
    {
        return $indent > 0 ? str_pad($string, $indent, $padStr) : $string;
    }

    /**
     * findValueByNodes
     * @param  array $data
     * @param  array $nodes
     * @param  mixed $default
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
     * find similar text from an array|Iterator
     * @param string $need
     * @param \Iterator|array $iterator
     * @param int $similarPercent
     * @return array
     */
    public static function findSimilar($need, $iterator, $similarPercent = 45): array
    {
        if (!$need) {
            return [];
        }

        // find similar command names by similar_text()
        $similar = [];

        foreach ($iterator as $name) {
            similar_text($need, $name, $percent);

            if ($similarPercent <= (int)$percent) {
                $similar[] = $name;
            }
        }

        return $similar;
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
    public static function getKeyMaxWidth(array $data, $expectInt = false): int
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
     * dump vars
     * @param array ...$args
     * @return string
     */
    public static function dumpVars(...$args): string
    {
        ob_start();
        var_dump(...$args);
        $string = ob_get_clean();

        return preg_replace("/=>\n\s+/", '=> ', trim($string));
    }

    /**
     * print vars
     * @param array ...$args
     * @return string
     */
    public static function printVars(...$args): string
    {
        $string = '';

        foreach ($args as $arg) {
            $string .= print_r($arg, 1) . PHP_EOL;
        }

        return preg_replace("/Array\n\s+\(/", 'Array (', trim($string));
    }
}
