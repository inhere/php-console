<?php

/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-18
 * Time: 18:57
 */

namespace Inhere\Console\Base;

use Inhere\Console\IO\InputDefinition;

/**
 * Interface BaseCommandInterface
 * @package Inhere\Console\Base
 */
interface BaseCommandInterface
{
    /**
     * run command
     * @param string $command
     * @return int
     */
    public function run($command = '');

    /**
     * @return InputDefinition
     */
    public function getDefinition();

    /**
     * @return ApplicationInterface
     */
    public function getApp();

    /**
     * @return string
     */
    public static function getName();

    /**
     * @return string
     */
    public static function getDescription();
}