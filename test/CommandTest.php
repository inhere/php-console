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
use PHPUnit\Framework\TestCase;

/**
 * Class CommandTest
 * @package Inhere\ConsoleTest
 */
class CommandTest extends TestCase
{
    public function testCommand_basic_usage(): void
    {
        $c = new TestCommand(new Input(), new Output());

        $this->assertSame('test1', $c::getName());
        $this->assertSame('test1', $c->getRealName());
        $this->assertStringContainsString('description', $c::getDesc());
        $this->assertStringContainsString('description', $c->getRealDesc());
    }

    public function testCommand_alone_run(): void
    {
        $c = new TestCommand(new Input(), new Output());

        $str = $c->run([]);
        $this->assertEquals('Inhere\ConsoleTest\TestCommand::execute', $str);
    }

    public function testCommand_alone_run_sub(): void
    {
        $c = new TestCommand(new Input(), new Output());

        $str = $c->run(['sub1']);
        $this->assertEquals('Inhere\ConsoleTest\{closure}', $str);
    }
}
