<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component;

use Inhere\Console\Console;
use Inhere\Console\Contract\FormatterInterface;
use RuntimeException;
use Toolkit\Stdlib\Obj\ObjectHelper;

/**
 * Class Formatter - message formatter
 *
 * @package Inhere\Console\Component
 */
abstract class MessageFormatter implements FormatterInterface
{
    // content align
    public const ALIGN_LEFT   = 'left';

    public const ALIGN_CENTER = 'center';

    public const ALIGN_RIGHT  = 'right';

    /**
     * @var array
     */
    protected array $config = [];

    /**
     * @var callable
     */
    protected $beforeWrite;

    /**
     * @param array $config
     *
     * @return MessageFormatter
     */
    public static function new(array $config = []): self
    {
        return new static($config);
    }

    /**
     * Formatter constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        ObjectHelper::init($this, $config);

        $this->config = $config;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function format(): string
    {
        throw new RuntimeException('Please implement the method on sub-class');
    }

    /**
     * Format and output message to steam.
     *
     * @return int
     */
    public function render(): int
    {
        return $this->display();
    }

    /**
     * Format and output message to steam.
     *
     * @return int
     */
    public function display(): int
    {
        return Console::write($this->toString());
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->format();
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return callable
     */
    public function getBeforeWrite(): callable
    {
        return $this->beforeWrite;
    }

    /**
     * @param callable $beforeWrite
     */
    public function setBeforeWrite(callable $beforeWrite): void
    {
        $this->beforeWrite = $beforeWrite;
    }
}
