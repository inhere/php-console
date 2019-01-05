<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:28
 */

namespace Inhere\Console\IO\Input;

use Inhere\Console\IO\Input;

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
    private $isArray;

    /**
     * @param string   $name
     * @param int|null $mode
     * @param string   $description
     * @param null     $default
     * @return static
     */
    public static function make(string $name, int $mode = null, string $description = '', $default = null)
    {
        return new static($name, $mode, $description, $default);
    }

    /**
     * class constructor.
     * @param string   $name
     * @param int|null $mode
     * @param string   $description
     * @param mixed    $default The default value
     *  - for InputArgument::OPTIONAL mode only
     *  - must be null for InputOption::OPT_BOOL
     */
    public function __construct(string $name, int $mode = null, string $description = '', $default = null)
    {
        $this->isArray = $mode === Input::ARG_IS_ARRAY;
    }

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->isArray;
    }
}
