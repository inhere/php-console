<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-06-20
 * Time: 15:10
 */

namespace Inhere\Console\Concern;

use Inhere\Console\Console;
use Toolkit\Stdlib\Php;
use function array_merge;
use function json_encode;

/**
 * Class FormatOutputAwareTrait
 *
 * @package Inhere\Console\Traits
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
            'stream' => $this->outputStream,
        ], $opts));
    }

    /**
     * @param string $format
     * @param mixed  ...$args
     */
    public function writef(string $format, ...$args): void
    {
        Console::printf($format, ...$args);
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
     * @param bool         $quit
     * @param array        $opts
     *
     * @return int
     */
    public function writeln($text, $quit = false, array $opts = []): int
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
    public function println($text, bool $quit = false, array $opts = []): int
    {
        return Console::writeln($text, $quit, $opts);
    }

    /**
     * @param string|mixed $text
     * @param bool         $nl
     * @param bool|int     $quit
     * @param array        $opts
     *
     * @return int
     */
    public function writeRaw($text, bool $nl = true, $quit = false, array $opts = []): int
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
        $data,
        bool $echo = true,
        int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) {
        $string = json_encode($data, $flags);

        if ($echo) {
            return Console::write($string);
        }

        return $string;
    }

    /**
     * @param mixed ...$vars
     */
    public function dump(...$vars): void
    {
        Console::write(Php::dumpVars(...$vars));
    }

    /**
     * @param mixed ...$vars
     */
    public function prints(...$vars): void
    {
        Console::write(Php::printVars(...$vars));
    }
}
