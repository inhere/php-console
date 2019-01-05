<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/4/23 0023
 * Time: 10:22
 */

namespace Inhere\Console\IO;

/**
 * Class OutputInterface
 * @package Inhere\Console\IO
 */
interface OutputInterface
{
    /**
     * Write a message to standard output stream.
     * @param  mixed       $messages Output message
     * @param  bool        $nl true 会添加换行符 false 原样输出，不添加换行符
     * @param  int|boolean $quit If is int, setting it is exit code.
     * @return static
     */
    public function write($messages, $nl = true, $quit = false);
}
