<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Annotate\Attr;

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
