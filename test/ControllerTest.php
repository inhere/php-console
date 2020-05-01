<?php declare(strict_types=1);

namespace Inhere\ConsoleTest;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use PHPUnit\Framework\TestCase;

/**
 * Class ControllerTest
 * @package Inhere\ConsoleTest
 */
class ControllerTest extends TestCase
{
    public function testBasic()
    {
        $c = new TestController(new Input(), new Output());

        $this->assertSame('test', $c::getName());
        $this->assertStringContainsString('desc', $c::getDescription());
    }
}
