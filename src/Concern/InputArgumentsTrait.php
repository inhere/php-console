<?php declare(strict_types=1);

namespace Inhere\Console\Concern;

use Inhere\Console\Exception\PromptException;
use function array_merge;
use function is_array;
use function is_int;

/**
 * Trait InputArgumentsTrait
 *
 * @package Inhere\Console\Concern
 */
trait InputArgumentsTrait
{
    /**
     * Input args data
     *
     * @var array
     */
    protected $args = [];

    /**
     * Bind an name for argument index
     *
     * [
     *  'name1' => 0,
     *  'name2' => 1,
     * ]
     *
     * @var array
     */
    protected $binds = [];

    /**
     * @param string $name
     * @param int    $index
     * @return self|mixed
     */
    public function bindArgument(string $name, int $index)
    {
        $this->binds[$name] = $index;
        return $this;
    }

    /**
     * @param array $map [ argName => index, ]
     * @param bool  $replace
     */
    public function bindArguments(array $map, bool $replace = false): void
    {
        if ($replace) {
            $this->binds = [];
        }

        foreach ($map as $name => $index) {
            $this->bindArgument($name, (int)$index);
        }
    }

    /***********************************************************************************
     * arguments (eg: arg0 name=john city=chengdu)
     ***********************************************************************************/

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->getArgs();
    }

    /**
     * @param array $args
     * @param bool  $replace
     */
    public function setArgs(array $args, bool $replace = false): void
    {
        $this->args = $replace ? $args : array_merge($this->args, $args);
    }

    /**
     * @param string|int $name
     *
     * @return bool
     */
    public function hasArg($name): bool
    {
        // get real index key
        $key = $this->binds[$name] ?? $name;

        return isset($this->args[$key]);
    }

    /**
     * get Argument
     *
     * @param null|int|string $name
     * @param mixed           $default
     *
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        return $this->get($name, $default);
    }

    /**
     * get Argument
     *
     * @param null|int|string $name
     * @param mixed           $default
     *
     * @return mixed
     */
    public function getArg($name, $default = null)
    {
        return $this->get($name, $default);
    }

    /**
     * get Argument
     *
     * @param null|int|string $name
     * @param mixed           $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        // get real index key
        $key = $this->binds[$name] ?? $name;

        return $this->args[$key] ?? $default;
    }

    /**
     * Get a required argument
     *
     * @param int|string $name argument index or name
     * @param string     $errMsg
     *
     * @return mixed
     */
    public function getRequiredArg($name, string $errMsg = '')
    {
        // get real index key
        $key = $this->binds[$name] ?? $name;
        if (isset($this->args[$key])) {
            return $this->args[$key];
        }

        if (!$errMsg) {
            $errName = is_int($key) ? "'{$name}'(position#{$key})" : "'{$name}'";
            $errMsg  = "The argument {$errName} is required";
        }

        throw new PromptException($errMsg);
    }

    /**
     * Get first argument
     *
     * @param string $default
     *
     * @return string
     */
    public function getFirstArg(string $default = ''): string
    {
        return $this->get(0, $default);
    }

    /**
     * Get second argument
     *
     * @param string $default
     *
     * @return string
     */
    public function getSecondArg(string $default = ''): string
    {
        return $this->get(1, $default);
    }

    /**
     * Get an string argument value
     *
     * @param string|int $key
     * @param string     $default
     *
     * @return string
     */
    public function getStringArg($key, string $default = ''): string
    {
        return (string)$this->get($key, $default);
    }

    /**
     * Get an int argument value
     *
     * @param string|int $key
     * @param int        $default
     *
     * @return int
     */
    public function getInt($key, int $default = 0): int
    {
        return $this->getIntArg($key, $default);
    }

    /**
     * Get an int argument value
     *
     * @param string|int $key
     * @param int        $default
     *
     * @return int
     */
    public function getIntArg($key, int $default = 0): int
    {
        $value = $this->get($key);

        return $value === null ? $default : (int)$value;
    }

    /**
     * Get an array argument value
     *
     * @param string|int $key
     * @param array      $default
     *
     * @return array
     */
    public function getArrayArg($key, array $default = []): array
    {
        $value = $this->get($key);
        if (is_array($value)) {
            return $value;
        }

        return $value ? [$value] : $default;
    }

    /**
     * Get same args value
     * eg: des = description
     *
     * ```php
     * $input->sameArg('des, description');
     * $input->sameArg(['des', 'description']);
     * ```
     *
     * @param string|array $names
     * @param mixed $default
     *
     * @return mixed
     */
    public function getSameArg($names, $default = null)
    {
        if (is_string($names)) {
            $names = array_map('trim', explode(',', $names));
        } elseif (!is_array($names)) {
            $names = (array)$names;
        }

        foreach ($names as $name) {
            if ($this->hasArg($name)) {
                return $this->get($name);
            }
        }

        return $default;
    }

    /**
     * @param string|array $names
     * @param mixed $default
     *
     * @return mixed
     */
    public function sameArg($names, $default = null)
    {
        return $this->getSameArg($names, $default);
    }

    /**
     * clear args
     */
    public function clearArgs(): void
    {
        $this->args = [];
    }
}
