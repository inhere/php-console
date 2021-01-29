<?php declare(strict_types=1);

namespace Inhere\Console\Flag;

use Inhere\Console\Concern\InputArgumentsTrait;
use Inhere\Console\Concern\InputOptionsTrait;

/**
 * Class Flags
 *
 * @package Inhere\Console\Flag
 */
class Flags
{
    use InputArgumentsTrait, InputOptionsTrait;

    public function new(): self
    {
        return new self();
    }

    /**
     * @param array $args
     *
     * @return array
     */
    public static function parseArgs(array $args): array
    {
        return (new self())->parse($args);
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
