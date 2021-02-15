<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:28
 */

namespace Inhere\Console\Flag;

use function implode;

/**
 * Class Option
 * - definition a input flag option
 *
 * @package Inhere\Console\IO\Input
 */
class Option extends Flag
{
    /**
     * alias name
     *
     * @var string
     */
    private $alias = '';

    /**
     * Shortcuts of the option. eg: ['a', 'b']
     *
     * @var array
     */
    private $shorts = [];

    /**
     * Shortcuts of the option, string format. eg: 'a|b'
     *
     * @var string
     */
    private $shortcut = '';

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->hasMode(Flag::OPT_IS_ARRAY);
    }

    /**
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->hasMode(Flag::OPT_OPTIONAL);
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->hasMode(Flag::OPT_REQUIRED);
    }

    /**
     * @return bool
     */
    public function isBoolean(): bool
    {
        return $this->hasMode(Flag::OPT_BOOLEAN);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getShortcut(): string
    {
        return $this->shortcut;
    }

    /**
     * @param string $shortcut eg: 'a|b'
     */
    public function setShortcut(string $shortcut): void
    {
        $shortcuts = preg_split('{(\|)-?}', ltrim($shortcut, '-'));
        $shortcuts = array_filter($shortcuts);

        $this->setShorts($shortcuts);
    }

    /**
     * @return array
     */
    public function getShorts(): array
    {
        return $this->shorts;
    }

    /**
     * @param array $shorts
     */
    public function setShorts(array $shorts): void
    {
        $this->shorts   = $shorts;
        $this->shortcut = implode('|', $shorts);
    }
}
