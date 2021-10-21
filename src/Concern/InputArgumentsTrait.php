<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Concern;

/**
 * Trait InputArgumentsTrait
 *
 * @package Inhere\Console\Concern
 */
trait InputArgumentsTrait
{
    /**
     * Input args data
     *
     * @var array
     */
    protected $args = [];

    /***********************************************************************************
     * arguments (eg: arg0 name=john city=chengdu)
     ***********************************************************************************/

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * clear args
     */
    public function clearArgs(): void
    {
        $this->args = [];
    }
}
