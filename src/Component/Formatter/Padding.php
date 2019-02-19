<?php

namespace Inhere\Console\Component\Formatter;

use Inhere\Console\Util\Helper;
use Inhere\Console\Util\Show;
use Toolkit\Cli\ColorTag;

/**
 * Class Padding
 * @package Inhere\Console\Component\Formatter
 */
class Padding extends Formatter
{
    /**
     * ```php
     * $data = [
     *  'Eggs' => '$1.99',
     *  'Oatmeal' => '$4.99',
     *  'Bacon' => '$2.99',
     * ];
     * ```
     *
     * @param array  $data
     * @param string $title
     * @param array  $opts
     */
    public static function show(array $data, string $title = '', array $opts = []): void
    {
        if (!$data) {
            return;
        }

        $string = $title ? ColorTag::wrap(\ucfirst($title), 'comment') . ":\n" : '';
        $opts   = \array_merge([
            'char'       => '.',
            'indent'     => '  ',
            'padding'    => 10,
            'valueStyle' => 'info',
        ], $opts);

        $keyMaxLen  = Helper::getKeyMaxWidth($data);
        $paddingLen = $keyMaxLen > $opts['padding'] ? $keyMaxLen : $opts['padding'];

        foreach ($data as $label => $value) {
            $value  = ColorTag::wrap((string)$value, $opts['valueStyle']);
            $string .= $opts['indent'] . \str_pad($label, $paddingLen, $opts['char']) . " $value\n";
        }

        Show::write(\trim($string));
    }
}
