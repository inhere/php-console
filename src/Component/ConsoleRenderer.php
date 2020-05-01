<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-01-01
 * Time: 13:40
 */

namespace Inhere\Console\Component;

use Inhere\Console\Contract\FormatterInterface;

/**
 * Class ConsoleRenderer
 *
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

    /**
     * @return FormatterInterface
     */
    public function getFormatter(): FormatterInterface
    {
        return $this->formatter;
    }

    /**
     * @param FormatterInterface $formatter
     */
    public function setFormatter(FormatterInterface $formatter): void
    {
        $this->formatter = $formatter;
    }
}
