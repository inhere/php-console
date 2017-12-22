<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-21
 * Time: 13:31
 */

namespace Inhere\Console\Components;

/**
 * Class NotifyMessage - like progress, spinner ....
 * @package Inhere\Console\Components
 * @link https://github.com/wp-cli/php-cli-tools/tree/master/lib/cli
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
    public function setSpeed($speed)
    {
        $this->speed = (int)$speed;
    }

}
