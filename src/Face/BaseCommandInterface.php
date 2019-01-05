<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-18
 * Time: 18:57
 */

namespace Inhere\Console\Face;

use Inhere\Console\AbstractApplication;
use Inhere\Console\IO\InputDefinition;

/**
 * Interface BaseCommandInterface
 * @package Inhere\Console\Face
 */
interface BaseCommandInterface
{
    public const OK  = 0;
    public const ERR = 2;

    // name -> {name}
    public const ANNOTATION_VAR = '{%s}'; // '{$%s}';

    /**
     * run command
     * @param string $command
     * @return int|mixed return int is exit code. other is command exec result.
     */
    public function run(string $command = '');

    /**
     * @return InputDefinition|null
     */
    public function getDefinition();

    /**
     * @return AbstractApplication|ApplicationInterface
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
