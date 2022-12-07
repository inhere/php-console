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
use Toolkit\Stdlib\Helper\DataHelper;
use Toolkit\Stdlib\Str;
use Toolkit\Stdlib\Str\StrBuffer;
use function array_keys;
use function array_merge;
use function array_sum;
use function ceil;
use function count;
use function is_bool;
use function is_string;

/**
 * Class Table - Tabular data display
 *
 * @package Inhere\Console\Component\Formatter
 */
class Table extends MessageFormatter
{
    /** @var array */
    public array $data = [];

    /** @var array */
    public array $columns = [];

    /** @var string|array */
    public string|array $body;

    /** @var string */
    public string $title = '';

    /** @var string */
    public string $titleBorder = '-';

    /** @var string */
    public string $titleStyle = '-';

    /** @var string */
    public string $titleAlign = self::ALIGN_LEFT;

    /**
     * Tabular data display
     *
     * @param array  $data
     * @param string $title
     * @param array  $opts
     *
     * @return int
     * @example
     *
     * ```php
     * // like from database query's data.
     * $data = [
     *  [ col1 => value1, col2 => value2, col3 => value3, ... ], // first row
     *  [ col1 => value4, col2 => value5, col3 => value6, ... ], // second row
     *  ... ...
     * ];
     * Show::table($data, 'a table');
     *
     * // use custom head
     * $data = [
     *  [ value1, value2, value3, ... ], // first row
     *  [ value4, value5, value6, ... ], // second row
     *  ... ...
     * ];
     * $opts = [
     *   'showBorder' => true,
     *   'columns' => [col1, col2, col3, ...]
     * ];
     * Show::table($data, 'a table', $opts);
     * ```
     */
    public static function show(array $data, string $title = 'Data Table', array $opts = []): int
    {
        if (!$data) {
            return -2;
        }

        $buf  = new StrBuffer();
        $opts = array_merge([
            'showBorder'     => true,
            'leftIndent'     => '  ',
            'titlePos'       => self::POS_LEFT,
            'titleStyle'     => 'bold',
            'headStyle'      => 'comment',
            'headBorderChar' => self::CHAR_EQUAL,   // default is '='
            'bodyStyle'      => '',
            'rowBorderChar'  => self::CHAR_HYPHEN,   // default is '-'
            'colBorderChar'  => self::CHAR_VERTICAL, // default is '|'
            'columns'        => [],                  // custom column names
        ], $opts);

        $hasHead       = false;
        $rowIndex      = 0;
        $head          = [];
        $tableHead     = $opts['columns'];
        $leftIndent    = $opts['leftIndent'];
        $showBorder    = $opts['showBorder'];
        $rowBorderChar = $opts['rowBorderChar'];
        $colBorderChar = $opts['colBorderChar'];

        $info = [
            // 'rowCount'       => count($data),
            'columnCount'    => 0,     // how many column in the table.
            'columnMaxWidth' => [], // table column max width
            // 'tableWidth'     => 0,      // table width. equals to all max column width's sum.
        ];

        // parse table data
        foreach ($data as &$row) {
            // collection all field name
            if ($rowIndex === 0) {
                $head = $tableHead ?: array_keys($row);
                // column count
                $info['columnCount'] = count($row);

                foreach ($head as $index => $name) {
                    if (is_string($name)) {// maybe no column name.
                        $hasHead = true;
                    }

                    $info['columnMaxWidth'][$index] = Str::utf8Len($name);
                }
            }

            $colIndex = 0;
            $rowData = (array)$row;

            foreach ($rowData as &$value) {
                // always convert to string
                $value = DataHelper::toString($value);

                // collection column max width
                if (isset($info['columnMaxWidth'][$colIndex])) {
                    $colWidth = Str::utf8Len($value);

                    // If current column width gt old column width. override old width.
                    if ($colWidth > $info['columnMaxWidth'][$colIndex]) {
                        $info['columnMaxWidth'][$colIndex] = $colWidth;
                    }
                } else {
                    $info['columnMaxWidth'][$colIndex] = Str::utf8Len($value);
                }

                $colIndex++;
            }
            unset($value);

            $rowIndex++;
            $row = $rowData;
        }
        unset($row);

        $columnCount = $info['columnCount'];
        $tableWidth  = (int)array_sum($info['columnMaxWidth']);

        // output title
        if ($title) {
            $tStyle      = $opts['titleStyle'] ?: 'bold';
            $title       = Str::ucwords(trim($title));
            $titleLength = Str::utf8Len($title);
            $indentSpace = Str::pad(' ', ceil($tableWidth / 2) - ceil($titleLength / 2) + ($columnCount * 2));
            $buf->write("  $indentSpace<$tStyle>$title</$tStyle>\n");
        }

        $border = $leftIndent . Str::pad($rowBorderChar, $tableWidth + ($columnCount * 3) + 2, $rowBorderChar);

        // output table top border
        if ($showBorder) {
            $buf->write($border . "\n");
        } else {
            $colBorderChar = '';// clear column border char
        }

        // output table head
        if ($hasHead) {
            $headStr = "$leftIndent$colBorderChar ";

            foreach ($head as $index => $name) {
                $colMaxWidth = $info['columnMaxWidth'][$index];
                // format head title
                // $name = Str::pad($name, $colMaxWidth, ' ');
                // use Str::padByWidth support zh-CN words
                $name = Str::padByWidth($name, $colMaxWidth);
                $name = ColorTag::wrap($name, $opts['headStyle']);
                // join string
                $headStr .= " $name $colBorderChar";
            }

            $buf->write($headStr . "\n");

            // head border: split head and body
            if ($headBorderChar = $opts['headBorderChar']) {
                $headBorder = $leftIndent . Str::pad(
                    $headBorderChar,
                    $tableWidth + ($columnCount * 3) + 2,
                    $headBorderChar
                );
                $buf->write($headBorder . "\n");
            }
        }

        $rowIndex = 0;

        // output table info
        foreach ($data as $row) {
            $colIndex = 0;
            $rowStr   = "  $colBorderChar ";

            foreach ((array)$row as $value) {
                $colMaxWidth = $info['columnMaxWidth'][$colIndex];
                // format
                if (is_bool($value)) {
                    $value = $value ? 'TRUE' : 'FALSE';
                }

                // $value = Str::pad($value, $colMaxWidth, ' ');
                // use Str::padByWidth support zh-CN words
                $value  = Str::padByWidth($value, $colMaxWidth);
                $value  = ColorTag::wrap($value, $opts['bodyStyle']);
                $rowStr .= " $value $colBorderChar";
                $colIndex++;
            }

            $buf->write($rowStr . "\n");
            $rowIndex++;
        }

        // output table bottom border
        if ($showBorder) {
            $buf->write($border . "\n");
        }

        return Console::write($buf);
    }
}
