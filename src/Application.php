<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console;

use Closure;
use Inhere\Console\Component\Router;
use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\Contract\ControllerInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\Helper;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Toolkit\FsUtil\Dir;
use Toolkit\PFlag\FlagsParser;
use Toolkit\PFlag\SFlags;
use Toolkit\Stdlib\Helper\Assert;
use Toolkit\Stdlib\Helper\DataHelper;
use Toolkit\Stdlib\Str;
use function array_shift;
use function array_unshift;
use function class_exists;
use function implode;
use function is_dir;
use function is_object;
use function is_string;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function trim;

/**
 * Class App
 *
 * @package Inhere\Console
 */
class Application extends AbstractApplication
{
    /**
     * Class constructor.
     *
     * @param array $config
     * @param Input|null $input
     * @param Output|null $output
     */
    public function __construct(array $config = [], Input $input = null, Output $output = null)
    {
        Console::setApp($this);

        parent::__construct($config, $input, $output);
    }

    /****************************************************************************
     * Dispatch and run console controller/command
     ****************************************************************************/

    /**
     * @param string $name command name or command ID or command path.
     * @param array $args
     *
     * @return mixed
     * @throws Throwable
     */
    public function dispatch(string $name, array $args = []): mixed
    {
        if (!$name = trim($name)) {
            throw new InvalidArgumentException('cannot dispatch an empty command');
        }

        $cmdId = $name;
        $this->debugf('app - begin dispatch the input command: "%s", args: %s', $name, DataHelper::toString($args));

        // format is: `group action` or `top sub sub2`
        if (strpos($name, ' ') > 0) {
            $names = Str::splitTrimmed($name, ' ');
            $cmdId = array_shift($names);

            // prepend elements to the beginning of $args
            array_unshift($args, ...$names);
        }

        // match handler by input name
        $info = $this->router->match($cmdId);

        // command not found
        if (!$info) {
            $evtName = ConsoleEvent::ON_NOT_FOUND;
            if (true === $this->fire($evtName, $cmdId, $this)) {
                $this->debugf('user custom handle the not found command: %s, event: %s', $name, $evtName);
                return 0;
            }

            $commands = $this->router->getAllNames();
            $this->output->error("The command '$name' is not exists!");

            // find similar command names by similar_text()
            if ($similar = Helper::findSimilar($name, $commands)) {
                $this->output->printf("\nMaybe what you mean is:\n    <info>%s</info>", implode(', ', $similar));
            } else {
                // $this->showCommandList();
                $scriptName = $this->getScriptName();
                $this->output->colored("\nPlease use '$scriptName --help' for see all available commands");
            }

            return 2;
        }

        // save command ID
        $cmdConf = $info['config'];
        unset($info['config']);
        $this->input->setCommandId($info['cmdId']);

        // is command
        if ($info['type'] === Router::TYPE_SINGLE) {
            return $this->runCommand($info, $cmdConf, $args);
        }

        // is controller/group
        return $this->runAction($info, $cmdConf, $args);
    }

    /**
     * run a independent command
     *
     * @param array{name: string, handler: mixed, realName: string} $info
     * @param array{aliases: array, desc: string, options: array, arguments: array} $config The config.
     * @param array $args
     *
     * @return mixed
     * @throws Throwable
     */
    protected function runCommand(array $info, array $config, array $args): mixed
    {
        /** @var Closure|class-string{Command} $handler Command class or handler func */
        $handler = $info['handler'];
        $iptName = $info['name'];

        if (is_object($handler)) {
            // Command object
            if ($handler instanceof Command) {
                $handler->setInputOutput($this->input, $this->output);
                $result = $handler->run($args);
            } else { // Closure
                $fs = SFlags::new();
                $fs->setName($iptName);
                $fs->addOptsByRules(GlobalOption::getAloneOptions());

                // command flags load
                if ($cmdOpts = $config['options'] ?? null) {
                    $fs->addOptsByRules($cmdOpts);
                }
                if ($cmdArgs = $config['arguments'] ?? null) {
                    $fs->addArgsByRules($cmdArgs);
                }

                $fs->setDesc($config['desc'] ?? 'No command description message');

                // save to input object
                $this->input->setFs($fs);

                if (!$fs->parse($args)) {
                    return 0; // render help
                }

                $result = $handler($fs, $this->output);
            }
        } else {
            Assert::isTrue(class_exists($handler), "The console command class [$handler] not exists!");

            /** @var $cmd Command */
            $cmd = new $handler($this->input, $this->output);
            Assert::isTrue($cmd instanceof Command, "Command class [$handler] must instanceof the " . Command::class);

            $cmd::setName($info['cmdId']); // real command name.
            $cmd->setApp($this);
            $cmd->setCommandName($iptName);
            $result = $cmd->run($args);
        }

        return $result;
    }

    /**
     * Execute an action in a group command(controller)
     *
     * @param array{action: string} $info Matched route info
     * @param array $config
     * @param array $args
     * @param bool $detachedRun
     *
     * @return mixed
     * @throws Throwable
     */
    protected function runAction(array $info, array $config, array $args, bool $detachedRun = false): mixed
    {
        $controller = $this->createController($info);
        $controller::setDesc($config['desc'] ?? '');

        if ($detachedRun) {
            $controller->setDetached();
        }

        if ($info['sub']) {
            array_unshift($args, $info['sub']);
        }

        // Command method, no suffix
        return $controller->run($args);
    }

    /**
     * @param string $name
     *
     * @return Controller
     */
    public function getController(string $name): Controller
    {
        $info = $this->router->getControllerInfo($name);
        if (!$info) {
            throw new RuntimeException('the group controller not exist. name: ' . $name);
        }

        $info['group'] = $name;
        return $this->createController($info);
    }

