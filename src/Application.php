<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 17:56
 */

namespace Inhere\Console;

use Inhere\Console\Base\AbstractApplication;
use Inhere\Console\Utils\Helper;

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
     * Register a app group command(by controller)
     * @param string $name The controller name
     * @param string $class The controller class
     * @param null|array|string $option
     * @return static
     * @throws \InvalidArgumentException
     */
    public function controller(string $name, string $class = null, $option = null)
    {
        if (!$class && class_exists($name)) {
            /** @var Controller $class */
            $class = $name;
            $name = $class::getName();
        }

        if (!$name || !$class) {
            throw new \InvalidArgumentException(
                'Group-command "name" and "controller" not allowed to is empty! name: ' . $name . ', controller: ' . $class
            );
        }

        $this->validateName($name, true);

        if (!class_exists($class)) {
            throw new \InvalidArgumentException("The console controller class [$class] not exists!");
        }

        if (!is_subclass_of($class, Controller::class)) {
            throw new \InvalidArgumentException('The console controller class must is subclass of the: ' . Controller::class);
        }

        // not enable
        if (!$class::isEnabled()) {
            return $this;
        }

        // allow define aliases in Command class by Controller::aliases()
        if ($aliases = $class::aliases()) {
            $option['aliases'] = isset($option['aliases']) ? array_merge($option['aliases'], $aliases) : $aliases;
        }

        $this->controllers[$name] = $class;

        if (!$option) {
            return $this;
        }

        // have option information
        if (\is_string($option)) {
            $this->addCommandMessage($name, $option);
        } elseif (\is_array($option)) {
            $this->addCommandAliases($name, $option['aliases'] ?? null);
            $this->addCommandMessage($name, $option['description'] ?? null);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     * @see Application::controller()
     * @throws \InvalidArgumentException
     */
    public function addController(string $name, string $class = null, $option = null)
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
     * Register a app independent console command
     * @param string|Command $name
     * @param string|\Closure|Command $handler
     * @param null|array|string $option
     * @return $this|mixed
     * @throws \InvalidArgumentException
     */
    public function command(string $name, $handler = null, $option = null)
    {
        if (!$handler && class_exists($name)) {
            /** @var Command $name */
            $handler = $name;
            $name = $name::getName();
        }

        if (!$name || !$handler) {
            throw new \InvalidArgumentException("Command 'name' and 'handler' not allowed to is empty! name: $name");
        }

        $this->validateName($name);

        if (isset($this->commands[$name])) {
            throw new \InvalidArgumentException("Command '$name' have been registered!");
        }

        if (\is_string($handler)) {
            if (!class_exists($handler)) {
                throw new \InvalidArgumentException("The console command class [$handler] not exists!");
            }

            if (!is_subclass_of($handler, Command::class)) {
                throw new \InvalidArgumentException('The console command class must is subclass of the: ' . Command::class);
            }

            // not enable
            /** @var Command $handler */
            if (!$handler::isEnabled()) {
                return $this;
            }

            // allow define aliases in Command class by Command::aliases()
            if ($aliases = $handler::aliases()) {
                $option['aliases'] = isset($option['aliases']) ? array_merge($option['aliases'], $aliases) : $aliases;
            }
        } elseif (!\is_object($handler) || !method_exists($handler, '__invoke')) {
            throw new \InvalidArgumentException(sprintf(
                'The console command handler must is an subclass of %s OR a Closure OR a object have method __invoke()',
                Command::class
            ));
        }

        // is an class name string
        $this->commands[$name] = $handler;

        if (!$option) {
            return $this;
        }

        // have option information
        if (\is_string($option)) {
            $this->setCommandMetaValue($name, 'description', $option);
        } elseif (\is_array($option)) {
            $this->addCommandAliases($name, $option['aliases'] ?? null);
            $this->setCommandMeta($name, $option);
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
     * addCommand
     * @param string $name
     * @param mixed $handler
     * @param null|string|array $option
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addCommand(string $name, $handler = null, $option = null): self
    {
        return $this->command($name, $handler, $option);
    }

    /**
     * addGroup
     * @param string $name
     * @param string|null $controller
     * @param null|string|array $option
     * @return static
     * @throws \InvalidArgumentException
     */
    public function addGroup(string $name, string $controller = null, $option = null)
    {
        return $this->controller($name, $controller, $option);
    }

    /**
     * auto register commands from a dir.
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
            if ($name[0] === '.') {
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

        // maybe is a controller name
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
        if (true !== $this->fire(self::ON_NOT_FOUND, [$this])) {
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
     * @param bool $believable The `$name` is believable
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function runCommand($name, $believable = false)
    {
        // if $believable = true, will skip check.
        if (!$believable && $this->isCommand($name)) {
            throw new \InvalidArgumentException("The console independent-command [$name] not exists!");
        }

        /** @var \Closure|string $handler Command class */
        $handler = $this->commands[$name];

        if (\is_object($handler) && \method_exists($handler, '__invoke')) {
            if ($this->input->getSameOpt(['h', 'help'])) {
                $des = $this->getCommandMetaValue($name, 'description', 'No command description message.');

                return $this->output->write($des);
            }

            $status = $handler($this->input, $this->output);
        } else {
            if (!\class_exists($handler)) {
                throw new \InvalidArgumentException("The console command class [$handler] not exists!");
            }

            /** @var Command $object */
            $object = new $handler($this->input, $this->output);

            if (!($object instanceof Command)) {
                throw new \InvalidArgumentException("The console command class [$handler] must instanceof the " . Command::class);
            }

            $object::setName($name);
            $object->setApp($this);
            $status = $object->run();
        }

        return $status;
    }

    /**
     * @param string $name group name
     * @param string $action Command method, no suffix
     * @param bool $believable The `$name` is believable
     * @param bool $standAlone
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     */
    public function runAction($name, $action, $believable = false, $standAlone = false)
    {
        // if $believable = true, will skip check.
        if (!$believable && !$this->isController($name)) {
            throw new \InvalidArgumentException("The console controller-command [$name] not exists!");
        }

        // Controller class
        $controller = $this->controllers[$name];

        if (!class_exists($controller)) {
            throw new \InvalidArgumentException("The console controller class [$controller] not exists!");
        }

        /** @var Controller $object */
        $object = new $controller($this->input, $this->output);

        if (!($object instanceof Controller)) {
            throw new \InvalidArgumentException("The console controller class [$object] must instanceof the " . Controller::class);
        }

        $object::setName($name);
        $object->setApp($this);
        $object->setDelimiter($this->delimiter);
        $object->setExecutionAlone($standAlone);

        return $object->run($action);
    }
}
