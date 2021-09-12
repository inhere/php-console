<?php declare(strict_types=1);

namespace Inhere\Console\Attr;

use Attribute;
use Toolkit\PFlag\Flag\Option;

/**
 * class CmdOption
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class CmdOption extends Option
{
    public function __construct(
        string $name,
        string $desc = '',
        string $type = 'string',
        bool $required = false,
        $default = null,
        string $shortcut = '',
        string $envVar = ''
    ) {
        parent::__construct($name, $desc, $type, $required, $default);

        $this->setEnvVar($envVar);
        $this->setShortcut($shortcut);
    }
}
