<?php declare(strict_types=1);

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
    protected $defaults;

    /**
     * The selected key
     *
     * @var string[]
     */
    protected $selected;

    /**
     * @var string[]
     */
    protected $selectedVals;

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
