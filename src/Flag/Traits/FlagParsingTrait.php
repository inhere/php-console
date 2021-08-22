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
     * Special short style
     *  gnu: `-abc` will expand: `-a -b -c`
     *  posix: `-abc`  will expand: `-a=bc`
     *
     * @var string
     */
    private $shortStyle = 'posix';

    /**
     * Whether stop parse option on first argument
     *
     * @var bool
     */
    // private $stopOnNoOption = true;
    // private $stopOnNoOpt = true;
    private $stopOnFistArg = true;

    /**
     * Whether stop parse option on found undefined option
     *
     * @var bool
     */
    private $stopOnUndefined = true;

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
    public function isStopOnFistArg(): bool
    {
        return $this->stopOnFistArg;
    }

    /**
     * @param bool $stopOnFistArg
     */
    public function setStopOnFistArg(bool $stopOnFistArg): void
    {
        $this->stopOnFistArg = $stopOnFistArg;
    }

    /**
     * @return bool
     */
    public function isStopOnUndefined(): bool
    {
        return $this->stopOnUndefined;
    }

    /**
     * @param bool $stopOnUndefined
     */
    public function setStopOnUndefined(bool $stopOnUndefined): void
    {
        $this->stopOnUndefined = $stopOnUndefined;
    }
}