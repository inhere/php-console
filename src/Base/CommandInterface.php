<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-15
 * Time: 9:23
 */

namespace Inhere\Console\Base;

use Inhere\Console\IO\InputDefinition;

/**
 * Interface CommandInterface
 * @package Inhere\Console\Base
 */
interface CommandInterface
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
    public function getApp(): ApplicationInterface;

    /**
     * @return string
     */
    public static function getName(): string;

    /**
     * @return string
     */
    public static function getDescription(): ?string;
}
