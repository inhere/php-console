<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:33
 */

namespace Inhere\Console\IO\Input;

/**
 * Class InputArgument
 * - definition a input argument
 *
 * @package Inhere\Console\IO\Input
 */
class InputArgument extends InputItem
{
    /**
     * @var int
     */
    private $index = 0;

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @param int $index
     */
    public function setIndex(int $index): void
    {
        $this->index = $index;
    }
}
