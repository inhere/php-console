<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Decorate;

use Inhere\Console\Component\Formatter\JSONPretty;
use Inhere\Console\Console;
use Toolkit\Stdlib\Json;
use Toolkit\Stdlib\Php;
use function array_merge;
use function count;
use function implode;
use const PHP_EOL;

/**
 * Class FormatOutputAwareTrait
 *
 * @package Inhere\Console\Decorate
 */
trait FormatOutputAwareTrait
{
    use StyledOutputAwareTrait;

    /**
     * @inheritdoc
     * @see Console::write()
     */
    public function write($messages, $nl = true, $quit = false, array $opts = []): int
    {
        return Console::write($messages, $nl, $quit, array_merge([
            'flush'  => true,
            'stream' => $this->getOutputStream(),
        ], $opts));
    }

    /**
     * @param ...$args
     */
    public function echo(...$args): void
    {
        echo count($args) > 1 ? implode(' ', $args) : $args[0];
    }

    /**
     * @param ...$args
     */
    public function echoln(...$args): void
    {
        echo (count($args) > 1 ? implode(' ', $args) : $args[0]), PHP_EOL;
    }

    /**
     * @param string $format
     * @param mixed ...$args
     *
     * @return int
     */
    public function writef(string $format, ...$args): int
    {
        return Console::printf($format, ...$args);
    }

    /**
     * @param string $format
     * @param mixed  ...$args
     */
    public function printf(string $format, ...$args): void
    {
        Console::printf($format, ...$args);
    }

    /**
     * @param string|mixed $text
     * @param bool|int $quit
     * @param array $opts
     *
     * @return int
     */
    public function writeln($text, bool $quit = false, array $opts = []): int
    {
        return Console::writeln($text, $quit, $opts);
    }

    /**
     * @param string|mixed $text
     * @param bool         $quit
     * @param array        $opts
     *
     * @return int
     */
    public function println(mixed $text, bool $quit = false, array $opts = []): int
    {
        return Console::writeln($text, $quit, $opts);
    }

    /**
     * @param string|mixed $text
     * @param bool         $nl
     * @param bool|int $quit
     * @param array        $opts
     *
     * @return int
     */
    public function writeRaw(mixed $text, bool $nl = true, bool $quit = false, array $opts = []): int
    {
        return Console::writeRaw($text, $nl, $quit, $opts);
    }

    /**
     * @param mixed $data
     * @param bool  $echo
     * @param int   $flags
     *
     * @return int|string
     */
    public function json(
        mixed $data,
        bool $echo = true,
        int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ): int|string {
        $string = Json::encode($data, $flags);

        if ($echo) {
            return Console::write($string);
        }

        return $string;
    }

    /**
     * @param mixed $data
     * @param string $title
     */
    public function prettyJSON(mixed $data, string $title = 'JSON:'): void
    {
        if ($title) {
            Console::colored($title, 'ylw0');
        }

        Console::write(JSONPretty::pretty($data));
    }

    /**
     * @param mixed ...$vars
     */
    public function dump(...$vars): void
    {
        Console::writeRaw(Php::dumpVars(...$vars));
    }

    /**
     * @param mixed ...$vars
     */
    public function prints(...$vars): void
    {
        Console::writeRaw(Php::printVars(...$vars));
    }
}
