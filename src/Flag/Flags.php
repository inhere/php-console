<?php declare(strict_types=1);

namespace Inhere\Console\Flag;

use Inhere\Console\Concern\NameAliasTrait;
use function array_shift;
use function count;
use function ltrim;
use function strlen;
use function substr;

/**
 * Class Flags
 *
 * @package Inhere\Console\Flag
 */
class Flags
{
    // use InputArgumentsTrait, InputOptionsTrait;
    use FlagOptionsTrait;
    use CmdArgumentsTrait;
    use NameAliasTrait;

    /**
     * @var self
     */
    private static $std;

    /**
     * @var callable
     */
    private $helpRenderer;

    /**
     * @var bool
     */
    private $parsed = false;

    /**
     * @var bool
     */
    private $autoBindArgs = false;

    /**
     * The raw input args
     *
     * @var array
     */
    private $rawArgs = [];

    /**
     * The remaining args on parsed
     *
     * @var array
     */
    private $args = [];

    /**
     * @return $this
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * @return $this
     */
    public static function std(): self
    {
        if (!self::$std) {
            self::$std = new self();
        }

        return self::$std;
    }

    /**************************************************************************
     * parse command option flags
     **************************************************************************/

    /**
     * @param array|null $args
     *
     * @return array
     */
    public static function parseArgs(array $args = null): array
    {
        return (new self())->parse($args);
    }

    /**
     * @var string
     */
    private $curOptKey = '';

    private $parseStatus = self::STATUS_OK;

    public const STATUS_OK   = 0;
    public const STATUS_ERR  = 1;
    public const STATUS_END  = 2;
    public const STATUS_HELP = 3; // found `-h|--help` flag

    /**
     * @param array|null $args
     *
     * @return array
     */
    public function parse(array $args = null): array
    {
        if ($args === null) {
            $args = $_SERVER['argv'];
        }

        $this->parsed  = true;
        $this->rawArgs = $this->args = $args;

        while (true) {
            [$goon, $status] = $this->parseOne();
            if ($goon) {
                continue;
            }

            if (self::STATUS_OK === $status) {
                break;
            }
        }

        // binding remaining args.
        if ($this->args && $this->autoBindArgs) {
            $this->bindingArguments();
        }

        return [];
    }

    /**
     * parse one flag.
     *
     * will stop on:
     * - found `-h|--help` flag
     * - found first arg(not an option)
     *
     * @return array [bool, status]
     */
    protected function parseOne(): array
    {
        $count = count($this->args);
        if ($count === 0) {
            return [false, self::STATUS_OK];
        }

        $arg = array_shift($this->args);

        // empty, continue.
        if ('' === $arg) {
            return [true, self::STATUS_OK];
        }

        // is not an option flag. exit.
        if ($arg[0] !== '-') {
            return [false, self::STATUS_OK];
        }

        $name = ltrim($arg, '-');

        // invalid arg. eg: '--' // ignore
        if ('' === $name) {
            return [true, self::STATUS_OK];
        }

        $value  = '';
        $hasVal = false;

        $len = strlen($name);
        for ($i = 0; $i < $len; $i++) {
            if ($name[$i] === '=') {
                $hasVal = true;
                $name   = substr($name, 0, $i);

                // fix: `--name=` no value string.
                if ($i + 1 < $len) {
                    $value = substr($name, $i + 1);
                }
            }
        }

        $rName = $this->resolveAlias($name);
        if (!isset($this->defined[$rName])) {
            throw new FlagException("flag option provided but not defined: $arg", 404);
        }

        $opt = $this->defined[$rName];

        // bool option always set TRUE.
        if ($opt->isBoolean()) {
            $boolVal = true;
            if ($hasVal) {
                $boolVal = self::filterBool($value);
            }
            $opt->setValue($boolVal);
        } else {
            if (!$hasVal && count($this->args) > 0) {
                // value is next arg
                $hasVal = true;
                $ntArg = $this->args[0];

                // is not an option value.
                if ($ntArg[0] === '-') {
                    $hasVal = false;
                } else {
                    $value = array_shift($this->args);
                }
            }

            if (!$hasVal) {
                throw new FlagException("flag option '$arg' needs an value", 400);
            }

            // set value
            $opt->setValue($value);
        }

        return [true, self::STATUS_OK];
    }

    // These words will be as a Boolean value
    private const TRUE_WORDS  = '|on|yes|true|';
    private const FALSE_WORDS = '|off|no|false|';

    /**
     * @param string $val
     *
     * @return bool|null
     */
    public static function filterBool(string $val): ?bool
    {
        // check it is a bool value.
        return false !== stripos(self::TRUE_WORDS, "|$val|");
    }

    /**
     * @param string $val
     *
     * @return bool|null
     */
    public static function filterBoolV2(string $val): ?bool
    {
        // check it is a bool value.
        if (false !== stripos(self::TRUE_WORDS, "|$val|")) {
            return true;
        }

        if (false !== stripos(self::FALSE_WORDS, "|$val|")) {
            return false;
        }

        // return null;
        return null;
    }

    /**************************************************************************
     * parse and binding command arguments
     **************************************************************************/

    /**
     * parse and binding command arguments
     *
     * NOTICE: must call it on options parsed.
     */
    public function bindingArguments(): void
    {
        if (!$this->args) {
            return;
        }

        // TODO ...
    }

    /**
     * @return callable
     */
    public function getHelpRenderer(): callable
    {
        return $this->helpRenderer;
    }

    /**
     * @param callable $helpRenderer
     */
    public function setHelpRenderer(callable $helpRenderer): void
    {
        $this->helpRenderer = $helpRenderer;
    }

    /**
     * @return array
     */
    public function getRawArgs(): array
    {
        return $this->rawArgs;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return bool
     */
    public function isAutoBindArgs(): bool
    {
        return $this->autoBindArgs;
    }

    /**
     * @param bool $autoBindArgs
     */
    public function setAutoBindArgs(bool $autoBindArgs): void
    {
        $this->autoBindArgs = $autoBindArgs;
    }

    /**
     * @return bool
     */
    public function isParsed(): bool
    {
        return $this->parsed;
    }
}
