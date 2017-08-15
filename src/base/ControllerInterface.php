<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-15
 * Time: 9:23
 */

namespace inhere\console\base;

/**
 * interface ControllerInterface
 * @package inhere\console\base
 */
interface ControllerInterface
{
    /**
     * @return int
     */
    public function helpCommand();

    /**
     * @return string
     */
    public function getAction(): string;

    /**
     * @return string
     */
    public function getDefaultAction(): string;
}