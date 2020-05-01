<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-30
 * Time: 18:58
 */

namespace Inhere\ConsoleTest;

use Inhere\Console\Controller;

/**
 * Class TestController
 *
 * @package Inhere\ConsoleTest
 */
class TestController extends Controller
{
    protected static $name = 'test';

    protected static $description = 'controller description message';

    /**
     * this is an demo command in test
     *
     * @return mixed
     */
    public function demoCommand()
    {
        return __METHOD__;
    }
}
