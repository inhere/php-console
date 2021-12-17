<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Toolkit\Cli\Cli;
use Toolkit\Cli\Color\ColorTag;
use Toolkit\Stdlib\Json;
use function date;
use function debug_backtrace;
use function implode;
use function is_numeric;
use function sprintf;
use function strpos;
use function trim;
use const DEBUG_BACKTRACE_IGNORE_ARGS;
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

    public const CMD_GROUP  = 1;

    public const CMD_SINGLE = 2;

    // eg: CONSOLE_DEBUG=4 php my-console
    public const DEBUG_ENV_KEY = 'CONSOLE_DEBUG';

    /**
     * @var Application|null
     */
    private static ?Application $app;

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
     * @return Input
     */
    public static function getInput(): Input
    {
        return self::$app->getInput();
    }

    /**
     * @return Output
     */
    public static function getOutput(): Output
    {
        return self::$app->getOutput();
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
     * @var int
     */
    public static int $traceIndex = 1;

    /**
     * @param int    $level
     * @param string $format
     * @param mixed  ...$args
     */
    public static function logf(int $level, string $format, ...$args): void
    {
        $datetime  = date('Y/m/d H:i:s');
        $levelName = self::LEVEL_NAMES[$level] ?? 'INFO';
        $colorName = self::LEVEL2TAG[$level] ?? 'info';

        $message = strpos($format, '%') > 0 ? sprintf($format, ...$args) : $format;
        $tagName = ColorTag::add($levelName, $colorName);

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::$traceIndex + 2);
        $position  = self::formatBacktrace($backtrace, self::$traceIndex);

        self::writef('%s [%s] [%s] %s' . PHP_EOL, $datetime, $tagName, $position, $message);
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
    public static function log(int $level, string $msg, array $data = [], array $opts = []): void
    {
        $levelName  = self::LEVEL_NAMES[$level] ?? 'INFO';
        $colorName  = self::LEVEL2TAG[$level] ?? 'info';
        $taggedName = ColorTag::add($levelName, $colorName);

        $userOpts = [];
        $datetime = date('Y/m/d H:i:s');
        foreach ($opts as $n => $v) {
            if (is_numeric($n) || str_starts_with($n, '_')) {
                $userOpts[] = "[$v]";
            } else {
                $userOpts[] = "[$n:$v]";
            }
        }

        $optString  = $userOpts ? ' ' . implode(' ', $userOpts) : '';
        $dataString = $data ? Json::encode($data, JSON_UNESCAPED_SLASHES) : '';

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::$traceIndex + 2);
        $position  = self::formatBacktrace($backtrace, self::$traceIndex);

        self::writef('%s [%s] [%s]%s %s %s' . PHP_EOL, $datetime, $taggedName, $position, $optString, trim($msg), $dataString);
    }

    /**
     * @param array $traces
     * @param int   $index
     *
     * @return string
     */
    private static function formatBacktrace(array $traces, int $index): string
    {
        $position = 'unknown';

        if (isset($traces[$index + 1])) {
            $tInfo = $traces[$index];
            $prev  = $traces[$index + 1];
            $type  = $prev['type'];

            $position = sprintf('%s%s%s(),L%d', $prev['class'], $type, $prev['function'] ?? 'UNKNOWN', $tInfo['line']);
        }

        return ColorTag::add($position, 'green');
    }
}
