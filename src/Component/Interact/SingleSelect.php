<?php declare(strict_types=1);

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
    protected $default;

    /**
     * The selected key
     *
     * @var string
     */
    protected $selected;

    /**
     * @var string
     */
    protected $selectedVal;

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
