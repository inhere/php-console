<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/28
 * Time: 下午10:36
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

spl_autoload_register(function($class)
{
    if (0 === strpos($class,'Inhere\Console\Examples\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Console\examples\\')));
        $file =__DIR__ . "/{$path}.php";

        if (is_file($file)) {
            include $file;
        }

    } elseif (0 === strpos($class,'Inhere\Console\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Console\\')));
        $file = dirname(__DIR__) . "/src/{$path}.php";

        if (is_file($file)) {
            include $file;
        }
    }
});
