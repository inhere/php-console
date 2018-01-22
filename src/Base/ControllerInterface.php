<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-15
 * Time: 9:23
 */

namespace Inhere\Console\Base;

/**
 * interface ControllerInterface
 * @package Inhere\Console\Base
 */
interface ControllerInterface
{
    /**
     * @return int
     */
    public function helpCommand(): int;

    /**
     * show command list of the controller class
     */
    public function showCommandList();

    /**
     * @return string
     */
    public function getAction(): string;

    /**
     * @return string
     */
    public function getDefaultAction(): string;

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter);
}
