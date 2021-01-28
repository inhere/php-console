<?php declare(strict_types=1);

namespace Inhere\Console;

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
        'h'              => 1,
        'help'           => 1,
        'V'              => 1,
        'version'        => 1,
        'no-interactive' => 1,
    ];
}
