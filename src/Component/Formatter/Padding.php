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
use Inhere\Console\Console;
use Toolkit\Cli\Color\ColorTag;
use Toolkit\Stdlib\Arr\ArrayHelper;
use Toolkit\Stdlib\Str;
use function array_merge;
use function trim;
use function ucfirst;

/**
 * Class Padding
 *
 * @package Inhere\Console\Component\Formatter
 */
class Padding extends MessageFormatter
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

        $string = $title ? ColorTag::wrap(ucfirst($title), 'comment') . ":\n" : '';
        $opts   = array_merge([
            'char'       => '.',
            'indent'     => '  ',
            'padding'    => 10,
            'valueStyle' => 'info',
        ], $opts);

        $keyMaxLen  = ArrayHelper::getKeyMaxWidth($data);
        $paddingLen = $keyMaxLen > $opts['padding'] ? $keyMaxLen : $opts['padding'];

        foreach ($data as $label => $value) {
            $value  = ColorTag::wrap((string)$value, $opts['valueStyle']);
            $string .= $opts['indent'] . Str::pad($label, $paddingLen, $opts['char']) . " $value\n";
        }

        Console::write(trim($string));
    }
}
