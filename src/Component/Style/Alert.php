<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/5/4
 * Time: 上午11:17
 */

namespace Inhere\Console\Component\Style;

/**
 * Class Alert
 * @package Inhere\Console\Component\Style
 */
class Alert
{
    public const THEMES = [
        'default' => '<{@style}>{@message}</{@style}>',
        'theme1'  => '<{@style}>[{@type}] {@message}</{@style}>',
        'lite'    => '[<{@style}>{@type}</{@style}>] {@message}',
    ];

    public static function create(string $message, string $style = 'info', array $opts = [])
    {

    }

    public static function block(string $message, string $style = 'info', array $opts = [])
    {
        $opts = \array_merge([
            'paddingX' => 1, // line
            'paddingY' => 1, // space

            'icon'     => '',
            'theme'    => 'default',
            'template' => ''
        ], $opts);

    }

    public static function lite(string $message, string $style = 'info')
    {

    }
}
