<?php declare(strict_types=1);

namespace Inhere\Console\Component\Formatter;

use Inhere\Console\Component\MessageFormatter;
use Inhere\Console\Console;
use Toolkit\Cli\ColorTag;
use Toolkit\Stdlib\Str;
use Toolkit\Sys\Sys;
use function array_merge;
use function ceil;

/**
 * Class Title
 *
 * @package Inhere\Console\Component\Formatter
 */
class Title extends MessageFormatter
{
    /**
     * @param string $title The title text
     * @param array  $opts
     */
    public static function show(string $title, array $opts = []): void
    {
        $opts = array_merge([
            'width'      => 80,
            'char'       => self::CHAR_EQUAL,
            'titlePos'   => self::POS_LEFT,
            'titleStyle' => 'bold',
            'indent'     => 0,
            'ucWords'    => true,
            'showBorder' => true,
        ], $opts);

        // list($sW, $sH) = Helper::getScreenSize();
        $width  = (int)$opts['width'];
        $char   = trim($opts['char']);
        $indent = (int)$opts['indent'] >= 0 ? $opts['indent'] : 2;

        $indentStr = '';
        if ($indent > 0) {
            $indentStr = Str::pad(self::CHAR_SPACE, $indent, self::CHAR_SPACE);
        }

        $title   = $opts['ucWords'] ? Str::ucwords(trim($title)) : trim($title);
        $tLength = Str::len($title);
        $width   = $width > 10 ? $width : 80;

        [$sw,] = Sys::getScreenSize();
        if ($sw > $width) {
            $width = $sw;
        }

        // title position
        if ($tLength >= $width) {
            $titleIndent = Str::pad(self::CHAR_SPACE, $indent, self::CHAR_SPACE);
        } elseif ($opts['titlePos'] === self::POS_RIGHT) {
            $titleIndent = Str::pad(self::CHAR_SPACE, ceil($width - $tLength) + $indent, self::CHAR_SPACE);
        } elseif ($opts['titlePos'] === self::POS_MIDDLE) {
            $titleIndent = Str::pad(self::CHAR_SPACE, ceil(($width - $tLength) / 2) + $indent, self::CHAR_SPACE);
        } else {
            $titleIndent = Str::pad(self::CHAR_SPACE, $indent, self::CHAR_SPACE);
        }

        $titleText = ColorTag::wrap($title, $opts['titleStyle']);
        $titleLine = "$titleIndent{$titleText}\n";

        $border = $indentStr . Str::pad($char, $width, $char);

        Console::write($titleLine . $border);
    }
}
