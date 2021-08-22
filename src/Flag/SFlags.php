<?php declare(strict_types=1);

namespace Inhere\Console\Flag;

use Inhere\Console\Concern\NameAliasTrait;
use Inhere\Console\Exception\FlagException;
use Inhere\Console\Flag\Traits\FlagParsingTrait;
use Toolkit\Stdlib\Obj\AbstractObj;
use Toolkit\Stdlib\Str;
use function current;
use function escapeshellarg;
use function explode;
use function is_bool;
use function is_int;
use function is_numeric;
use function is_string;
use function next;
use function preg_match;
use function str_split;
use function stripos;
use function strlen;
use function strpos;
use function substr;
use function trim;

/**
 * Class SFlags
 * @package Inhere\Console\Flag
 */
class SFlags extends AbstractObj
{
    use FlagParsingTrait;
    use NameAliasTrait;

    // These words will be as a Boolean value
    private const TRUE_WORDS = '|on|yes|true|';

    private const FALSE_WORDS = '|off|no|false|';

    public const SHORT_STYLE_GUN   = 'gnu';
    public const SHORT_STYLE_POSIX = 'posix';

    /**
     * Whether parse the remaining args {@see $rawArgs}.
     *
     * eg: 'arg=value' -> [arg => value]
     *
     * @var bool
     */
    private $parseRawArgs = true;

    /**
     * @var array
     */
    private $defined = [];

    /**
     * Parsed options
     *
     * @var array
     */
    private $opts = [];

    /**
     * Parsed arguments
     *
     * @var array
     */
    private $args = [];

    /**
     * load option defines
     *
     * ```php
     * // element format:
     * // - k-v: k is option, v is value type
     * // - v:  v is option, type is string.
     * $defines = [
     *  'long,s', // use default type: string
     *  // option => value type,
     *  'long,s' => int,
     *  's'      => bool,
     *  'long'   => string,
     *  'long'   => array, // TODO int[], string[]
     * ];
     * ```
     *
     * @param array $defines
     */
    protected function loadDefined(array $defines): void
    {
        foreach ($defines as $key => $type) {
            if (is_int($key)) {
                $key  = $type;
                $type = FlagType::STRING;
            }

            if (is_string($key)) {
                if (strpos($key, ',') === false) {
                    $this->defined[$key] = (string)$type;
                    continue;
                }

                $name = '_';
                $keys = Str::explode($key, ',');

                // first is the option name. other is aliases.
                foreach ($keys as $i => $k) {
                    if ($i === 0) {
                        $name = $k;

                        $this->defined[$k] = (string)$type;
                    } else {
                        $this->setAlias($name, $k, true);
                    }
                }
            }
        }

    }

    /**
     * Parse options by pre-defined
     *
     * Usage:
     *
     * ```php
     * $rawFlags = $_SERVER['argv'];
     * // NOTICE: must shift first element.
     * $scriptFile = \array_shift($rawFlags);
     * $rawArgs = Flags::new()->parseDefined($rawFlags);
     * ```
     *
     * ```php
     * // element format:
     * // - k-v: k is option, v is value type
     * // - v:  v is option, type is string.
     * $defines = [
     *  'long,s', // use default type: string
     *  // option => value type,
     *  'long,s' => int,
     *  's'      => bool,
     *  'long'   => string,
     *  'long'   => array, // TODO int[], string[]
     * ];
     * ```
     *
     * @param array $rawFlags
     * @param array $defines The want parsed options defines
     *
     * @return array
     */
    public function parseDefined(array $rawFlags, array $defines): array
    {
        if ($this->parsed) {
            return $this->rawArgs;
        }

        $this->parsed   = true;
        $this->rawFlags = $rawFlags;
        $this->loadDefined($defines);

        $optParseEnd = false;
        while (false !== ($p = current($rawFlags))) {
            next($rawFlags);

            // option parse end, collect remaining arguments.
            if ($optParseEnd) {
                $this->rawArgs[] = $p;
                continue;
            }

            // is options and not equals '-' '--'
            if ($p !== '' && $p[0] === '-' && '' !== trim($p, '-')) {
                $value  = true;
                $hasVal = false;

                $isShort = true;
                $option  = substr($p, 1);
                // long-opt: (--<opt>)
                if (strpos($option, '-') === 0) {
                    $isShort = false;
                    $option  = substr($option, 1);

                    // long-opt: value specified inline (--<opt>=<value>)
                    if (strpos($option, '=') !== false) {
                        [$option, $value] = explode('=', $option, 2);
                        $hasVal = $value !== '';
                    }

                    // short-opt: value specified inline (-<opt>=<value>)
                } elseif (isset($option[1]) && $option[1] === '=') {
                    [$option, $value] = explode('=', $option, 2);
                    $hasVal = $value !== '';
                }

                // Is special short opts. eg: -abc
                if ($isShort && strlen($option) > 1) {
                    $this->parseSpecialShorts($option);
                    continue;
                }

                $option = $this->resolveAlias($option);
                if (!isset($this->defined[$option])) {
                    throw new FlagException("flag option provided but not defined: $option", 404);
                }

                $type = $this->defined[$option];

                // eg: -o=false
                $isBool = $type === FlagType::BOOL;
                if ($hasVal && $isBool) {
                    $value = FlagType::str2bool($value);
                    $this->setRealOptValue($option, $type, $value);
                    continue;
                }

                // check if next element is a descriptor or a value
                $next = current($rawFlags);
                if ($hasVal === false && $isBool === false) {
                    if (false === self::nextIsValue($next)) {
                        throw new FlagException("must provide value for the option: $option", 404);
                    }

                    $value = $next;
                    next($rawFlags);
                }

                $this->setRealOptValue($option, $type, $value);
                continue;
            }

            // collect arguments.
            $this->rawArgs[] = $p;

            // stop parse options:
            // - on found fist argument.
            // - found '--' will stop parse options
            if ($this->stopOnFistArg || $p === '--') {
                $optParseEnd = true;
            }
        }

        return $this->rawArgs;
    }

