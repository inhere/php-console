<?php

namespace Inhere\ConsoleTest;

use Inhere\Console\Application;
use Inhere\Console\Console;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputInterface;
use Inhere\Console\Router;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    private function newApp(array $args = null): Application
    {
        $input = new Input($args);

        return new Application([
            'name'    => 'Tests',
            'debug'   => 1,
            'version' => '1.0.0',
        ], $input);
    }

    public function testApp(): void
    {
        $app = Console::newApp([
            'name' => 'Tests',
        ]);

        $this->assertArrayHasKey('name', $app->getConfig());
        $this->assertEquals('Tests', $app->getName());
        $this->assertEquals('Tests', $app->getParam('name'));

        $this->assertInstanceOf(InputInterface::class, $app->getInput());
    }

    public function testAddCommand(): void
    {
        $app = $this->newApp();

        $app->addCommand('test', function () {
            return 0;
        });

        $router = $app->getRouter();

        $this->assertTrue($router->isCommand('test'));
        $this->assertFalse($router->isController('test'));
        $this->assertArrayHasKey('test', $router->getCommands());
        $this->assertContains('test', $router->getCommandNames());
    }

    public function testAddCommandError(): void
    {
        $app = $this->newApp();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp("/'name' and 'handler' cannot be empty/");
        $app->addCommand('');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"name" and "controller" cannot be empty/');
        $app->addCommand('test', 'invalid');
    }

    public function testRunCommand(): void
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

    public function testAddController(): void
    {
        $app = $this->newApp();

        $app->addGroup('test', TestController::class);

        $router = $app->getRouter();

        $this->assertTrue($app->getRouter()->isController('test'));
        $this->assertFalse($app->getRouter()->isCommand('test'));
        $this->assertArrayHasKey('test', $router->getControllers());

        $group = $router->getControllers()['test'];
        $this->assertSame(TestController::class, $group['handler']);
        $this->assertSame(Router::TYPE_GROUP, $group['type']);
    }

    public function testAddControllerError(): void
    {
        $app = $this->newApp();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"name" and "controller" cannot be empty/');
        $app->addController('');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"name" and "controller" cannot be empty/');
        $app->controller('test', 'invalid');
    }

    public function testRunController(): void
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
