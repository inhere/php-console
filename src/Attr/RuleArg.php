<?php declare(strict_types=1);

namespace Inhere\Console\Attr;

use Attribute;
use Toolkit\PFlag\AbstractFlags;

/**
 * class RuleArg
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RuleArg
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     * @see AbstractFlags::$argRules
     */
    public $rule;
}
