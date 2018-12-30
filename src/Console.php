<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-31
 * Time: 01:43
 */

namespace Inhere\Console;

/**
 * Class Console
 * @package Inhere\Console
 */
class Console
{
    /**
     * @var Application
     */
    private static $app;

    /**
     * @return Application
     */
    public static function app()
    {
        return self::$app;
    }

    /**
     * @param Application $app
     */
    public static function setApp(Application $app)
    {
        self::$app = $app;
    }

    /**
     * @param array $config
     * @return Application
     */
    public static function newApp(array $config = [])
    {
        return new Application($config);
    }

}
