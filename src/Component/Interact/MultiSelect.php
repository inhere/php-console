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
 * class MultiSelect
 */
class MultiSelect extends AbstractSelect
{
    /**
     * The default selected keys
     *
     * @var string
     */
    protected string $defaults;

    /**
     * The selected key
     *
     * @var string[]
     */
    protected array $selected;

    /**
     * @var string[]
     */
    protected array $selectedVals;

    /**
     * @return string[]
     */
    public function getSelected(): array
    {
        return $this->selected;
    }

    /**
     * @return string[]
     */
    public function getSelectedVals(): array
    {
        return $this->selectedVals;
    }

    /**
     * @return string
     */
    public function getDefaults(): string
    {
        return $this->defaults;
    }

    /**
     * @param string $defaults
     */
    public function setDefaults(string $defaults): void
    {
        $this->defaults = $defaults;
    }
}
