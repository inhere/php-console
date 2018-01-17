<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:28
 */

namespace Inhere\Console\IO\Input;

/**
 * Class InputItem
 * - definition a input item(option|argument)
 * @package Inhere\Console\IO\Input
 */
class InputItem
{
    /**
     * @var string
     */
    public $name;

    /**
     * alias name
     * @var string
     */
    public $alias;

    /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $mode;

    /**
     * The argument data type. (eg: 'string', 'array', 'mixed')
     * @var string
     */
    public $type;

    /**
     * default value
     * @var mixed
     */
    public $default;

    /**
     * allow multi value
     * @var bool
     */
    public $multi;
}