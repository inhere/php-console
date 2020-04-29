<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 17:56
 */

namespace Inhere\Console;

use Closure;
use Inhere\Console\Contract\ControllerInterface;
use Inhere\Console\Util\Helper;
use InvalidArgumentException;
use ReflectionException;
use SplFileInfo;
use function class_exists;
use function implode;
use function is_object;
use function is_string;
use function method_exists;
use function sprintf;
use function strlen;
use function strpos;
use function substr;

/**
 * Class App
 *
 * @package Inhere\Console
 */
class Application extends AbstractApplication
{
    /****************************************************************************
     * register console controller/command
     ****************************************************************************/

    /**
     * {@inheritdoc}
     */
    public function controller(string $name, $class = null, $option = null)
    {
        if (is_string($option)) {
            $option = [
                'description' => $option,
            ];
        }

        $this->getRouter()->addGroup($name, $class, (array)$option);

        return $this;
    }

    /**
     * Add group/controller
     *
     * @param string $name
     * @param string|ControllerInterface $class The controller class
     * @param null|array|string          $option
     *
     * @return Application|Contract\ApplicationInterface
     * @see controller()
     */
    public function addGroup(string $name, $class = null, $option = null)
    {
        return $this->controller($name, $class, $option);
    }

    /**
     * @param string $name
     * @param string|ControllerInterface $class The controller class
     * @param null|array|string          $option
     *
     * @return Application|Contract\ApplicationInterface
     * @see controller()
     */
    public function addController(string $name, $class = null, $option = null)
    {
        return $this->controller($name, $class, $option);
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
        $this->getRouter()->addControllers($controllers);
    }

    /**
     * {@inheritdoc}
     */
    public function command(string $name, $handler = null, $option = null)
    {
        if (is_string($option)) {
            $option = [
                'description' => $option,
            ];
        }

        $this->getRouter()->addCommand($name, $handler, (array)$option);

        return $this;
    }

    /**
     * add command
     *
     * @param string $name
     * @param null   $handler
     * @param null   $option
     *
     * @return Application
     * @see command()
     */
    public function addCommand(string $name, $handler = null, $option = null): self
    {
        return $this->command($name, $handler, $option);
    }

    /**
     * @param array $commands
     *
     * @throws InvalidArgumentException
     */
    public function addCommands(array $commands): void
    {
        $this->getRouter()->addCommands($commands);
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
            $class = $namespace . '\\' . substr($file, $length, -4);
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
            $class = $namespace . '\\' . substr($file, $length, -4);
            $this->addController($class);
        }

        return $this;
    }

    /**
     * @return Closure
     */
    protected function getFileFilter(): callable
    {
        return function (SplFileInfo $f) {
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
     * @inheritdoc
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function dispatch(string $name, bool $standAlone = false)
    {
        $this->logf(Console::VERB_DEBUG, 'begin dispatch command - %s', $name);

        // match handler by input name
        $info = $this->getRouter()->match($name);

        // command not found
        if (!$info && true !== $this->fire(self::ON_NOT_FOUND, $this)) {
            $this->output->liteError("The command '{$name}' is not exists in the console application!");

            $commands = $this->getRouter()->getAllNames();

            // find similar command names by similar_text()
            if ($similar = Helper::findSimilar($name, $commands)) {
                $this->write(sprintf("\nMaybe what you mean is:\n    <info>%s</info>", implode(', ', $similar)));
            } else {
                $this->showCommandList();
            }

            return 2;
        }

        // save command ID
        $this->input->setCommandId($info['cmdId']);

        // is command
        if ($info['type'] === Router::TYPE_SINGLE) {
            return $this->runCommand($info['name'], $info['handler'], $info['options']);
        }

        // is controller/group
        return $this->runAction($info['group'], $info['action'], $info['handler'], $info['options'], $standAlone);
    }

    /**
     * run a independent command
     *
     * @param string         $name    Command name
     * @param Closure|string $handler Command class
     * @param array          $options
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function runCommand(string $name, $handler, array $options)
    {
        if (is_object($handler) && method_exists($handler, '__invoke')) {
            if ($this->input->getSameOpt(['h', 'help'])) {
                $desc = $options['description'] ?? 'No command description message';

                return $this->output->write($desc);
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
            $result = $object->run();
        }

        return $result;
    }

    /**
     * Execute an action in a group command(controller)
     *
     * @param string $group   The group name
     * @param string $action  Command method, no suffix
     * @param mixed  $handler The controller class or object
     * @param array  $options
     * @param bool   $standAlone
     *
     * @return mixed
     * @throws ReflectionException
     */
    protected function runAction(string $group, string $action, $handler, array $options, bool $standAlone = false)
    {
        /** @var Controller $handler */
        if (is_string($handler)) {
            $class = $handler;

            if (!class_exists($class)) {
                Helper::throwInvalidArgument('The console controller class [%s] not exists!', $class);
            }

            $handler = new $class($this->input, $this->output);
        }

        if (!($handler instanceof Controller)) {
            Helper::throwInvalidArgument('The console controller class [%s] must instanceof the %s', $handler,
                Controller::class);
        }

        $handler::setName($group);

        if ($desc = $options['description'] ?? '') {
            $handler::setDescription($desc);
        }

        $handler->setApp($this);
        $handler->setDelimiter($this->delimiter);
        $handler->setExecutionAlone($standAlone);

        return $handler->run($action);
    }
}
