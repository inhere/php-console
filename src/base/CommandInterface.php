<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-15
 * Time: 9:23
 */

namespace inhere\console\base;

use inhere\console\io\InputDefinition;

/**
 * Interface CommandInterface
 * @package inhere\console\base
 */
interface CommandInterface
{
    /**
     * run command
     * @return int
     */
    public function run();

    /**
     * @return InputDefinition
     */
    public function getDefinition();

    /**
     * @return AppInterface
     */
    public function getApp(): AppInterface;
}