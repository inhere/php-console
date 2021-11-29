<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

spl_autoload_register(static function ($class): void {
    $file = null;

    if (str_starts_with($class, 'Inhere\Console\Examples\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Console\Examples\\')));
        $file = dirname(__DIR__) . "/examples/$path.php";
    } elseif (str_starts_with($class, 'Inhere\ConsoleTest\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\ConsoleTest\\')));
        $file = __DIR__ . "/$path.php";
    } elseif (str_starts_with($class, 'Inhere\Console\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Console\\')));
        $file = dirname(__DIR__) . "/src/$path.php";
    }

    if ($file && is_file($file)) {
        include $file;
    }
});

if (is_file(dirname(__DIR__, 3) . '/autoload.php')) {
    require dirname(__DIR__, 3) . '/autoload.php';
} elseif (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    require dirname(__DIR__) . '/vendor/autoload.php';
}
