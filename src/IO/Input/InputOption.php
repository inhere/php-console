<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:28
 */

namespace Inhere\Console\IO\Input;

/**
 * Class InputOption
 * - definition a input option
 *
 * @package Inhere\Console\IO\Input
 */
class InputOption extends InputItem
{
    /**
     * alias name
     *
     * @var string
     */
    private $alias;

    /**
     * @var string|array
     */
    private $shortcut;

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
     * @return array
     */
    public function getShortcut()
    {
        return $this->shortcut;
    }

    /**
     * @param array|string $shortcut
     */
    public function setShortcut($shortcut): void
    {
        $this->shortcut = (array)$shortcut;
    }
}
