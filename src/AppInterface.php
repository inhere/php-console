<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-14
 * Time: 16:51
 */

namespace inhere\console;

/**
 * Interface AppInterface
 * @package inhere\console
 */
interface AppInterface
{
    public function run($exit = true);
    public function stop($code = 0);

    public function controller(string $name, string $controller = null);
}