<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/22 0022
 * Time: 21:43
 */

namespace Inhere\Console\Components\Formatter;

/**
 * Class Table
 * - method version please {@see \Inhere\Console\Utils\Show::table()}
 * @package Inhere\Console\Components\Formatter
 */
class Table extends Formatter
{
    /**
     * @var array
     */
    public $data = [];

    /**
     * @var array
     */
    public $columns = [];

    /** @var string|array */
    public $body;

    /** @var string  */
    public $title = '';

    /** @var string  */
    public $titleBorder = '-';

    /** @var string  */
    public $titleStyle = '-';

    /** @var string */
    public $titleAlign = self::ALIGN_LEFT;

    /**
     * @return string
     */
    public function toString(): string
    {
        return '';
    }
}
