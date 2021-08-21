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
     * The raw input flags
     *
     * @var array
     */
    protected $rawFlags = [];

    /**
     * The remaining args.
     * After on option parsed from {@see $rawFlags}
     *
     * @var array
     */
    protected $rawArgs = [];

    /**
     * @return array
     */
    public function getRawArgs(): array
    {
        return $this->rawArgs;
    }

    /**
     * @return array
     */
    public function getRawFlags(): array
    {
        return $this->rawFlags;
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