<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-28
 * Time: 9:20
 */

namespace Inhere\Console\Traits;

/**
 * Class NameAliasTrait
 *
 * @package Inhere\Console\Traits
 */
trait NameAliasTrait
{
    /**
     * @var array
     */
    private $aliases = [];

    /**
     * set name alias(es)
     *
     * @param string       $name
     * @param string|array $alias
     */
    public function setAlias(string $name, $alias): void
    {
        foreach ((array)$alias as $aliasName) {
            if (!isset($this->aliases[$aliasName])) {
                $this->aliases[$aliasName] = $name;
            }
        }
    }

    /**
     * get real name by alias
     *
     * @param string $alias
     *
     * @return mixed
     */
    public function resolveAlias(string $alias): string
    {
        return $this->aliases[$alias] ?? $alias;
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function hasAlias(string $alias): bool
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }
}
