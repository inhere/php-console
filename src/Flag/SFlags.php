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
        // posix: -abc will expand: -a -b -c
        // unix: -abc  will expand: -a=bc
        'shortStyle' => 'posix',
    ];

    /**
     * @param array $rawArgs
     * @param array $settings
     *
     * @return array
     */
    public function parse(array $rawArgs, array $settings = []): array
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
