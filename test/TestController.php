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
    protected static string $name = 'test';

    protected static string $desc = 'controller description message';

    /**
     * this is an demo command in test
     *
     * @return string
     */
    public function demoCommand(): string
    {
        return __METHOD__;
    }
}
