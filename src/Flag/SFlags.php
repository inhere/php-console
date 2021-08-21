<?php declare(strict_types=1);

namespace Inhere\Console\Flag;

use Inhere\Console\Flag\Traits\FlagParsingTrait;
use Toolkit\Stdlib\Obj\AbstractObj;
use function array_merge;

/**
 * Class SFlags
 * @package Inhere\Console\Flag
 */
class SFlags extends AbstractObj
{
    use FlagParsingTrait;

    /**
     * @var array
     */
    private $settings = [
        // List of parameters without values(bool option keys)
        'boolOpts'       => [], // ['debug', 'h']
        // Whether merge short-opts and long-opts
        'mergeOpts'      => false,
        // Only want parsed options.
        // if not empty, will ignore no matched
        'wantParsedOpts' => [],
        // List of option allow array values.
        'arrayOpts'      => [], // ['names', 'status']
        // Special short style
        // gnu: `-abc` will expand: `-a -b -c`
        // posix: `-abc`  will expand: `-a=bc`
        'shortStyle'     => 'posix',
    ];

    /**
     * Special short style
     *  gnu: `-abc` will expand: `-a -b -c`
     *  posix: `-abc`  will expand: `-a=bc`
     *
     * @var string
     */
    private $shortStyle = 'posix';

    /**
     * Whether stop parse option on found unknown option
     *
     * @var bool
     */
    private $stopOnUnknown = true;

    /**
     * Whether parse the remaining args {@see $rawArgs}.
     *
     * eg: 'arg=value' -> [arg => value]
     *
     * @var bool
     */
    private $parseRawArgs = true;

    /**
     * Parsed options
     *
     * @var array
     */
    private $opts = [];

    /**
     * Parsed arguments
     *
     * @var array
     */
    private $args = [];

    /**
     * Parse options by pre-defined
     *
     * ```php
     * // element format:
     * // - k-v: k is option, v is value type
     * // - v:  v is option, type is string.
     * $defines = [
     *  's,long', // use default type: string
     *  // option => value type,
     *  's,long' => string,
     *  's'      => bool,
     *  'long'   => int,
     *  'long'   => array, // TODO int[], string[]
     * ];
     * ```
     *
     * @param array $rawArgs
     * @param array $defines
     *
     * @return array
     */
    public function parseDefined(array $rawArgs, array $defines, array $settings = []): array
    {
        $this->setSettings($settings);
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings): void
    {
        $this->settings = array_merge($this->settings, $settings);
    }
}
