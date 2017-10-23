<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-14
 * Time: 16:51
 */

namespace Inhere\Console\Base;

/**
 * Interface ApplicationInterface
 * @package Inhere\Console
 */
interface ApplicationInterface
{
    // event name list
    const ON_BEFORE_RUN = 'beforeRun';
    const ON_AFTER_RUN = 'afterRun';
    const ON_RUN_ERROR = 'runError';
    const ON_STOP_RUN = 'stopRun';
    const ON_NOT_FOUND = 'notFound';

    public function run($exit = true);

    public function stop($code = 0);

    public function controller(string $name, string $controller = null);

    public function command(string $name, $handler = null, $description = null);
}
