<?php

namespace Inhere\Console;

use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\Contract\ControllerInterface;
use Inhere\Console\Contract\RouterInterface;
use Inhere\Console\Traits\NameAliasTrait;
use Inhere\Console\Util\Helper;

/**
 * Class Router - match input command find command handler
 * @package Inhere\Console
 */
class Router implements RouterInterface
{
    use NameAliasTrait;

    /**
     * @var array
     */
    private $blocked = ['help', 'version'];

    /**
     * Command delimiter char. e.g dev:serve
     * @var string
     */
    private $delimiter = ':'; // '/' ':'

    /**
     * The independent commands
     * @var array
     * [
     *  'name' => [
     *      'handler' => MyCommand::class,
     *      'options' => []
     *  ]
     * ]
     */
    private $commands = [];

    /**
     * The group commands(controller)
     * @var array
     * [
     *  'name' => [
     *      'handler' => MyController::class,
     *      'options' => []
     *  ]
     * ]
     */
    private $controllers = [];

    /**********************************************************
     * register command/group methods
     **********************************************************/

    /**
     * Register a app group command(by controller)
     * @param string                     $name The controller name
     * @param string|ControllerInterface $class The controller class
     * @param array                      $options
     * array:
     *  - aliases     The command aliases
     *  - description The description message
     * @return Router
     * @throws \InvalidArgumentException
     */
    public function addGroup(string $name, $class = null, array $options = []): RouterInterface
    {
        /**
         * @var Controller $class name is an controller class
         */
        if (!$class && \class_exists($name)) {
            $class = $name;
            $name  = $class::getName();
        }

        if (!$name || !$class) {
            Helper::throwInvalidArgument(
                'Group-command "name" and "controller" cannot be empty! name: %s, controller: %s',
                $name,
                $class
            );
        }

        $this->validateName($name);

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

        // allow define aliases in group class by Controller::aliases()
        if ($aliases = $class::aliases()) {
            $options['aliases'] = isset($options['aliases']) ? \array_merge($options['aliases'], $aliases) : $aliases;
        }

        $this->controllers[$name] = [
            'type'    => self::TYPE_GROUP,
            'handler' => $class,
            'options' => $options,
        ];

        // has alias option
        if (isset($options['aliases'])) {
            $this->setAlias($name, $options['aliases'] ?? []);
        }

        return $this;
    }

    /**
     * Register a app independent console command
     * @param string|CommandInterface          $name
     * @param string|\Closure|CommandInterface $handler
     * @param array                            $options
     * array:
     *  - aliases     The command aliases
     *  - description The description message
     * @return Router|RouterInterface
     * @throws \InvalidArgumentException
     */
    public function addCommand(string $name, $handler = null, array $options = []): RouterInterface
    {
        /**
         * @var Command $name name is an command class
         */
        if (!$handler && \class_exists($name)) {
            $handler = $name;
            $name    = $name::getName();
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
                $options['aliases'] = isset($options['aliases']) ?
                    \array_merge($options['aliases'], $aliases) :
                    $aliases;
            }
        } elseif (!\is_object($handler) || !\method_exists($handler, '__invoke')) {
            Helper::throwInvalidArgument(
                'The console command handler must is an subclass of %s OR a Closure OR a object have method __invoke()',
                Command::class
            );
        }

        // is an class name string
        $this->commands[$name] = [
            'type'    => self::TYPE_SINGLE,
            'handler' => $handler,
            'options' => $options,
        ];

        // has alias option
        if (isset($options['aliases'])) {
            $this->setAlias($name, $options['aliases'] ?? []);
        }

        return $this;
    }

    /**
     * @param array $commands
     * @throws \InvalidArgumentException
     */
    public function addCommands(array $commands): void
    {
        foreach ($commands as $name => $handler) {
            if (\is_int($name)) {
                $this->addCommand($handler);
            } else {
                $this->addCommand($name, $handler);
            }
        }
    }

    /**
     * @param array $controllers
     * @throws \InvalidArgumentException
     */
    public function addControllers(array $controllers): void
    {
        foreach ($controllers as $name => $controller) {
            if (\is_int($name)) {
                $this->addGroup($controller);
            } else {
                $this->addGroup($name, $controller);
            }
        }
    }

    /**********************************************************
     * getter/setter methods
     **********************************************************/

    /**
     * @param string $name The input command name
     * @return array return route info array. If not found, will return empty array.
     * [
     *  type    => 1, // 1 group 2 command
     *  handler => handler class/object/func ...
     *  options => [
     *      aliases => [],
     *      description => '',
     *  ],
     * ]
     */
    public function match(string $name): array
    {
        $sep  = $this->delimiter;
        $name = \trim($name, $sep);
        // resolve alias
        $realName = $this->resolveAlias($name);

        // is a command name
        if ($route = $this->commands[$realName] ?? []) {
            return $route;
        }

        // maybe is a controller/group name
        $action = '';
        $group  = $realName;

        // like 'home:index'
        if (\strpos($realName, $sep) > 0) {
            $input = \array_values(\array_filter(\explode($sep, $realName)));

            [$group, $action] = \count($input) > 2 ? \array_splice($input, 2) : $input;
            // resolve alias
            $group = $this->resolveAlias($group);
        }

        // is group name
        if ($route = $this->controllers[$group] ?? []) {
            $route['action'] = $action;
            return $route;
        }

        // not found
        return [];
    }

    /**********************************************************
     * getter/setter methods
     **********************************************************/

    /**
     * @return array
     */
    public function getControllers(): array
    {
        return $this->controllers;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isController(string $name): bool
    {
        return isset($this->controllers[$name]);
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isCommand(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isBlocked(string $name): bool
    {
        return \in_array($name, $this->blocked, true);
    }

    /**
     * @return array
     */
    public function getBlocked(): array
    {
        return $this->blocked;
    }

    /**
     * @param array $blocked
     */
    public function setBlocked(array $blocked): void
    {
        $this->blocked = $blocked;
    }

    /**
     * @param      $name
     * @throws \InvalidArgumentException
     */
    protected function validateName(string $name): void
    {
        // '/^[a-z][\w-]*:?([a-z][\w-]+)?$/'
        $pattern = '/^[a-z][\w-:]+$/';

        if (1 !== \preg_match($pattern, $name)) {
            throw new \InvalidArgumentException("The command name '$name' is must match: $pattern");
        }

        if ($this->isBlocked($name)) {
            throw new \InvalidArgumentException("The command name '$name' is not allowed. It is a built in command.");
        }
    }

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = \trim($delimiter) ?: ':';
    }
}
