<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 16-4-1
 * Time: 上午10:08
 * Used:
 * file: Color.php
 */

namespace Inhere\Console\Util;

use FilesystemIterator;
use Inhere\Console\Concern\RuntimeProfileTrait;
use InvalidArgumentException;
use Iterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Swoole\Coroutine;
use function class_exists;
use function file_exists;
use function is_dir;
use function is_numeric;
use function mb_strlen;
use function mkdir;
use function preg_match;
use function similar_text;
use function sprintf;
use function strpos;

/**
 * Class Helper
 *
 * @package Inhere\Console\Util
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
     * @param int $haystack
     * @param int $value
     *
     * @return bool
     */
    public static function hasMode(int $haystack, int $value): bool
    {
        return ($haystack & $value) > 0;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public static function isAbsPath(string $path): bool
    {
        return strpos($path, '/') === 0 || 1 === preg_match('#^[a-z]:[\/|\\\]{1}.+#i', $path);
    }

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @throws RuntimeException
     */
    public static function mkdir(string $dir, int $mode = 0775): void
    {
        if (!file_exists($dir) && !mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }

    /**
     * @param string   $srcDir
     * @param callable $filter
     * @param int      $flags
     *
     * @return RecursiveIteratorIterator
     * @throws InvalidArgumentException
     */
    public static function directoryIterator(
        string $srcDir,
        callable $filter,
        $flags = FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO
    ): RecursiveIteratorIterator {
        if (!$srcDir || !file_exists($srcDir)) {
            throw new InvalidArgumentException('Please provide a exists source directory.');
        }

        $directory      = new RecursiveDirectoryIterator($srcDir, $flags);
        $filterIterator = new RecursiveCallbackFilterIterator($directory, $filter);

        return new RecursiveIteratorIterator($filterIterator);
    }

    /**
     * @param string $command
     * @param array  $map
     */
    public static function commandSearch(string $command, array $map): void
    {
    }

    /**
     * find similar text from an array|Iterator
     *
     * @param string         $need
     * @param Iterator|array $iterator
     * @param int            $similarPercent
     *
     * @return array
     */
    public static function findSimilar(string $need, $iterator, int $similarPercent = 45): array
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
     * @param array $data
     *     [
     *     'key1'      => 'value1',
     *     'key2-test' => 'value2',
     *     ]
     * @param bool  $expectInt
     *
     * @return int
     */
    public static function getKeyMaxWidth(array $data, bool $expectInt = false): int
    {
        $keyMaxWidth = 0;

        foreach ($data as $key => $value) {
            // key is not a integer
            if (!$expectInt || !is_numeric($key)) {
                $width = mb_strlen((string)$key, 'UTF-8');

                $keyMaxWidth = $width > $keyMaxWidth ? $width : $keyMaxWidth;
            }
        }

        return $keyMaxWidth;
    }

    /**
     * @param string $format
     * @param mixed  ...$args
     */
    public static function throwInvalidArgument(string $format, ...$args): void
    {
        throw new InvalidArgumentException(sprintf($format, ...$args));
    }

    /**
     * @param string $optsStr
     */
    public static function formatOptions(string $optsStr): void
    {
    }
}
