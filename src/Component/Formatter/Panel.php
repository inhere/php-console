<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/22 0022
 * Time: 21:48
 */

namespace Inhere\Console\Component\Formatter;

use Toolkit\StrUtil\StrBuffer;
use Inhere\Console\Util\FormatUtil;

/**
 * Class Panel
 * - method version please {@see \Inhere\Console\Util\Show::panel()}
 * @package Inhere\Console\Component\Formatter
 */
class Panel extends Formatter
{
    /** @var string */
    public $title = '';

    /** @var string */
    public $titleBorder = '-';

    /** @var string */
    public $titleStyle = '-';

    /** @var string */
    public $titleAlign = self::ALIGN_LEFT;

    /** @var string|array */
    public $data;

    /** @var string */
    public $bodyAlign = self::ALIGN_LEFT;

    /** @var string */
    public $footerBorder = '-';

    /** @var string */
    public $footer = '';

    /** @var bool */
    public $ucFirst = true;

    /** @var int */
    public $width = 0;

    /** @var bool */
    public $border = true;

    /** @var string */
    public $borderYChar = '-';

    /** @var string */
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
        $buffer = new StrBuffer();

        if (!$this->data) {
            // self::write('<info>No data to display!</info>');
            return '';
        }

        $borderChar = $this->borderXChar;
        $data = \is_array($this->data) ? array_filter($this->data) : [\trim($this->data)];
        $title = \trim($this->title);

        $panelData = []; // [ 'label' => 'value' ]
        $labelMaxWidth = 0; // if label exists, label max width
        $valueMaxWidth = 0; // value max width

        foreach ($data as $label => $value) {
            // label exists
            if (!is_numeric($label)) {
                $width = mb_strlen($label, 'UTF-8');
                $labelMaxWidth = $width > $labelMaxWidth ? $width : $labelMaxWidth;
            }

            // translate array to string
            if (\is_array($value)) {
                $temp = '';

                /** @var array $value */
                foreach ($value as $key => $val) {
                    if (\is_bool($val)) {
                        $val = $val ? 'True' : 'False';
                    } else {
                        $val = (string)$val;
                    }

                    $temp .= (!is_numeric($key) ? "$key: " : '') . "<info>$val</info>, ";
                }

                $value = rtrim($temp, ' ,');
            } elseif (\is_bool($value)) {
                $value = $value ? 'True' : 'False';
            } else {
                $value = trim((string)$value);
            }

            // get value width
            /** @var string $value */
            $value = trim($value);
            $width = mb_strlen(strip_tags($value), 'UTF-8'); // must clear style tag
            $valueMaxWidth = $width > $valueMaxWidth ? $width : $valueMaxWidth;

            $panelData[$label] = $value;
        }

        $border = null;
        $panelWidth = $labelMaxWidth + $valueMaxWidth;

        // output title
        if ($title) {
            $title = ucwords($title);
            $titleLength = mb_strlen($title, 'UTF-8');
            $panelWidth = $panelWidth > $titleLength ? $panelWidth : $titleLength;
            $indentSpace = str_pad(' ', ceil($panelWidth / 2) - ceil($titleLength / 2) + 2 * 2, ' ');
            $buffer->write("  {$indentSpace}<bold>{$title}</bold>");
        }

        // output panel top border
        if ($borderChar) {
            $border = str_pad($borderChar, $panelWidth + (3 * 3), $borderChar);
            $buffer->write('  ' . $border);
        }

        // output panel body
        $panelStr = FormatUtil::spliceKeyValue($panelData, [
            'leftChar'    => "  $borderChar ",
            'sepChar'     => ' | ',
            'keyMaxWidth' => $labelMaxWidth,
            'ucFirst'     => $this->ucFirst,
        ]);

        // already exists "\n"
        $buffer->write($panelStr);

        // output panel bottom border
        if ($border) {
            $buffer->write("  $border\n");
        }

        unset($panelData);

        return $buffer->toString();
    }

    /**
     * @param bool $border
     * @return $this
     */
    public function border($border): self
    {
        $this->border = (bool)$border;

        return $this;
    }
}
