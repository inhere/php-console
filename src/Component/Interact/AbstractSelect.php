<?php declare(strict_types=1);

namespace Inhere\Console\Component\Interact;

use Inhere\Console\Component\InteractiveHandle;

/**
 * class AbstractSelect
 */
abstract class AbstractSelect extends InteractiveHandle
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var bool
     */
    protected $allowExit = true;

    /**
     * @return bool
     */
    public function isAllowExit(): bool
    {
        return $this->allowExit;
    }

    /**
     * @param bool $allowExit
     */
    public function setAllowExit(bool $allowExit): void
    {
        $this->allowExit = $allowExit;
    }

}
