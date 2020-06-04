<?php declare(strict_types=1);

namespace Inhere\Console\Contract;

/**
 * Interface InputFlagInterface
 *
 * @package Inhere\Console\Contract
 */
interface InputFlagInterface
{
    /**
     * @param int $mode
     *
     * @return bool
     */
    public function hasMode(int $mode): bool;

    /**
     * @return bool
     */
    public function isArray(): bool;

    /**
     * @return bool
     */
    public function isRequired(): bool;

    /**
     * @return bool
     */
    public function isOptional(): bool;
}
