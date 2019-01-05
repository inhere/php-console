<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-31
 * Time: 13:12
 */

namespace Inhere\Console\Face;

use Inhere\Console\AbstractApplication;
use Inhere\Console\Application;

/**
 * Interface ErrorHandlerInterface
 * @package Inhere\Console\Face
 */
interface ErrorHandlerInterface
{
    /**
     * @param \Throwable                      $e
     * @param Application|AbstractApplication $app
     */
    public function handle(\Throwable $e, AbstractApplication $app);
}
