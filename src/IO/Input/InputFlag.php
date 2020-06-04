<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:28
 */

namespace Inhere\Console\IO\Input;

use Inhere\Console\Contract\InputFlagInterface;
use Inhere\Console\IO\Input;

/**
 * Class InputFlag
 * - definition a input item(option|argument)
 *
 * @package Inhere\Console\IO\Input
 */
abstract class InputFlag implements InputFlagInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $mode;

    /**
     * The argument data type. (eg: 'int', 'bool', 'string', 'array', 'mixed')
     *
     * @var string
     */
    private $type = '';

    /**
     * The default value
     *
     * @var mixed
     */
    private $default;

    /**
     * @param string $name
     * @param int    $mode see Input::ARG_* or Input::OPT_*
     * @param string $description
     * @param null   $default
     *
     * @return static
     */
    public static function make(string $name, int $mode = 0, string $description = '', $default = null)
    {
        return new static($name, $mode, $description, $default);
    }

    /**
     * Class constructor.
     *
     * @param string $name
     * @param int    $mode      see Input::ARG_* or Input::OPT_*
     * @param string $description
     * @param mixed  $default   The default value
     *                          - for Input::ARG_OPTIONAL mode only
     *                          - must be null for InputOption::OPT_BOOL
     */
    public function __construct(string $name, int $mode = 0, string $description = '', $default = null)
    {
        $this->name = $name;
        $this->mode = $mode;

        $this->default = $default;
        $this->setDescription($description);
    }

    /******************************************************************
     * mode value
     *****************************************************************/

    /**
     * @param int $mode
     *
     * @return bool
     */
    public function hasMode(int $mode): bool
    {
        return ($this->mode & $mode) > 0;
    }


    /******************************************************************
     *
     *****************************************************************/

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     */
    public function setDefault($default): void
    {
        $this->default = $default;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'mode'        => $this->mode,
            'type'        => $this->type,
            'default'     => $this->default,
            'isArray'     => $this->isArray(),
            'isOptional'  => $this->isOptional(),
            'isRequired'  => $this->isRequired(),
            'description' => $this->description,
        ];
    }
}