    /**
     * @param string $shorts
     */
    private function parseSpecialShorts(string $shorts): void
    {
        // posix: '-abc' will expand to '-a=bc'
        if ($this->shortStyle === self::SHORT_STYLE_POSIX) {
            $this->setOptValue($shorts[0], substr($shorts, 1));
            return;
        }

        // gnu: '-abc' will expand to '-a -b -c'
        foreach (str_split($shorts) as $short) {
            $option = $this->resolveAlias($short);
            if (!isset($this->defined[$option])) {
                throw new FlagException("flag option provided but not defined: $option", 404);
            }

            $type = $this->defined[$option];
            $this->setRealOptValue($option, $type, true);
        }
    }

    /**
     * @param string $option
     * @param string $type
     * @param mixed  $value
     */
    protected function setRealOptValue(string $option, string $type, $value): void
    {
        if (isset(FlagType::ARRAY_TYPES[$type])) {
            $this->opts[$option][] = $value;
        } else {
            $this->opts[$option] = $value;
        }
    }

    /**
     * @param string $option
     * @param mixed  $value
     */
    public function setOptValue(string $option, $value): void
    {
        $option = $this->resolveAlias($option);
        if (!isset($this->defined[$option])) {
            throw new FlagException("flag option provided but not defined: $option", 404);
        }

        $type  = $this->defined[$option];
        $value = FlagType::fmtBasicTypeValue($type, $value);

        $this->setRealOptValue($option, $type, $value);
    }

    /**
     * The mapping name argument index
     *
     * @var array
     */
    private $name2index = [];

    /**
     * parse remaining rawArgs as arguments
     *
     * ```php
     * $defines = [
     *  // type see FlagType::*
     *  'type', // not set name, use index for get value.
     *  'type, name', // allow set argument name.
     * ];
     * ```
     *
     * @param array $defines
     */
    public function parseRawArgs(array $defines): void
    {
        // TODO
        foreach ($this->rawArgs as $index => $arg) {
            // value specified inline (<arg>=<value>)
            if (strpos($arg, '=') !== false) {
                [$name, $value] = explode('=', $arg, 2);

                if (self::isValidArgName($name)) {
                    $this->args[$name] = self::filterBool($value);
                } else {
                    $this->args[] = $arg;
                }
            } else {
                $this->args[] = $arg;
            }
        }
    }

    /**
     * @param bool $resetDefines
     */
    public function reset(bool $resetDefines = true): void
    {
        $this->parsed  = false;
        $this->rawArgs = $this->rawFlags = [];

        $this->opts = $this->args = [];

        if ($resetDefines) {
            $this->defined = [];
        }
    }

    /**
     * @param string|bool|int|mixed $val
     *
     * @return bool|int|mixed
     */
    public static function filterBool($val)
    {
        if (is_bool($val) || is_numeric($val)) {
            return $val;
        }

        // check it is a bool value.
        if (false !== stripos(self::TRUE_WORDS, "|$val|")) {
            return true;
        }

        if (false !== stripos(self::FALSE_WORDS, "|$val|")) {
            return false;
        }

        return $val;
    }

    /**
     * check next is option value
     *
     * @param mixed $val
     *
     * @return bool
     */
    public static function nextIsValue($val): bool
    {
        // current() fetch error, will return FALSE
        if ($val === false) {
            return false;
        }

        // if is: '', 0
        if (!$val) {
            return true;
        }

        // is not option name.
        if ($val[0] !== '-') {
            // ensure is option value.
            if (false === strpos($val, '=')) {
                return true;
            }

            // is string value, but contains '='
            [$name,] = explode('=', $val, 2);

            // named argument OR invalid: 'some = string'
            return false === self::isValidArgName($name);
        }

        // is option name.
        return false;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function isValidArgName(string $name): bool
    {
        return preg_match('#^\w+$#', $name) === 1;
    }

    /**
     * Escapes a token through escape shell arg if it contains unsafe chars.
     *
     * @param string $token
     *
     * @return string
     */
    public static function escapeToken(string $token): string
    {
        return preg_match('{^[\w-]+$}', $token) ? $token : escapeshellarg($token);
    }
}
