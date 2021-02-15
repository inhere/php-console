<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:28
 */

namespace Inhere\Console\Flag;

use Inhere\Console\Contract\InputFlagInterface;
use Inhere\Console\IO\Input;

/**
 * Class InputFlag
 * - definition a input flag item(option|argument)
 *
 * @package Inhere\Console\IO\Input
 */
abstract class Flag implements InputFlagInterface
{
    public const TYPE_INT = 'int';

    public const TYPE_BOOL = 'bool';

    public const TYPE_FLOAT = 'float';

    public const TYPE_STRING = 'string';

    public const TYPE_ARRAY = 'array';

    // extend types

    public const TYPE_INTS = 'int[]';

    public const TYPE_STRINGS = 'string[]';

    public const TYPE_MIXED = 'mixed';

    public const TYPE_CUSTOM = 'custom';

    public const TYPE_UNKNOWN = '';

    /**
     * @var string
     */
    private $name;

    /**
     * The flag description
     *
     * @var string
     */
    private $desc;

    /**
     * @var int
     */
    private $mode;

    /**
     * The flag data type. (eg: 'int', 'bool', 'string', 'array', 'mixed')
     *
     * @var string
     */
    private $type = self::TYPE_UNKNOWN;

    /**
     * The default value
     *
     * @var mixed
     */
    private $default;

    /**
     * The flag value
     *
     * @var mixed
     */
    private $value;

    /**
     * The flag value validator
     * - if validate fail, please throw FlagException
     *
     * @var callable
     */
    private $validator;

    /**
     * @param string $name
     * @param int    $mode see Input::ARG_* or Input::OPT_*
     * @param string $description
     * @param null   $default
     *
     * @return static
     */
    public static function new(string $name, string $description = '', int $mode = 0, $default = null)
    {
        return new static($name, $mode, $description, $default);
    }

    /**
     * Class constructor.
     *
     * @param string $name      The flag name
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

    public function init(): void
    {
        if ($this->isArray()) {
            $this->type = self::TYPE_ARRAY;
        }
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

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        // filter value by type
        switch ($this->type) {
            case self::TYPE_INT:
                $value = (int)$value;
                break;
            case self::TYPE_BOOL:
                $value = (bool)$value;
                break;
            case self::TYPE_FLOAT:
                $value = (float)$value;
                break;
            case self::TYPE_STRING:
                $value = (string)$value;
                break;
            // case self::TYPE_ARRAY:
            //     $value = (string)$value;
            //     break;
            default:
                // nothing
                break;
        }

        // has validator
        if ($cb = $this->validator) {
            $value = $cb($value);
            // if (false === $ok) {
            //     throw new FlagException('');
            // }
        }

        if ($this->isArray()) {
            $this->value[] = $value;
        } else {
            $this->value = $value;
        }
    }

    /**
     * @param callable $validator
     */
    public function setValidator(callable $validator): void
    {
        $this->validator = $validator;
    }
}
