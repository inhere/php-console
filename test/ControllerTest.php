<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\ConsoleTest;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

/**
 * Class ControllerTest
 * @package Inhere\ConsoleTest
 */
class ControllerTest extends BaseTestCase
{
    public function testBasic(): void
    {
        $c = new TestController(new Input(), new Output());

        $this->assertSame('test', $c::getName());
        $this->assertStringContainsString('desc', $c::getDesc());
    }
}
