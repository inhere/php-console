<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/22 0022
 * Time: 21:48
 */

namespace Inhere\Console\Components\Formatter;

/**
 * Class Panel
 * - method version please {@see \Inhere\Console\Utils\Show::panel()}
 * @package Inhere\Console\Components\Formatter
 */
class Panel extends Formatter
{
    /** @var string  */
    public $title = '';

    /** @var string  */
    public $titleBorder = '-';

    /** @var string  */
    public $titleStyle = '-';

    public $titleAlign = self::ALIGN_LEFT;

    /** @var string|array */
    public $body;

    /** @var string */
    public $bodyAlign = self::ALIGN_LEFT;

    /** @var string  */
    public $footerBorder = '-';

    /** @var string  */
    public $footer = '';

    /** @var bool  */
    public $border = true;

    /** @var string  */
    public $borderYChar = '-';

    /** @var string  */
    public $borderXChar = '|';

    /**
     * @var string Template for the panel. don't contains border
     */
    public $template = <<<EOF
{%title%}
{%title-border%}
{%content%}
{%footer-border%}
{%footer%}
EOF;

    /**
     * @return string
     */
    public function toString(): string
    {
        $buffer = '';

        return '';
    }

    /**
     * @return string|array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array|string $body
     */
    public function setBody($body)
    {
        $this->body = (array)$body;
    }
}
