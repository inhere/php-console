<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component\Interact;

use Inhere\Console\Concern\InteractiveHandle;

/**
 * class AbstractSelect
 */
abstract class AbstractSelect extends InteractiveHandle
{
    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var bool
     */
    protected bool $allowExit = true;

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
