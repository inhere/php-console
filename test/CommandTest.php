<?php declare(strict_types=1);

namespace Inhere\ConsoleTest;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use PHPUnit\Framework\TestCase;

/**
 * Class CommandTest
 * @package Inhere\ConsoleTest
 */
class CommandTest extends TestCase
{
    public function testBasic(): void
    {
        $c = new TestCommand(new Input(), new Output());

        $this->assertSame('test1', $c::getName());
        $this->assertStringContainsString('desc', $c::getDescription());
    }
}
