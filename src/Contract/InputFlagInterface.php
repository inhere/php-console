<?php declare(strict_types=1);

namespace Inhere\Console\Contract;

/**
 * Interface InputFlagInterface
 *
 * @package Inhere\Console\Contract
 */
interface InputFlagInterface
{
    // fixed args and opts for a command/controller-command
    public const ARG_REQUIRED = 1;

    public const ARG_OPTIONAL = 2;

    public const ARG_IS_ARRAY = 4;

    public const OPT_BOOLEAN  = 1; // eq symfony InputOption::VALUE_NONE

    public const OPT_REQUIRED = 2;

    public const OPT_OPTIONAL = 4;

    public const OPT_IS_ARRAY = 8;

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
