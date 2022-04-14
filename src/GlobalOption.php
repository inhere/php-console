<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console;

use Toolkit\PFlag\FlagType;
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
     * Global options config
     *
     * @var array
     * @psalm-var array<string, string>
     */
    private static array $globalOptions = [
        'debug'          => [
            'type'    => FlagType::INT,
            'desc'    => 'Setting the runtime log debug level, quiet 0 - crazy 5',
            'envVar'  => Console::DEBUG_ENV_KEY,
            'default' => Console::VERB_ERROR,
        ],
        'ishell'         => 'bool;Run application an interactive shell environment',
        'profile'        => 'bool;Display timing and memory usage information',
        'no-color'       => 'bool;Disable color/ANSI for message output',
        'help'           => 'bool;Display application help message;;;h',
        'version'        => 'bool;Show application version information;;;V',
        'no-interactive' => 'bool;Run commands in a non-interactive environment',
        // - hidden options
        'auto-completion'          => [
            'type'   => FlagType::BOOL,
            'hidden' => true,
            'desc'   => 'Open generate auto completion script',
            // 'envVar' => Console::DEBUG_ENV_KEY,
        ],
        'shell-env'          => [
            'type'   => FlagType::STRING,
            'hidden' => true,
            'desc'   => 'The shell env name for generate auto completion script',
            // 'envVar' => Console::DEBUG_ENV_KEY,
        ],
        'gen-file'          => [
            'type'   => FlagType::STRING,
            'hidden' => true,
            'desc'   => 'The output file for generate auto completion script',
            // 'envVar' => Console::DEBUG_ENV_KEY,
            'default' => 'stdout',
        ],
        'tpl-file'          => [
            'type'   => FlagType::STRING,
            'hidden' => true,
            'desc'   => 'custom tpl file for generate completion script',
            // 'default' => 'stdout',
        ],
    ];

    /**
     * @var array built-in options for the alone command
     */
    protected static array $aloneOptions = [
        // '--help'          => 'bool;Display this help message;;;h',
        // '--show-disabled' => 'string;Whether display disabled commands',
    ];

    public const SHOW_DISABLED = 'show-disabled';

    /**
     * @var array built-in options for the group command
     */
    protected static array $groupOptions = [
        // '--help'          => 'bool;Display this help message;;;h',
        self::SHOW_DISABLED => [
            'hidden' => true,
            'desc'   => 'Whether display disabled commands'
        ],
    ];

    /**
     * @var array common options for the group/command
     */
    protected static array $commonOptions = [
        self::HELP => 'bool;Display command help message;;;h',
    ];

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function isExists(string $name): bool
    {
        return isset(self::$globalOptions[$name]);
    }

    /**
     * @param string $name
     * @param array $rule
     */
    public static function setOption(string $name, array $rule): void
    {
        self::$globalOptions[$name] = $rule;
    }

    /**
     * @param array $globalOptions
     */
    public static function setOptions(array $globalOptions): void
    {
        if ($globalOptions) {
            self::$globalOptions = $globalOptions;
        }
    }

    /**
     * @param array $globalOptions
     */
    public static function addOptions(array $globalOptions): void
    {
        if ($globalOptions) {
            self::$globalOptions = array_merge(self::$globalOptions, $globalOptions);
        }
    }

    /**
     * @return array
     */
    public static function getOptions(): array
    {
        return self::$globalOptions;
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
