<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
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
