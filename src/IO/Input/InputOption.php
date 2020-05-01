<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:28
 */

namespace Inhere\Console\IO\Input;

/**
 * Class InputOption
 * - definition a input option
 *
 * @package Inhere\Console\IO\Input
 */
class InputOption extends InputItem
{
    /**
     * alias name
     *
     * @var string
     */
    public $alias;

    /**
     * @var string|array
     */
    public $shortcut;

}
