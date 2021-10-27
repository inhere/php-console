<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Concern;

use Inhere\Console\Console;
use Toolkit\Stdlib\Helper\JsonHelper;
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
            'stream' => $this->getOutputStream(),
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
     * @param mixed $data
     * @param string $title
     */
    public function prettyJSON($data, string $title = 'JSON:'): void
    {
        if ($title) {
            Console::colored($title, 'ylw0');
        }

        Console::write(JsonHelper::prettyJSON($data));
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
