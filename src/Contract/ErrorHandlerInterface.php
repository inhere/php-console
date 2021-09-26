<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Contract;

use Throwable;

/**
 * Interface ErrorHandlerInterface
 *
 * @package Inhere\Console\Contract
 */
interface ErrorHandlerInterface
{
    /**
     * @param Throwable $e
     */
    public function handle(Throwable $e): void;
}
