<?php declare(strict_types=1);

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
    protected static $allowedTags = [
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
