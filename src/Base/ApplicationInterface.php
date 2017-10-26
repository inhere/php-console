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

    /**
     * @param bool $exit
     * @return int
     */
    public function run($exit = true);

    public function stop($code = 0);

    /**
     * run a independent command
     * @param string $name
     * @param bool $believable
     * @return mixed
     */
    public function runCommand($name, $believable = false);

    /**
     * run a controller's action
     * @param string $name Controller name
     * @param string $action Command
     * @param bool $believable The `$name` is believable
     * @param bool $standAlone
     * @return mixed
     */
    public function runAction($name, $action, $believable = false, $standAlone = false);

    public function controller(string $name, string $controller = null);

    public function command(string $name, $handler = null, $description = null);

    public function showCommandList($quit = true);
}
