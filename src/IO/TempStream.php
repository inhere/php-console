<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO;

use RuntimeException;
use function fclose;
use function fopen;
use function fread;
use function fwrite;
use function is_resource;
use function strlen;

/**
 * Class TempStream
 *
 * @package Inhere\Console\IO
 */
class TempStream
{
    /**
     * @var resource
     */
    private static $tempFd;

    /**
     * create temp stream
     *
     * @return resource
     */
    public static function create()
    {
        self::$tempFd = fopen('php://memory', 'wb');
        if (!is_resource(self::$tempFd)) {
            throw new RuntimeException('create temp memory stream fail');
        }

        return self::$tempFd;
    }

    /**
     * reset data
     */
    public static function reset(): void
    {
        if (is_resource(self::$tempFd)) {
            fclose(self::$tempFd);
            self::create();
        }
    }

    /**
     * close stream
     */
    public static function close(): void
    {
        if (is_resource(self::$tempFd)) {
            fclose(self::$tempFd);
        }
    }

    /**
     * @param string|int|mixed $string
     */
    public static function write(mixed $string): void
    {
        fwrite(self::$tempFd, (string)$string);
    }

    /**
     * @return string
     */
    public static function read(): string
    {
        $string = '';
        $length = 1024;
        while (true) {
            $part = fread(self::$tempFd, $length);
            if ($part === false) {
                break;
            }

            if (strlen($part) < $length) {
                break;
            }

            $string .= $part;
        }

        return $string;
    }
}
