<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component\Interact;

/**
 * class SingleSelect
 */
class SingleSelect extends AbstractSelect
{
    /**
     * The default selected key
     *
     * @var string
     */
    protected string $default;

    /**
     * The selected key
     *
     * @var string
     */
    protected string $selected;

    /**
     * @var string
     */
    protected string $selectedVal;

    /**
     * @return string
     */
    public function getDefault(): string
    {
        return $this->default;
    }

    /**
     * @param string $default
     */
    public function setDefault(string $default): void
    {
        $this->default = $default;
    }

    /**
     * @return string
     */
    public function getSelected(): string
    {
        return $this->selected;
    }

    /**
     * @return string
     */
    public function getSelectedVal(): string
    {
        return $this->selectedVal;
    }
}
