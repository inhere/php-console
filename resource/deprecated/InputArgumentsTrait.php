<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Deprecated\Concern;

use Inhere\Console\Exception\PromptException;
use function array_merge;
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
    protected array $args = [];

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
    protected array $binds = [];

    /**
     * @param string $name
     * @param int    $index
     * @return self
     */
    public function bindArgument(string $name, int $index): static
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
     * @param int|string $name
     *
     * @return bool
     */
    public function hasArg(int|string $name): bool
    {
        // get real index key
        $key = $this->binds[$name] ?? $name;

        return isset($this->args[$key]);
    }

    /**
     * get Argument
     *
     * @param int|string|null $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getArgument(int|string|null $name, mixed $default = null): mixed
    {
        return $this->get($name, $default);
    }

    /**
     * get argument
     *
     * @param int|string|null $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getArg(int|string|null $name, mixed $default = null): mixed
    {
        return $this->get($name, $default);
    }

    /**
     * get Argument
     *
     * @param int|string|null $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(int|string|null $name, mixed $default = null): mixed
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
    public function getRequiredArg(int|string $name, string $errMsg = ''): mixed
    {
        // get real index key
        $key = $this->binds[$name] ?? $name;
        if (isset($this->args[$key])) {
            return $this->args[$key];
        }

        if (!$errMsg) {
            $errName = is_int($key) ? "'$name'(position#$key)" : "'$name'";
            $errMsg  = "The argument $errName is required";
        }

        throw new PromptException($errMsg);
    }

    /**
     * clear args
     */
    public function clearArgs(): void
    {
        $this->args = [];
    }
}
