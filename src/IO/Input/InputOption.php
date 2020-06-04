<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:28
 */

namespace Inhere\Console\IO\Input;

use Inhere\Console\IO\Input;
use function implode;

/**
 * Class InputOption
 * - definition a input option
 *
 * @package Inhere\Console\IO\Input
 */
class InputOption extends InputFlag
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
    private $shortcuts = [];

    /**
     * eg: 'a|b'
     *
     * @var string
     */
    private $shortcut = '';

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->hasMode(Input::OPT_IS_ARRAY);
    }

    /**
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->hasMode(Input::OPT_OPTIONAL);
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->hasMode(Input::OPT_REQUIRED);
    }

    /**
     * @return bool
     */
    public function isBoolean(): bool
    {
        return $this->hasMode(Input::OPT_BOOLEAN);
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
     * @param string $shortcut
     */
    public function setShortcutsByString(string $shortcut): void
    {
        $shortcuts = preg_split('{(\|)-?}', ltrim($shortcut, '-'));
        $shortcuts = array_filter($shortcuts);

        $this->setShortcuts($shortcuts);
    }

    /**
     * @return array
     */
    public function getShortcuts(): array
    {
        return $this->shortcuts;
    }

    /**
     * @param array $shortcuts
     */
    public function setShortcuts(array $shortcuts): void
    {
        $this->shortcuts = $shortcuts;
        $this->shortcut  = implode('|', $shortcuts);
    }

}
