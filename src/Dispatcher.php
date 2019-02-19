<?php

namespace Inhere\Console;

use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\Contract\ControllerInterface;
use Inhere\Console\Contract\RouterInterface;

/**
 * Class Dispatcher - match input command and dispatch command handler
 * @package Inhere\Console
 */
class Dispatcher implements RouterInterface
{
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

    /**
     * Register a app group command(by controller)
     * @param string                     $name The controller name
     * @param string|ControllerInterface $class The controller class
     * @param null|array|string          $option
     * string: define the description message.
     * array:
     *  - aliases     The command aliases
     *  - description The description message
     * @return static
     * @throws \InvalidArgumentException
     */
    public function controller(string $name, $class = null, $option = null)
    {
        // TODO: Implement controller() method.
    }

    /**
     * Register a app independent console command
     * @param string|CommandInterface          $name
     * @param string|\Closure|CommandInterface $handler
     * @param null|array|string                $option
     * string: define the description message.
     * array:
     *  - aliases     The command aliases
     *  - description The description message
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function command(string $name, $handler = null, $option = null)
    {
        // TODO: Implement command() method.
    }

    /**
     * @param string $command
     * @return array
     * [
     *  status,
     *  type,
     *  route info(array)
     * ]
     */
    public function match(string $command): array
    {
        // TODO: Implement match() method.
    }
}
