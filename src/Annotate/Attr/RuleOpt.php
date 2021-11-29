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
use Toolkit\PFlag\FlagsParser;

/**
 * class RuleOpt
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RuleOpt
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     * @see FlagsParser::$optRules
     */
    public string $rule;
}
