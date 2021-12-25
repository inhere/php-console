<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\ConsoleTest;

use Inhere\Console\Application;
use Inhere\Console\Console;
use Inhere\Console\IO\Input;
use Inhere\Console\Contract\InputInterface;
use Inhere\Console\Component\Router;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Throwable;
use function get_class;
use function strpos;

/**
 * Class ApplicationTest
 *
 * @package Inhere\ConsoleTest
 */
class ApplicationTest extends TestCase
{
    protected function assertStringContains(string $string, string $contains): void
    {
        self::assertNotFalse(strpos($string, $contains), "string \"$string\" not contains: $contains");
    }

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

        self::assertArrayHasKey('name', $app->getConfig());
        self::assertEquals('Tests', $app->getName());
        self::assertEquals('Tests', $app->getParam('name'));

        self::assertInstanceOf(InputInterface::class, $app->getInput());
    }

    public function testAddCommand(): void
    {
        $app = $this->newApp();

        $app->addCommand('test', function () {
            return 0;
        });

        $router = $app->getRouter();

        self::assertTrue($router->isCommand('test'));
        self::assertFalse($router->isController('test'));
        self::assertArrayHasKey('test', $router->getCommands());
        self::assertContains('test', $router->getCommandNames());
    }

    public function testAddCommandError(): void
    {
        $app = $this->newApp();
        try {
            $app->addCommand('');
        } catch (Throwable $e) {
            self::assertSame(get_class($e), InvalidArgumentException::class);
        }

        try {
            $app->addCommand('test', 'invalid');
        } catch (Throwable $e) {
            self::assertSame(get_class($e), InvalidArgumentException::class);
            self::assertSame($e->getMessage(), "The console command class 'invalid' not exists!");
        }
    }

    public function testAddCommand_class_run(): void
    {
        $app = $this->newApp([
            './app',
            'test1'
        ]);
        $app->addCommand(TestCommand::class);

        $ret = $app->run(false);
        self::assertSame('Inhere\ConsoleTest\TestCommand::execute', $ret);
    }

    public function testAddCommand_callback_run(): void
    {
        $app = $this->newApp([
            './app',
            'test'
        ]);

        $app->addCommand('test', function () {
            return 'hello';
        });

        $ret = $app->run(false);
        self::assertSame('hello', $ret);
    }

    public function testAddCommand_object_run(): void
    {
        $app = $this->newApp([
            './app',
            'test'
        ]);

        $app->command('test', new TestCommand());

        $ret = $app->run(false);
        self::assertSame('Inhere\ConsoleTest\TestCommand::execute', $ret);
    }

    public function testAddController(): void
    {
        $app = $this->newApp();

        $app->addGroup('test', TestController::class);

        $router = $app->getRouter();

        self::assertTrue($app->getRouter()->isController('test'));
        self::assertFalse($app->getRouter()->isCommand('test'));
        self::assertArrayHasKey('test', $router->getControllers());

        $group = $router->getControllers()['test'];
        self::assertSame(TestController::class, $group['handler']);
        self::assertSame(Router::TYPE_GROUP, $group['type']);
    }

    public function testAddControllerError(): void
    {
        $app = $this->newApp();

        try {
            $app->addController('');
        } catch (Throwable $e) {
            self::assertSame(get_class($e), InvalidArgumentException::class);
            $this->assertStringContains($e->getMessage(), '"name" and "controller" cannot be empty');
        }

        try {
            $app->controller('test', 'invalid');
        } catch (Throwable $e) {
            self::assertSame(get_class($e), InvalidArgumentException::class);
            self::assertSame($e->getMessage(), 'The console controller class [invalid] not exists!');
        }
    }

    public function testAdd_Controller_class_Run(): void
    {
        $app = $this->newApp([
            './app',
            'test:demo'
        ]);

        $app->controller('test', TestController::class);

        $ret = $app->run(false);
        self::assertSame('Inhere\ConsoleTest\TestController::demoCommand', $ret);
    }

    public function testAdd_Controller_object_Run(): void
    {
        $app = $this->newApp([
            './app',
            'test:demo'
        ]);

        $app->controller('test', new TestController);

        $ret = $app->run(false);
        self::assertSame('Inhere\ConsoleTest\TestController::demoCommand', $ret);
    }

    public function testTriggerEvent(): void
    {
        $app = $this->newApp([
            './app',
            'test1'
        ]);

        $app->on(Application::ON_BEFORE_RUN, function (Application $app): void {
            $this->assertEquals('Tests', $app->getName());
        });

        $app->addCommand('test1', TestCommand::class);

        $ret = $app->run(false);
        self::assertSame('Inhere\ConsoleTest\TestCommand::execute', $ret);
    }
}
