<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 17:56
 */

namespace inhere\console;

use inhere\console\base\AbstractApp;

/**
 * Class App
 * @package inhere\console
 */
class App extends AbstractApp
{

    /**********************************************************
     * register console controller/command
     **********************************************************/

    /**
     * Register a app group command(by controller)
     * @param string $name The controller name
     * @param string $controller The controller class
     * @return static
     */
    public function controller(string $name, string $controller = null)
    {
        if (!$controller && class_exists($name)) {
            /** @var Controller $controller */
            $controller = $name;
            $name = $controller::getName();
        }

        if (!$name || !$controller) {
            throw new \InvalidArgumentException('Group-command "name" and "controller" not allowed to is empty! name: '
                . $name . ', controller: ' .$controller);
        }

        $this->validateName($name, true);

        if (!class_exists($controller)) {
            throw new \InvalidArgumentException("The console controller class [$controller] not exists!");
        }

        if (!is_subclass_of($controller, Controller::class)) {
            throw new \InvalidArgumentException('The console controller class must is subclass of the: ' . Controller::class);
        }

        $this->controllers[$name] = $controller;

        return $this;
    }

    /**
     * @param array $controllers
     */
    public function controllers(array $controllers)
    {
        foreach ($controllers as $name => $controller) {
            if (is_int($name)) {
                $this->controller($controller);
            } else {
                $this->controller($name, $controller);
            }
        }
    }

    /**
     * Register a app independent console command
     * @param string $name
     * @param string|\Closure $handler
     * @param null|string $description
     * @return $this
     */
    public function command(string $name, $handler = null, $description = null)
    {
        if (!$handler && class_exists($name)) {
            /** @var Command $handler */
            $handler = $name;
            $name = $handler::getName();
        }

        if (!$name || !$handler) {
            throw new \InvalidArgumentException('Command "name" and "handler" not allowed to is empty!');
        }

        $this->validateName($name);

        if (isset($this->commands[$name])) {
            throw new \InvalidArgumentException("Command '$name' have been registered!");
        }

        if (is_string($handler)) {
            if (!class_exists($handler)) {
                throw new \InvalidArgumentException("The console command class [$handler] not exists!");
            }

            if (!is_subclass_of($handler, Command::class)) {
                throw new \InvalidArgumentException('The console command class must is subclass of the: ' . Command::class);
            }
        } elseif (!is_object($handler) || !method_exists($handler, '__invoke')) {
            throw new \InvalidArgumentException(sprintf(
                'The console command handler must is an subclass of %s OR a Closure OR a object have method __invoke()',
                Command::class
            ));
        }

        // is an class name string
        $this->commands[$name] = $handler;

        if ($description) {
            $this->addCommandMessage($name, $description);
        }

        return $this;
    }

    /**
     * @param array $commands
     */
    public function commands(array $commands)
    {
        foreach ($commands as $name => $handler) {
            if (is_int($name)) {
                $this->command($handler);
            } else {
                $this->command($name, $handler);
            }
        }
    }

    /**
     * addCommand
     * @param string $name
     * @param mixed $handler
     * @return $this
     */
    public function addCommand(string $name, $handler = null)
    {
        return $this->command($name, $handler);
    }

    /**
     * addGroup
     * @param string $name
     * @param string|null $controller
     * @return static
     */
    public function addGroup(string $name, string $controller = null)
    {
        return $this->controller($name, $controller);
    }

    /**********************************************************
     * dispatch and run console controller/command
     **********************************************************/

    /**
     * @inheritdoc
     */
    protected function dispatch($name)
    {
        $sep = $this->delimiter ?: '/';

        //// is a command name

        if ($this->isCommand($name)) {
            return $this->runCommand($name, true);
        }

        //// is a controller name

        $action = '';

        // like 'home/index'
        if (strpos($name, $sep) > 0) {
            $input = array_filter(explode($sep, $name));
            list($name, $action) = count($input) > 2 ? array_splice($input, 2) : $input;
        }

        if ($this->isController($name)) {
            return $this->runAction($name, $action, true);
        }

        if (true !== self::fire(self::ON_NOT_FOUND, [$this])) {
            $this->output->error("Console controller or command [$name] not exists!");
            $this->showCommandList(false);
        }

        return 404;
    }

    /**
     * run a command
     * @param string $name Command name
     * @param bool $believable The `$name` is believable
     * @return mixed
     */
    public function runCommand($name, $believable = false)
    {
        // if $believable = true, will skip check.
        if (!$believable && $this->isCommand($name)) {
            throw new \InvalidArgumentException("The console independent-command [$name] not exists!");
        }

        /** @var \Closure|string $handler Command class */
        $handler = $this->commands[$name];

        if (is_object($handler) && method_exists($handler, '__invoke')) {
            $status = $handler($this->input, $this->output);
        } else {
            if (!class_exists($handler)) {
                throw new \InvalidArgumentException("The console command class [$handler] not exists!");
            }

            /** @var Command $object */
            $object = new $handler($this->input, $this->output);

            if (!($object instanceof Command)) {
                throw new \InvalidArgumentException("The console command class [$handler] must instanceof the " . Command::class);
            }

            $object::setName($name);
            $status = $object->run();
        }

        return $status;
    }

    /**
     * @param string $name Controller name
     * @param string $action
     * @param bool $believable The `$name` is believable
     * @param bool $standAlone
     * @return mixed
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
        $object->delimiter = $this->delimiter;
        $object->setStandAlone($standAlone);

        return $object->setAction($action)->run();
    }

}
