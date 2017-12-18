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
    public function helpCommand();

    public function showCommandList();

    /**
     * @return string
     */
    public function getAction();

    /**
     * @return string
     */
    public function getDefaultAction();

    /**
     * @param string $delimiter
     */
    public function setDelimiter($delimiter);
}