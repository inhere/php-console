<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-02-02
 * Time: 23:52
 */

namespace Inhere\Console\Face;

/**
 * Interface RouterInterface
 * @package Inhere\Console\Face
 */
interface RouterInterface
{
    public const FOUND     = 1;
    public const NOT_FOUND = 2;

    public const TYPE_GROUP  = 1;
    public const TYPE_SINGLE = 1;

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
    public function controller(string $name, $class = null, $option = null);

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
    public function command(string $name, $handler = null, $option = null);

    /**
     * @param string $command
     * @return array
     * [
     *  status,
     *  type,
     *  route info(array)
     * ]
     */
    public function match(string $command): array;
}
