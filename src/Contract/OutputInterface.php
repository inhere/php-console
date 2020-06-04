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
     * Write a message to standard output stream.
     *
     * @param mixed       $messages Output message
     * @param bool        $nl       Output with newline
     * @param int|boolean $quit     If is int, setting it is exit code.
     *
     * @return int
     */
    public function write($messages, $nl = true, $quit = false): int;
}
