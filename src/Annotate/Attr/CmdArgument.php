<?php declare(strict_types=1);

namespace Inhere\Console\Annotate\Attr;

use Attribute;
use Toolkit\PFlag\Flag\Argument;

/**
 * class CmdArgument
 */
#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD)]
class CmdArgument extends Argument
{
    public function __construct(
        string $name,
        string $desc = '',
        string $type = 'string',
        bool $required = false,
        $default = null,
        string $envVar = ''
    ) {
        parent::__construct($name, $desc, $type, $required, $default);

        $this->setEnvVar($envVar);
    }
}
