<?php declare(strict_types=1);


namespace Inhere\Console\Flag;

use function stripos;

/**
 * Class FlagConst
 * @package Inhere\Console\Flag
 */
class FlagType
{
    public const INT = 'int';

    public const BOOL = 'bool';

    public const FLOAT = 'float';

    public const STRING = 'string';

    // ------ complex types ------

    public const ARRAY  = 'array';

    public const OBJECT   = 'object';

    public const CALLABLE = 'callable';

    // ------ extend types ------

    public const INTS = 'int[]';

    public const STRINGS = 'string[]';

    public const MIXED = 'mixed';

    public const CUSTOM = 'custom';

    public const UNKNOWN = 'unknown';

    public const ARRAY_TYPES = [
        self::ARRAY => 2,
        self::INTS => 3,
        self::STRINGS => 3,
    ];

    public const TYPES_MAP = [
        self::INT => 1,
        self::BOOL => 1,
        self::FLOAT => 1,
        self::STRING => 1,

        // ------ complex types ------
        self::ARRAY => 2,
        self::OBJECT => 2,
        self::CALLABLE => 2,

        // ------ extend types ------
        self::INTS => 3,
        self::STRINGS => 3,
        self::MIXED => 3,
        self::CUSTOM => 3,
        self::UNKNOWN => 3,
    ];

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isValid(string $type): bool
    {
        return isset(self::TYPES_MAP[$type]);
    }

    /**
     * @param string $type
     * @param mixed  $value
     *
     * @return bool|float|int|mixed|string
     */
    public static function fmtBasicTypeValue(string $type, $value)
    {
        // filter value by type
        switch ($type) {
            case self::INT:
            case self::INTS:
                $value = (int)$value;
                break;
            case self::BOOL:
                $value = (bool)$value;
                break;
            case self::FLOAT:
                $value = (float)$value;
                break;
            case self::STRING:
            case self::STRINGS:
                $value = (string)$value;
                break;
            // case FlagType::ARRAY:
            //     $value = (string)$value;
            //     break;
            default:
                // nothing
                break;
        }

        return $value;
    }

    // These words will be as a Boolean value
    private const TRUE_WORDS = '|on|yes|true|';

    private const FALSE_WORDS = '|off|no|false|';

    public static function str2bool(string $val): bool
    {
        // check it is a bool value.
        if (false !== stripos(self::TRUE_WORDS, "|$val|")) {
            return true;
        }

        if (false !== stripos(self::FALSE_WORDS, "|$val|")) {
            return false;
        }

        // TODO throws error
        return false;
    }
}