<?php declare(strict_types=1);

namespace Inhere\Console\Flag\Traits;

/**
 * Trait FlagParsingTrait
 * @package Inhere\Console\Flag\Traits
 */
trait FlagParsingTrait
{
    /**
     * @var bool
     */
    private $parsed = false;

    /**
     * Whether stop parse option on first argument
     *
     * @var bool
     */
    // private $stopOnNoOption = true;
    // private $stopOnNoOpt = true;
    private $stopOnArg = true;

    /**
     * The remaining args on option parsed
     *
     * @var array
     */
    private $args = [];

    /**
     * The raw input args
     *
     * @var array
     */
    private $rawArgs = [];

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return array
     */
    public function getRawArgs(): array
    {
        return $this->rawArgs;
    }

    /**
     * @return bool
     */
    public function isParsed(): bool
    {
        return $this->parsed;
    }

    /**
     * @return bool
     */
    public function isStopOnArg(): bool
    {
        return $this->stopOnArg;
    }

    /**
     * @param bool $stopOnArg
     *
     * @return static
     */
    public function setStopOnArg(bool $stopOnArg): self
    {
        $this->stopOnArg = $stopOnArg;
        return $this;
    }
}