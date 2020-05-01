<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-31
 * Time: 13:12
 */

namespace Inhere\Console\Contract;

use Inhere\Console\AbstractApplication;
use Inhere\Console\Application;
use Throwable;

/**
 * Interface ErrorHandlerInterface
 *
 * @package Inhere\Console\Contract
 */
interface ErrorHandlerInterface
{
    /**
     * @param Throwable                       $e
     * @param Application|AbstractApplication $app
     */
    public function handle(Throwable $e, AbstractApplication $app): void;
}
