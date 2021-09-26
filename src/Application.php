<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 17:56
 */

namespace Inhere\Console;

use Closure;
use Inhere\Console\Contract\ApplicationInterface;
use Inhere\Console\Contract\ControllerInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\Helper;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;
use Throwable;
use Toolkit\PFlag\SFlags;
use Toolkit\Stdlib\Helper\DataHelper;
use function array_unshift;
use function class_exists;
use function implode;
use function is_object;
use function is_string;
use function method_exists;
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
     * register console controller/command
     ****************************************************************************/

    /**
     * {@inheritdoc}
     */
    public function controller(string $name, $class = null, array $config = []): ApplicationInterface
    {
        $this->logf(Console::VERB_CRAZY, 'register group controller: %s', $name);
        $this->router->addGroup($name, $class, $config);

        return $this;
    }

    /**
     * Add group/controller
     *
     * @param string $name
     * @param string|ControllerInterface|null $class The controller class
     * @param array $config
     *
     * @return Application|Contract\ApplicationInterface
     * @see controller()
     */
    public function addGroup(string $name, $class = null, array $config = []): ApplicationInterface
    {
        return $this->controller($name, $class, $config);
    }

    /**
     * @param string $name
     * @param string|ControllerInterface|null $class The controller class
     * @param array $config
     *
     * @return Application|Contract\ApplicationInterface
     * @see controller()
     */
    public function addController(string $name, $class = null, array $config = []): ApplicationInterface
    {
        return $this->controller($name, $class, $config);
    }

    /**
     * @param array $controllers
     *
     * @throws InvalidArgumentException
     */
    public function controllers(array $controllers): void
    {
        $this->addControllers($controllers);
    }

    /**
     * @param array $controllers
     *
     * @throws InvalidArgumentException
     * @deprecated please use addControllers() instead it.
     */
    public function setControllers(array $controllers): void
    {
        $this->addControllers($controllers);
    }

    /**
     * @param array $controllers
     *
     * @throws InvalidArgumentException
     */
    public function addControllers(array $controllers): void
    {
        $this->router->addControllers($controllers);
    }

    /**
     * {@inheritdoc}
     */
    public function command(string $name, $handler = null, array $config = [])
    {
        $this->logf(Console::VERB_CRAZY, 'register alone command: %s', $name);
        $this->router->addCommand($name, $handler, $config);

        return $this;
    }

    /**
     * add command
     *
     * @param string $name
     * @param null|mixed $handler
     * @param array $config
     *
     * @return Application
     * @see command()
     */
    public function addCommand(string $name, $handler = null, array $config = []): self
    {
        return $this->command($name, $handler, $config);
    }

    /**
     * @param array $commands
     *
     * @throws InvalidArgumentException
     */
    public function addCommands(array $commands): void
    {
        $this->router->addCommands($commands);
    }

    /**
     * @param array $commands
     *
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     */
    public function registerCommands(string $namespace, string $basePath): self
    {
        $length   = strlen($basePath) + 1;
        $iterator = Helper::directoryIterator($basePath, $this->getFileFilter());

        foreach ($iterator as $file) {
            $class = $namespace . '\\' . substr($file->getPathName(), $length, -4);
            $this->addCommand($class);
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
     * @throws InvalidArgumentException
     */
    public function registerGroups(string $namespace, string $basePath): self
    {
        $length   = strlen($basePath) + 1;
        $iterator = Helper::directoryIterator($basePath, $this->getFileFilter());

        foreach ($iterator as $file) {
            $class = $namespace . '\\' . substr($file->getPathName(), $length, -4);
            $this->addController($class);
        }

        return $this;
    }

    /**
     * @return Closure
     */
    protected function getFileFilter(): callable
    {
        return static function (SplFileInfo $f) {
            $name = $f->getFilename();

            // Skip hidden files and directories.
            if (strpos($name, '.') === 0) {
                return false;
            }

            // go on read sub-dir
            if ($f->isDir()) {
                return true;
            }

            // php file
            return $f->isFile() && substr($name, -4) === '.php';
        };
    }

    /****************************************************************************
     * Dispatch and run console controller/command
     ****************************************************************************/

    /**
     * @param string $name command name or command ID or command path.
     * @param array $args
     *
     * @return int|mixed
     * @throws Throwable
     */
    public function dispatch(string $name, array $args = [])
    {
        if (!$name = trim($name)) {
            throw new InvalidArgumentException('cannot dispatch an empty command');
        }

        $cmdId = $name;
        $this->debugf('begin dispatch the input command: %s, args: %s', $name, DataHelper::toString($args));

        // format is: `group action`
        if (strpos($name, ' ') > 0) {
            $cmdId = str_replace(' ', $this->delimiter, $name);
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
        $cmdOptions = $info['options'];
        unset($info['options']);
        $this->input->setCommandId($info['cmdId']);

        // is command
        if ($info['type'] === Router::TYPE_SINGLE) {
            return $this->runCommand($info['name'], $info['handler'], $cmdOptions, $args);
        }

        // is controller/group
        return $this->runAction($info, $cmdOptions, $args);
    }

    /**
     * run a independent command
     *
     * @param string $name Command name
     * @param Closure|string $handler Command class or handler func
     * @param array $options
     * @param array $args
     *
     * @return mixed
     * @throws Throwable
     */
    protected function runCommand(string $name, $handler, array $options, array $args)
    {
        if (is_object($handler) && method_exists($handler, '__invoke')) {
            $fs = SFlags::new();
            $fs->addOptsByRules(GlobalOption::getAloneOptions());
            $desc = $options['desc'] ?? 'No command description message';
            $fs->setDesc($desc);

            // save to input object
            $this->input->setFs($fs);

            if (!$fs->parse($args)) {
                return 0; // render help
            }

            $result = $handler($this->input, $this->output);
        } else {
            if (!class_exists($handler)) {
                Helper::throwInvalidArgument("The console command class [$handler] not exists!");
            }

            /** @var Command $object */
            $object = new $handler($this->input, $this->output);

            if (!($object instanceof Command)) {
                Helper::throwInvalidArgument("The console command class [$handler] must instanceof the " . Command::class);
            }

            $object::setName($name);
            $object->setApp($this);
            $result = $object->run($args);
        }

        return $result;
    }

    /**
     * Execute an action in a group command(controller)
     *
     * @param array $info Matched route info
     *
     * @psalm-param array{action: string} $info Matched route info
     *
     * @param array $options
     * @param array $args
     * @param bool $detachedRun
     *
     * @return mixed
     * @throws Throwable
     */
    protected function runAction(array $info, array $options, array $args, bool $detachedRun = false)
    {
        $controller = $this->createController($info);
        $controller::setDesc($options['desc'] ?? '');

        if ($detachedRun) {
            $controller->setDetached();
        }

        if ($info['action']) {
            array_unshift($args, $info['action']);
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
     * @param array $info
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
            if (!class_exists($class)) {
                Helper::throwInvalidArgument('The console controller class [%s] not exists!', $class);
            }

            $handler = new $class($this->input, $this->output);
        }

        if (!($handler instanceof Controller)) {
            Helper::throwInvalidArgument(
                'The console controller class [%s] must instanceof the %s',
                $handler,
                Controller::class
            );
        }

        // force set name and description
        $handler::setName($group);
        $handler->setApp($this);
        $handler->setDelimiter($this->delimiter);

        // cache object
        $this->groupObjects[$group] = $handler;
        return $handler;
    }
}
