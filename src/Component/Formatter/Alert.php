<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/5/4
 * Time: 上午11:54
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
