<?php declare(strict_types=1);

namespace Inhere\Console\Flag;

/**
 * Class Flags
 *
 * @package Inhere\Console\Flag
 */
class Flags
{
    public function new(): self
    {
        return new self();
    }

    /**
     * @param array|null $args
     *
     * @return array
     */
    public function parse(array $args = null): array
    {
        return [];
    }
}
