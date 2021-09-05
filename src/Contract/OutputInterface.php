<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/4/23 0023
 * Time: 10:22
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
     * @param string $content
     *
     * @return int
     */
    public function writeln(string $content): int;

    /**
     * Whether the stream is an interactive terminal
     */
    public function isInteractive() : bool;
}
