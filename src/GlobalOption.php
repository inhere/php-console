<?php declare(strict_types=1);

namespace Inhere\Console;

use function array_merge;

/**
 * Class GlobalOption
 *
 * @package Inhere\Console
 */
class GlobalOption
{
    public const HELP = 'help';

    public const DEBUG = 'debug';

    public const ISHELL = 'ishell';

    public const VERSION = 'version';

    public const PROFILE = 'profile';

    public const NO_COLOR = 'no-color';

    public const NO_INTERACTIVE = 'no-interactive';

    public const HELP_OPTS = ['h', 'help'];

    public const VERSION_OPTS = ['V', 'version'];

    public const KEY_MAP = [
        'debug'          => 1,
        'ishell'         => 1,
        'profile'        => 1,
        'no-color'       => 1,
        // 'h'              => 1,
        'help'           => 1,
        // 'V'              => 1,
        'version'        => 1,
        'no-interactive' => 1,
    ];

    /**
     * @var array
     * @psalm-var array<string, string>
     */
    private static $options = [
        '--debug'          => 'int;Setting the runtime log debug level(quiet 0 - 5 crazy);no;1',
        // '--debug'          => 'Setting the runtime log debug level(quiet 0 - 5 crazy)',
        '--ishell'         => 'bool;Run application an interactive shell environment',
        // '--ishell'         => 'Run application an interactive shell environment',
        '--profile'        => 'bool;Display timing and memory usage information',
        // '--profile'        => 'Display timing and memory usage information',
        '--no-color'       => 'bool;Disable color/ANSI for message output',
        // '--no-color'       => 'Disable color/ANSI for message output',
        '--help'           => 'bool;Display this help message;;;h',
        // '-h, --help'       => 'Display this help message',
        '--version'        => 'bool;Show application version information;;;V',
        // '-V, --version'    => 'Show application version information',
        '--no-interactive' => 'bool;Run commands in a non-interactive environment',
        // '--no-interactive' => 'Run commands in a non-interactive environment',
    ];

    /**
     * @var array built-in options for the alone command
     */
    protected static $aloneOptions = [
        // '--help'          => 'bool;Display this help message;;;h',
        // '--show-disabled' => 'string;Whether display disabled commands',
    ];

    /**
     * @var array built-in options for the group command
     */
    protected static $groupOptions = [
        // '--help'          => 'bool;Display this help message;;;h',
        '--show-disabled' => 'string;Whether display disabled commands',
    ];

    /**
     * @var array common options for the group/command
     */
    protected static $commonOptions = [
        '--help' => 'bool;Display this help message;;;h',
    ];

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function isExists(string $name): bool
    {
        return isset(self::KEY_MAP[$name]);
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        if ($options) {
            self::$options = $options;
        }
    }

    /**
     * @param array $options
     */
    public function addOptions(array $options): void
    {
        if ($options) {
            self::$options = array_merge(self::$options, $options);
        }
    }

    /**
     * @return array
     */
    public static function getOptions(): array
    {
        return self::$options;
    }

    /**
     * @return array
     */
    public static function getCommonOptions(): array
    {
        return self::$commonOptions;
    }

    /**
     * @param bool $withCommon
     *
     * @return array
     */
    public static function getAloneOptions(bool $withCommon = true): array
    {
        if ($withCommon) {
            return array_merge(self::$commonOptions, self::$aloneOptions);
        }

        return self::$aloneOptions;
    }

    /**
     * @param bool $withCommon
     *
     * @return array
     */
    public static function getGroupOptions(bool $withCommon = true): array
    {
        if ($withCommon) {
            return array_merge(self::$commonOptions, self::$groupOptions);
        }

        return self::$groupOptions;
    }
}
