<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 17:56
 */

namespace Inhere\Console;

use Inhere\Console\Util\Helper;

/**
 * Class App
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
        /** @var Controller $class */
        if (!$class && \class_exists($name)) {
            $class = $name;
            $name = $class::getName();
        }

        if (!$name || !$class) {
            Helper::throwInvalidArgument(
                'Group-command "name" and "controller" cannot be empty! name: %s, controller: %s',
                $name,
                $class
            );
        }

        $this->validateName($name, true);

        if (\is_string($class) && !\class_exists($class)) {
            Helper::throwInvalidArgument("The console controller class [$class] not exists!");
        }

        if (!\is_subclass_of($class, Controller::class)) {
            Helper::throwInvalidArgument('The console controller class must is subclass of the: ' . Controller::class);
        }

        // not enable
        if (!$class::isEnabled()) {
            return $this;
        }

        // allow define aliases in Command class by Controller::aliases()
        if ($aliases = $class::aliases()) {
            $option['aliases'] = isset($option['aliases']) ? \array_merge($option['aliases'], $aliases) : $aliases;
        }

        $this->controllers[$name] = $class;

        // has option information
        if ($option) {
            if (\is_string($option)) {
                $this->setCommandMetaValue($name, 'description', $option);
            } elseif (\is_array($option)) {
                $this->addCommandAliases($name, $option['aliases'] ?? null);
                unset($option['aliases']);
                $this->setCommandMeta($name, $option);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     * @see Application::controller()
     * @throws \InvalidArgumentException
     */
    public function addController(string $name, $class = null, $option = null)
    {
        return $this->controller($name, $class, $option);
    }

    /**
     * @param array $controllers
     * @throws \InvalidArgumentException
     */
    public function controllers(array $controllers)
    {
        $this->setControllers($controllers);
    }

    /**
     * {@inheritdoc}
     */
    public function command(string $name, $handler = null, $option = null)
    {
        /** @var Command $name */
        if (!$handler && \class_exists($name)) {
            $handler = $name;
            $name = $name::getName();
        }

        if (!$name || !$handler) {
            Helper::throwInvalidArgument("Command 'name' and 'handler' cannot be empty! name: $name");
        }

        $this->validateName($name);

        if (isset($this->commands[$name])) {
            Helper::throwInvalidArgument("Command '$name' have been registered!");
        }

        if (\is_string($handler)) {
            if (!\class_exists($handler)) {
                Helper::throwInvalidArgument("The console command class [$handler] not exists!");
            }

            if (!\is_subclass_of($handler, Command::class)) {
                Helper::throwInvalidArgument('The console command class must is subclass of the: ' . Command::class);
            }

            // not enable
            /** @var Command $handler */
            if (!$handler::isEnabled()) {
                return $this;
            }

            // allow define aliases in Command class by Command::aliases()
            if ($aliases = $handler::aliases()) {
                $option['aliases'] = isset($option['aliases']) ? \array_merge($option['aliases'], $aliases) : $aliases;
            }
        } elseif (!\is_object($handler) || !\method_exists($handler, '__invoke')) {
            Helper::throwInvalidArgument(
                'The console command handler must is an subclass of %s OR a Closure OR a object have method __invoke()',
                Command::class
            );
        }

        // is an class name string
        $this->commands[$name] = $handler;

        // have option information
        if ($option) {
            if (\is_string($option)) {
                $this->setCommandMetaValue($name, 'description', $option);
            } elseif (\is_array($option)) {
                $this->addCommandAliases($name, $option['aliases'] ?? null);
                unset($option['aliases']);
                $this->setCommandMeta($name, $option);
            }
        }

        return $this;
    }

    /**
     * @param array $commands
     * @throws \InvalidArgumentException
     */
    public function commands(array $commands)
    {
        $this->setCommands($commands);
    }

    /**
     * add command
     * @inheritdoc
     * @see command()
     */
    public function addCommand(string $name, $handler = null, $option = null): self
    {
        return $this->command($name, $handler, $option);
    }

    /**
     * add group/controller
     * @inheritdoc
     * @see controller()
     */
    public function addGroup(string $name, $controller = null, $option = null)
    {
        return $this->controller($name, $controller, $option);
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
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function registerCommands(string $namespace, string $basePath): self
    {
        $length = \strlen($basePath) + 1;
        $iterator = Helper::directoryIterator($basePath, $this->getFileFilter());

        foreach ($iterator as $file) {
            $class = $namespace . '\\' . \substr($file, $length, -4);
            $this->addCommand($class);
        }

        return $this;
    }

    /**
     * auto register controllers from a dir.
     * @param string $namespace
     * @param string $basePath
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function registerGroups(string $namespace, string $basePath): self
    {
        $length = \strlen($basePath) + 1;
        $iterator = Helper::directoryIterator($basePath, $this->getFileFilter());

        foreach ($iterator as $file) {
            $class = $namespace . '\\' . \substr($file, $length, -4);
            $this->addController($class);
        }

        return $this;
    }

    /**
     * @return \Closure
     */
    protected function getFileFilter(): callable
    {
        return function (\SplFileInfo $f) {
            $name = $f->getFilename();

            // Skip hidden files and directories.
            if (\strpos($name, '.') === 0) {
                return false;
            }

            // go on read sub-dir
            if ($f->isDir()) {
                return true;
            }

            // php file
            return $f->isFile() && \substr($name, -4) === '.php';
        };
    }

    /****************************************************************************
     * dispatch and run console controller/command
     ****************************************************************************/

    /**
     * @inheritdoc
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     */
    protected function dispatch(string $name)
    {
        $sep = $this->delimiter ?: ':';

        // maybe is a command name
        $realName = $this->getRealCommandName($name);

        if ($this->isCommand($realName)) {
            return $this->runCommand($realName, true);
        }

        // maybe is a controller/group name
        $action = '';

        // like 'home:index'
        if (\strpos($realName, $sep) > 0) {
            $input = \array_values(\array_filter(\explode($sep, $realName)));
            list($realName, $action) = \count($input) > 2 ? \array_splice($input, 2) : $input;
            $realName = $this->getRealCommandName($realName);
        }

        if ($this->isController($realName)) {
            return $this->runAction($realName, $action, true);
        }

        // command not found
        if (true !== $this->fire(self::ON_NOT_FOUND, $this)) {
            $this->output->liteError("The command '{$name}' is not exists in the console application!");

            $commands = \array_merge($this->getControllerNames(), $this->getCommandNames());

            // find similar command names by similar_text()
            if ($similar = Helper::findSimilar($name, $commands)) {
                $this->write(\sprintf("\nMaybe what you mean is:\n    <info>%s</info>", implode(', ', $similar)));
            } else {
                $this->showCommandList(false);
            }
        }

        return 2;
    }

    /**
     * run a command
     * @param string $name Command name
     * @param bool   $believable The `$name` is believable
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function runCommand(string $name, bool $believable = false)
    {
        // if $believable = true, will skip check.
        if (!$believable && $this->isCommand($name)) {
            Helper::throwInvalidArgument("The console independent-command [$name] not exists!");
        }

        /** @var \Closure|string $handler Command class */
        $handler = $this->commands[$name];

        if (\is_object($handler) && \method_exists($handler, '__invoke')) {
            if ($this->input->getSameOpt(['h', 'help'])) {
                $des = $this->getCommandMetaValue($name, 'description', 'No command description message.');

                return $this->output->write($des);
            }

            $result = $handler($this->input, $this->output);
        } else {
            if (!\class_exists($handler)) {
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
     * exec an action in a group command(controller)
     * @param string $name group name
     * @param string $action Command method, no suffix
     * @param bool   $believable The `$name` is believable
     * @param bool   $standAlone
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     */
    public function runAction(string $name, string $action, bool $believable = false, bool $standAlone = false)
    {
        // if $believable = true, will skip check.
        if (!$believable && !$this->isController($name)) {
            Helper::throwInvalidArgument('The console controller-command [%s] not exists!', $name);
        }

        // Controller class
        $object = $this->controllers[$name];

        if (\is_string($object)) {
            $class = $object;

            if (!\class_exists($class)) {
                Helper::throwInvalidArgument('The console controller class [%s] not exists!', $class);
            }

            /** @var Controller $object */
            $object = new $class($this->input, $this->output);
        }

        if (!($object instanceof Controller)) {
            Helper::throwInvalidArgument(
                'The console controller class [%s] must instanceof the %s',
                $object,
                Controller::class
            );
        }

        $object::setName($name);
        $object->setApp($this);
        $object->setDelimiter($this->delimiter);
        $object->setExecutionAlone($standAlone);

        return $object->run($action);
    }
}
