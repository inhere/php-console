<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component\Formatter;

use Inhere\Console\Component\MessageFormatter;

/**
 * Class Alert
 *
 * @package Inhere\Console\Component\Formatter
 */
class Alert extends MessageFormatter
{
    public const THEMES = [
        'default' => '<{@style}>{@message}</{@style}>',
        'theme1'  => '<{@style}>[{@type}] {@message}</{@style}>',
        'lite'    => '[<{@style}>{@type}</{@style}>] {@message}',
    ];

    public static function simple(string $message, string $style = 'info', array $opts = []): void
    {
    }

    public static function block(string $message, string $style = 'info', array $opts = []): void
    {
        $opts = array_merge([
            'paddingX' => 1, // line
            'paddingY' => 1, // space

            'icon'     => '',
            'theme'    => 'default',
            'template' => ''
        ], $opts);
    }

    public static function lite(string $message, string $style = 'info'): void
    {
    }
}
