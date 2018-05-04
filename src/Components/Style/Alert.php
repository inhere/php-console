<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/5/4
 * Time: 上午11:17
 */

namespace Inhere\Console\Components\Style;

/**
 * Class Alert
 * @package Inhere\Console\Components\Style
 */
class Alert
{
    const THEMES = [
        '<{%style%}>{%message}</{%style%}>',
        '[<{%style%}>{%type%}</{%style%}>] {%message%}',
        '[<{%style%}>{%type%}] {%message%}</{%style%}>',
    ];

    public static function block(string $message, string $style = 'info', array $opts = [])
    {
        $opts = \array_merge([
            'paddingX' => 1, // line
            'paddingY' => 1, // space
        ], $opts);

    }

    public static function lite(string $message, string $style = 'info')
    {

    }
}
