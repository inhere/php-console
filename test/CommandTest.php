<?php

namespace Inhere\ConsoleTest;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use PHPUnit\Framework\TestCase;
use Inhere\Console\Command;

/**
 * Class CommandTest
 * @package Inhere\ConsoleTest
 */
class CommandTest extends TestCase
{
    public function testBasic()
    {
        $c = new TestCommand(new Input(), new Output());

        $this->assertSame('test1', $c::getName());
        $this->assertContains('desc', $c::getDescription());
    }

}
