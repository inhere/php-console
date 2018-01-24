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
    const OK = 0;

    // name -> {name}
    const ANNOTATION_VAR = '{%s}'; // '{$%s}';

    /**
     * run command
     * @param string $command
     * @return int
     */
    public function run(string $command = ''): int;

    /**
     * @return InputDefinition|null
     */
    public function getDefinition();

    /**
     * @return AbstractApplication
     */
    public function getApp(): AbstractApplication;

    /**
     * @return string
     */
    public static function getName(): string;

    /**
     * @return string
     */
    public static function getDescription(): string;
}
