<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-01-01
 * Time: 13:40
 */

namespace Inhere\Console\Component;

use Inhere\Console\Component\Formatter\FormatterInterface;

/**
 * Class ConsoleRenderer
 * @package Inhere\Console\Component
 */
class ConsoleRenderer
{
    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @return string
     */
    public function render(): string
    {
        return '';
    }

    public function dump(): void
    {

    }
}
