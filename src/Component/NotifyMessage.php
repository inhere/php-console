<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component;

use RuntimeException;

/**
 * Class NotifyMessage - like progress, spinner ....
 *
 * @package Inhere\Console\Component
 * @link    https://github.com/wp-cli/php-cli-tools/tree/master/lib/cli
 */
class NotifyMessage
{
    /** @var int Speed value. allow 1 - 10 */
    protected int $speed = 2;

    /**
     * @return int
     */
    public function getSpeed(): int
    {
        return $this->speed;
    }

    /**
     * @param int $speed
     */
    public function setSpeed(int $speed): void
    {
        $this->speed = (int)$speed;
    }

    public function display(): void
    {
        throw new RuntimeException('Please implement the method on sub-class');
    }
}