    /**
     * @param array{name: string, group: string, handler: mixed} $info
     *
     * @return Controller
     */
    protected function createController(array $info): Controller
    {
        $group = $info['group']; // The group name
        if (isset($this->groupObjects[$group])) {
            $this->debugf('load the "%s" controller object form cache', $group);
            return $this->groupObjects[$group];
        }

        $this->debugf('create the "%s" controller object and cache it', $group);

        /** @var Controller $handler */
        $handler = $info['handler']; // The controller class or object
        if (is_string($handler)) {
            $class = $handler;
            Assert::isTrue(class_exists($class), "The console controller class '$class' not exists!");

            // create group object
            $handler = new $class();
        }

        if (!$handler instanceof Controller) {
            Helper::throwInvalidArgument(
                'The console controller class [%s] must instanceof the %s',
                $handler,
                Controller::class
            );
        }

        // force set name and description
        $handler::setName($group);
        $handler->setApp($this);
        $handler->setInputOutput($this->input, $this->output);

        // set input name
        if ($inputName = $info['name'] ?? '') {
            $handler->setGroupName($inputName);
        }

        $handler->setDelimiter($this->delimiter);

        // cache object
        $this->groupObjects[$group] = $handler;
        return $handler;
    }

    /****************************************************************************
     * register console controller/command
     ****************************************************************************/

    /**
     * @param string $name
     * @param ControllerInterface|string|null $class
     * @param array $config
     *
     * @return $this
     */
    public function controller(string $name, ControllerInterface|string $class = null, array $config = []): static
    {
        $this->logf(Console::VERB_CRAZY, 'register group controller: %s', $name);
        $this->router->addGroup($name, $class, $config);

        return $this;
    }

    /**
     * Add group/controller
     *
     * @param string|class-string $name
     * @param string|ControllerInterface|null $class The controller class
     * @param array $config
     *
     * @return static
     * @see controller()
     */
    public function addGroup(string $name, ControllerInterface|string $class = null, array $config = []): static
    {
        return $this->controller($name, $class, $config);
    }

    /**
     * @param string $name
     * @param string|ControllerInterface|null $class The controller class
     * @param array $config
     *
     * @return $this
     * @see controller()
     */
    public function addController(string $name, ControllerInterface|string $class = null, array $config = []): static
    {
        return $this->controller($name, $class, $config);
    }

    /**
     * @param array $controllers
     */
    public function controllers(array $controllers): void
    {
        $this->addControllers($controllers);
    }

    /**
     * @param array $controllers
     */
    public function addControllers(array $controllers): void
    {
        $this->router->addControllers($controllers);
    }

    /**
     * @param string $name
     * @param class-string|CommandInterface|null|Closure(FlagsParser, Output):void $handler
     * @param array{desc: string, aliases: array, options: array, arguments: array} $config config the command.
     *
     * @return Application
     */
    public function command(string $name, string|Closure|CommandInterface $handler = null, array $config = []): static
    {
        $this->logf(Console::VERB_CRAZY, 'register alone command: %s', $name);
        $this->router->addCommand($name, $handler, $config);

        return $this;
    }

    /**
     * add command
     *
     * @param string $name
     * @param class-string|CommandInterface|null|Closure(FlagsParser, Output):void $handler
     * @param array{desc: string, aliases: array, options: array, arguments: array} $config config the command.
     *
     * @return Application
     * @see command()
     */
    public function addCommand(string $name, string|Closure|CommandInterface $handler = null, array $config = []): static
    {
        return $this->command($name, $handler, $config);
    }

    /**
     * @param array{string, mixed} $commands
     */
    public function addCommands(array $commands): void
    {
        $this->router->addCommands($commands);
    }

    /**
     * @param array{string, mixed} $commands
     */
    public function commands(array $commands): void
    {
        $this->addCommands($commands);
    }

    /**
     * auto register commands from a dir.
     *
     * ```php
     * $app->registerCommands('SwagPhp\Command', dirname(__DIR__) . '/src/Command');
     * ```
     *
     * @param string $namespace
     * @param string $basePath
     *
     * @return $this
     */
    public function registerCommands(string $namespace, string $basePath): static
    {
        if (!is_dir($basePath)) {
            return $this;
        }

        $this->debugf('register commands from the namespace: %s', $namespace);

        $length = strlen($basePath) + 1;
        // $iterator = Helper::directoryIterator($basePath, $this->getFileFilter());
        $iter = Dir::getIterator($basePath, Dir::getPhpFileFilter());

        foreach ($iter as $file) {
            $subPath  = substr($file->getPathName(), $length, -4);
            $fullClass = $namespace . '\\' . str_replace('/', '\\', $subPath);
            $this->addCommand($fullClass);
        }

        return $this;
    }

    /**
     * auto register controllers from a dir.
     *
     * @param string $namespace
     * @param string $basePath
     *
     * @return $this
     */
    public function registerGroups(string $namespace, string $basePath): self
    {
        if (!is_dir($basePath)) {
            return $this;
        }
        $this->debugf('register groups from the namespace: %s', $namespace);

        $length = strlen($basePath) + 1;
        // $iterator = Helper::directoryIterator($basePath, $this->getFileFilter());
        $iter = Dir::getIterator($basePath, Dir::getPhpFileFilter());

        foreach ($iter as $file) {
            $subPath  = substr($file->getPathName(), $length, -4);
            $fullClass = $namespace . '\\' . str_replace('/', '\\', $subPath);
            $this->addController($fullClass);
        }

        return $this;
    }
}
