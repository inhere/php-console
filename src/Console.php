<?php declare(strict_types=1);

namespace Inhere\Console;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Toolkit\Cli\Cli;
use Toolkit\Cli\ColorTag;
use function date;
use function implode;
use function is_numeric;
use function json_encode;
use function sprintf;
use function strpos;
use function trim;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

/**
 * Class Console
 *
 * @package Inhere\Console
 */
class Console extends Cli
{
    // constants for error level 0 - 4. you can setting by '--debug LEVEL'
    public const VERB_QUIET = 0;

    public const VERB_ERROR = 1; // default reporting on error

    public const VERB_WARN = 2;

    public const VERB_INFO = 3;

    public const VERB_DEBUG = 4;

    public const VERB_CRAZY = 5;

    // level => name
    public const LEVEL_NAMES = [
        self::VERB_QUIET => 'QUIET',
        self::VERB_ERROR => 'ERROR',
        self::VERB_WARN  => 'WARN',
        self::VERB_INFO  => 'INFO',
        self::VERB_DEBUG => 'DEBUG',
        self::VERB_CRAZY => 'CRAZY',
    ];

    public const LEVEL2TAG = [
        self::VERB_QUIET => 'normal',
        self::VERB_ERROR => 'error',
        self::VERB_WARN  => 'warning',
        self::VERB_INFO  => 'info',
        self::VERB_DEBUG => 'cyan',
        self::VERB_CRAZY => 'magenta',
    ];

    /**
     * @var Application
     */
    private static $app;

    /**
     * @return Application
     */
    public static function app(): Application
    {
        return self::$app;
    }

    /**
     * @param Application $app
     */
    public static function setApp(Application $app): void
    {
        self::$app = $app;
    }

    /**
     * @param array       $config
     * @param Input|null  $input
     * @param Output|null $output
     *
     * @return Application
     */
    public static function newApp(
        array $config = [],
        Input $input = null,
        Output $output = null
    ): Application {
        return new Application($config, $input, $output);
    }

    /**
     * @param int    $level
     * @param string $format
     * @param mixed  ...$args
     */
    public static function logf(int $level, string $format, ...$args): void
    {
        $levelName  = self::LEVEL_NAMES[$level] ?? 'INFO';
        $colorName  = self::LEVEL2TAG[$level] ?? 'info';
        $taggedName = ColorTag::add($levelName, $colorName);

        $message = strpos($format, '%') > 0 ? sprintf($format, ...$args) : $format;

        self::writef('[%s] %s', $taggedName, $message);
    }

    /**
     * Print log message to console
     *
     * @param string $msg
     * @param array  $data
     * @param int    $level
     * @param array  $opts
     *  [
     *  '_category' => 'application',
     *  'process' => 'work',
     *  'pid' => 234,
     *  'coId' => 12,
     *  ]
     */
    public static function log(string $msg, array $data = [], int $level = self::VERB_DEBUG, array $opts = []): void
    {
        $levelName  = self::LEVEL_NAMES[$level] ?? 'INFO';
        $colorName  = self::LEVEL2TAG[$level] ?? 'info';
        $taggedName = ColorTag::add($levelName, $colorName);

        $userOpts = [];
        foreach ($opts as $n => $v) {
            if (is_numeric($n) || strpos($n, '_') === 0) {
                $userOpts[] = "[$v]";
            } else {
                $userOpts[] = "[$n:$v]";
            }
        }

        $optString  = $userOpts ? ' ' . implode(' ', $userOpts) : '';
        $dataString = $data ? PHP_EOL . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : '';

        self::writef('%s [%s]%s %s %s', date('Y/m/d H:i:s'), $taggedName, $optString, trim($msg), $dataString);
    }
}
