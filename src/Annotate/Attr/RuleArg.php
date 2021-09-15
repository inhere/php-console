<?php declare(strict_types=1);

namespace Inhere\Console\Annotate\Attr;

use Attribute;
use Toolkit\PFlag\FlagsParser;

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
     * @see FlagsParser::$argRules
     */
    public $rule;
}
