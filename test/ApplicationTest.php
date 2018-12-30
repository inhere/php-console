<?php

namespace Inhere\ConsoleTest;

use Inhere\Console\Application;
use Inhere\Console\Console;
use Inhere\Console\IO\InputInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers Application
 */
class ApplicationTest extends TestCase
{
    private function newApp()
    {
        return new Application([
            'name'    => 'Tests',
            'debug'   => 1,
            'version' => '1.0.0',
        ]);
    }

    public function testApp()
    {
        $app = Console::newApp([
            'name' => 'Tests',
        ]);

        $this->assertArrayHasKey('name', $app->getMeta());
        $this->assertEquals('Tests', $app->getName());
        $this->assertEquals('Tests', $app->getMeta('name'));

        $this->assertInstanceOf(InputInterface::class, $app->getInput());
    }

    public function testAddCommand()
    {
        $app = $this->newApp();

        $app->addCommand('test', function () {
            return 0;
        });

        $this->assertTrue($app->isCommand('test'));
        $this->assertFalse($app->isController('test'));
        $this->assertArrayHasKey('test', $app->getCommands());
        $this->assertContains('test', $app->getCommandNames());
    }

    public function testAddController()
    {
        $app = $this->newApp();

        $app->addGroup('test', TestController::class);

        $this->assertTrue($app->isController('test'));
        $this->assertFalse($app->isCommand('test'));
        $this->assertArrayHasKey('test', $app->getControllers());
        $this->assertContains('test', $app->getControllerNames());
        $this->assertSame(TestController::class, $app->getControllers()['test']);
    }

    public function testAddControllerError()
    {
        $app = $this->newApp();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"name" and "controller" cannot be empty/');
        $app->addController('');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"name" and "controller" cannot be empty/');
        $app->controller('test', 'invalid');
    }
}
