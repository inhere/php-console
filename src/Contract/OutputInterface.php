<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Contract;

/**
 * Class OutputInterface
 *
 * @package Inhere\Console\Contract
 */
interface OutputInterface
{
    /**
     * Write a message to output
     *
     * @param string $content
     *
     * @return int
     */
    public function write(string $content): int;

    /**
     * Write a message to output with newline
     *
     * @param string|int|array|mixed $content
     * @param bool $quit
     * @param array $opts
     *
     * @return int
     */
    public function writeln(mixed $content, bool $quit = false, array $opts = []): int;

    /**
     * Write a message to output with format.
     *
     * @param string $format
     * @param mixed ...$args
     *
     * @return int
     */
    public function writef(string $format, ...$args): int;

    /**
     * Whether the stream is an interactive terminal
     */
    public function isInteractive(): bool;
}
