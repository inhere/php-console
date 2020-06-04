<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:33
 */

namespace Inhere\Console\IO\Input;

use Inhere\Console\IO\Input;

/**
 * Class InputArgument
 * - definition a input argument
 *
 * @package Inhere\Console\IO\Input
 */
class InputArgument extends InputFlag
{
    /**
     * The argument position
     *
     * @var int
     */
    private $index = 0;

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->hasMode(Input::ARG_IS_ARRAY);
    }

    /**
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->hasMode(Input::ARG_OPTIONAL);
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->hasMode(Input::ARG_REQUIRED);
    }

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
