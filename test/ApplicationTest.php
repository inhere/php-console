<?php

namespace Inhere\ConsoleTest;

use Inhere\Console\Application;
use Inhere\Console\Console;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers Application
 */
class ApplicationTest extends TestCase
{
    private function newApp(array $args = null)
    {
        $input = new Input($args);

        return new Application([
            'name'    => 'Tests',
            'debug'   => 1,
            'version' => '1.0.0',
        ], $input);
    }

    public function testApp()
    {
        $app = Console::newApp([
            'name' => 'Tests',
        ]);

        $this->assertArrayHasKey('name', $app->getConfig());
        $this->assertEquals('Tests', $app->getName());
        $this->assertEquals('Tests', $app->getConfig('name'));

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

    public function testAddCommandError()
    {
        $app = $this->newApp();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp("/'name' and 'handler' cannot be empty/");
        $app->addCommand('');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"name" and "controller" cannot be empty/');
        $app->addCommand('test', 'invalid');
    }

    public function testRunCommand()
    {
        $app = $this->newApp([
            './app',
            'test'
        ]);

        $app->addCommand('test', function () {
            return 'hello';
        });

        $ret = $app->run(false);
        $this->assertSame('hello', $ret);
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

    public function testRunController()
    {
        $app = $this->newApp([
            './app',
            'test:demo'
        ]);

        $app->controller('test', TestController::class);

        $ret = $app->run(false);
        $this->assertSame('Inhere\ConsoleTest\TestController::demoCommand', $ret);
    }
}
