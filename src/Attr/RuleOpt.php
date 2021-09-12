<?php declare(strict_types=1);

namespace Inhere\Console\Attr;

use Attribute;
use Toolkit\PFlag\AbstractFlags;

/**
 * class RuleOpt
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RuleOpt
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     * @see AbstractFlags::$optRules
     */
    public $rule;
}
