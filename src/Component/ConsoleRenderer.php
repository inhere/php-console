<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
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
     * @var FormatterInterface|null
     */
    private ?FormatterInterface $formatter;

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
