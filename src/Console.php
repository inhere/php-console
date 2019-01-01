<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-31
 * Time: 01:43
 */

namespace Inhere\Console;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

/**
 * Class Console
 * @package Inhere\Console
 */
class Console
{
    // constants for error level 0 - 4. you can setting by '--debug LEVEL'
    public const VERB_QUIET = 0;
    public const VERB_ERROR = 1; // default reporting on error
    public const VERB_WARN  = 2;
    public const VERB_INFO  = 3;
    public const VERB_DEBUG = 4;
    public const VERB_CRAZY = 5;

    // level => name
    public const VERB_NAMES = [
        self::VERB_QUIET => 'QUIET',
        self::VERB_ERROR => 'ERROR',
        self::VERB_WARN  => 'WARN',
        self::VERB_INFO  => 'INFO',
        self::VERB_DEBUG => 'DEBUG',
        self::VERB_CRAZY => 'CRAZY',
    ];

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
     * @param array       $config
     * @param Input|null  $input
     * @param Output|null $output
     * @return Application
     */
    public static function newApp(array $config = [], Input $input = null, Output $output = null)
    {
        return new Application($config, $input, $output);
    }

}
