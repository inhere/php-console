<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Annotate;

/**
 * class AnnotateRules
 */
class AnnotateRules
{
    /**
     * Allow display message tags in the command method docblock
     *
     * @var array
     */
    protected static array $allowedTags = [
        // tag name => allow multi tags
        'desc'     => false,
        'usage'    => false,
        'argument' => true,
        'option'   => true,
        'example'  => false,
        'help'     => false,
    ];

    /**
     * @return array
     */
    public static function getAllowedTags(): array
    {
        return self::$allowedTags;
    }
}
