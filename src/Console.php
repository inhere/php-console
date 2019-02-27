<?php

namespace Inhere\Console;

use Inhere\Console\Component\Style\Style;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Toolkit\Cli\ColorTag;

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

    /** @var string */
    private static $buffer = '';

    /** @var bool */
    private static $buffering = false;

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
     * @return Style
     */
    public static function style(): Style
    {
        return Style::instance();
    }

    /***********************************************************************************
     * Output message
     ***********************************************************************************/

    /**
     * Format and write message to terminal. like printf()
     * @param string $format
     * @param mixed  ...$args
     * @return int
     */
    public static function writef(string $format, ...$args): int
    {
        return self::write(\sprintf($format, ...$args));
    }

    /**
     * Format and write message to terminal. like printf()
     * @param string $format
     * @param mixed  ...$args
     * @return int
     */
    public static function printf(string $format, ...$args): int
    {
        return self::write(\sprintf($format, ...$args));
    }

    /**
     * Write raw data to stdout, will disable color render.
     * @param string|array $message
     * @param bool         $nl
     * @param bool|int     $quit
     * @param array        $opts
     * @return int
     */
    public static function writeRaw($message, $nl = true, $quit = false, array $opts = []): int
    {
        $opts['color'] = false;
        return self::write($message, $nl, $quit, $opts);
    }

    /**
     * Write data to stdout with newline.
     * @param string|array $message
     * @param array        $opts
     * @param bool|int     $quit
     * @return int
     */
    public static function writeln($message, $quit = false, array $opts = []): int
    {
        return self::write($message, true, $quit, $opts);
    }

    /**
     * Write a message to standard output stream.
     * @param string|array $messages Output message
     * @param boolean      $nl True 会添加换行符, False 原样输出，不添加换行符
     * @param int|boolean  $quit If is int, setting it is exit code. 'True' translate as code 0 and exit, 'False' will not exit.
     * @param array        $opts
     * [
     *     'color'  => bool, // whether render color, default is: True.
     *     'stream' => resource, // the stream resource, default is: STDOUT
     *     'flush'  => bool, // flush the stream data, default is: True
     * ]
     * @return int
     */
    public static function write($messages, $nl = true, $quit = false, array $opts = []): int
    {
        if (\is_array($messages)) {
            $messages = \implode($nl ? \PHP_EOL : '', $messages);
        }

        $messages = (string)$messages;

        if (!isset($opts['color']) || $opts['color']) {
            $messages = Style::instance()->render($messages);
        } else {
            $messages = Style::stripColor($messages);
        }

        // if open buffering
        if (self::isBuffering()) {
            self::$buffer .= $messages . ($nl ? \PHP_EOL : '');

            if (!$quit) {
                return 0;
            }

            $messages = self::$buffer;
            // clear buffer
            self::$buffer = '';
        } else {
            $messages .= $nl ? \PHP_EOL : '';
        }

        \fwrite($stream = $opts['stream'] ?? \STDOUT, $messages);

        if (!isset($opts['flush']) || $opts['flush']) {
            \fflush($stream);
        }

        // if will quit.
        if ($quit !== false) {
            $code = true === $quit ? 0 : (int)$quit;
            exit($code);
        }

        return 0;
    }

    /**
     * Logs data to stdout
     * @param string|array $text
     * @param bool         $nl
     * @param bool|int     $quit
     */
    public static function stdout($text, $nl = true, $quit = false): void
    {
        self::write($text, $nl, $quit);
    }

    /**
     * Logs data to stderr
     * @param string|array $text
     * @param bool         $nl
     * @param bool|int     $quit
     */
    public static function stderr($text, $nl = true, $quit = -200): void
    {
        self::write($text, $nl, $quit, [
            'stream' => \STDERR,
        ]);
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

        $message = \strpos($format, '%') > 0 ? \sprintf($format, ...$args) : $format;

        self::writef('[%s] %s', $taggedName, $message);
    }

    /**
     * Print log message to console
     * @param string $msg
     * @param array  $data
     * @param int    $level
     * @param array  $opts
     * [
     *  '_category' => 'application',
     *  'process' => 'work',
     *  'pid' => 234,
     *  'coId' => 12,
     * ]
     */
    public static function log(string $msg, array $data = [], int $level = self::VERB_DEBUG, array $opts = []): void
    {
        $levelName  = self::LEVEL_NAMES[$level] ?? 'INFO';
        $colorName  = self::LEVEL2TAG[$level] ?? 'info';
        $taggedName = ColorTag::add($levelName, $colorName);

        $userOpts = [];
        foreach ($opts as $n => $v) {
            if (\is_numeric($n) || \strpos($n, '_') === 0) {
                $userOpts[] = "[$v]";
            } else {
                $userOpts[] = "[$n:$v]";
            }
        }

        $optString = $userOpts ? ' ' . \implode(' ', $userOpts) : '';

        self::write(\sprintf(
            '%s [%s]%s %s %s',
            \date('Y/m/d H:i:s'),
            $taggedName,
            $optString,
            \trim($msg),
            $data ? \PHP_EOL . \json_encode($data, \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT) : ''
        ));
    }

    /***********************************************************************************
     * Read message
     ***********************************************************************************/

    /**
     * Read message from STDIN
     *
     * @param mixed $message
     * @param bool  $nl
     * @param array $opts
     * @return string
     */
    public static function read($message = null, bool $nl = false, array $opts = []): string
    {
        if ($message) {
            self::write($message, $nl);
        }

        $opts = \array_merge([
            'length' => 1024,
            'stream' => \STDIN,
        ], $opts);

        return \file_get_contents($opts['stream'], $opts['length']);
    }

    /**
     * Gets line from file pointer
     *
     * @param mixed $message
     * @param bool  $nl
     * @param array $opts
     * [
     *   'stream' => \STDIN
     * ]
     * @return string
     */
    public static function readln($message = null, $nl = false, array $opts = []): string
    {
        if ($message) {
            self::write($message, $nl);
        }

        $opts = \array_merge([
            'length' => 1024,
            'stream' => \STDIN,
        ], $opts);

        return \trim(\fgets($opts['stream'], $opts['length']));
    }

    /**
     * Read input information
     * @param  mixed $message 若不为空，则先输出文本
     * @param  bool  $nl true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public static function readRow($message = null, $nl = false): string
    {
        return self::readln($message, $nl);
    }

    /**
     * Gets line from file pointer and strip HTML tags
     *
     * @param mixed $message
     * @param bool  $nl
     * @param array $opts
     * @return string
     */
    public static function readSafe($message = null, bool $nl = false, array $opts = []): string
    {
        if ($message) {
            self::write($message, $nl);
        }

        $opts = \array_merge([
            'length' => 1024,
            'stream' => \STDIN,
            'allowTags' => null,
        ], $opts);

        return \trim(\fgetss($opts['stream'], $opts['length'], $opts['allowTags']));
    }

    /**
     * Gets first character from file pointer
     * @param string $message
     * @param bool   $nl
     * @return string
     */
    public static function readChar(string $message = '', bool $nl = false): string
    {
        $line = self::readln($message, $nl);

        return $line !== '' ? $line[0] : '';
    }

    /**
     * Read input first char
     * @param string $message
     * @param bool   $nl
     * @return string
     */
    public static function readFirst(string $message = '', bool $nl = false): string
    {
        return self::readChar($message, $nl);
    }

    /***********************************************************************************
     * Output buffer
     ***********************************************************************************/

    /**
     * @return bool
     */
    public static function isBuffering(): bool
    {
        return self::$buffering;
    }

    /**
     * @return string
     */
    public static function getBuffer(): string
    {
        return self::$buffer;
    }

    /**
     * @param string $buffer
     */
    public static function setBuffer(string $buffer): void
    {
        self::$buffer = $buffer;
    }

    /**
     * Start buffering
     */
    public static function startBuffer(): void
    {
        self::$buffering = true;
    }

    /**
     * Clear buffering
     */
    public static function clearBuffer(): void
    {
        self::$buffer = '';
    }

    /**
     * Stop buffering
     * @see write()
     * @param bool  $flush Whether flush buffer to output stream
     * @param bool  $nl Default is False, because the last write() have been added "\n"
     * @param bool  $quit
     * @param array $opts
     * @return string If flush = False, will return all buffer text.
     */
    public static function stopBuffer($flush = true, $nl = false, $quit = false, array $opts = []): string
    {
        self::$buffering = false;

        if ($flush && self::$buffer) {
            // all text have been rendered by Style::render() in every write();
            $opts['color'] = false;

            // flush to stream
            self::write(self::$buffer, $nl, $quit, $opts);

            // clear buffer
            self::$buffer = '';
        }

        return self::$buffer;
    }

    /**
     * Stop buffering and flush buffer text
     * @see write()
     * @param bool  $nl
     * @param bool  $quit
     * @param array $opts
     */
    public static function flushBuffer($nl = false, $quit = false, array $opts = []): void
    {
        self::stopBuffer(true, $nl, $quit, $opts);
    }
}
