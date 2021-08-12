<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:28
 */

namespace Inhere\Console\Flag;

use Inhere\Console\Contract\InputFlagInterface;

/**
 * Class Flag
 * - - definition a input flag item(option|argument)
 *
 * @package Inhere\Console\IO\Input
 */
abstract class Flag implements InputFlagInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $desc;

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
     * @param string $desc
     * @param mixed|null   $default
     *
     * @return static
     */
    public static function make(string $name, int $mode = 0, string $desc = '', $default = null)
    {
        return new static($name, $mode, $desc, $default);
    }

    /**
     * Class constructor.
     *
     * @param string $name
     * @param int    $mode      see Input::ARG_* or Input::OPT_*
     * @param string $desc
     * @param mixed  $default   The default value
     *                          - for Input::ARG_OPTIONAL mode only
     *                          - must be null for InputOption::OPT_BOOL
     */
    public function __construct(string $name, int $mode = 0, string $desc = '', $default = null)
    {
        $this->name = $name;
        $this->mode = $mode;

        $this->default = $default;
        $this->setDesc($desc);
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
    public function getDesc(): string
    {
        return $this->desc;
    }

    /**
     * @param string $desc
     */
    public function setDesc(string $desc): void
    {
        $this->desc = $desc;
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
            'description' => $this->desc,
        ];
    }
}
