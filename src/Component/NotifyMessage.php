<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-21
 * Time: 13:31
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
    protected $speed = 2;

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
    public function setSpeed($speed): void
    {
        $this->speed = (int)$speed;
    }

    public function display(): void
    {
        throw new RuntimeException('Please implement the method on sub-class');
    }
}
