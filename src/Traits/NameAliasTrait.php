<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-28
 * Time: 9:20
 */

namespace Inhere\Console\Traits;

use InvalidArgumentException;

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
     * @param bool         $validate
     */
    public function setAlias(string $name, $alias, bool $validate = false): void
    {
        foreach ((array)$alias as $aliasName) {
            if (!isset($this->aliases[$aliasName])) {
                $this->aliases[$aliasName] = $name;
            } elseif ($validate) {
                $oldName = $this->aliases[$aliasName];
                throw new InvalidArgumentException("Alias '{$aliasName}' has been registered by '{$oldName}', cannot assign to the '{$name}'");
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
