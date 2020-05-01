<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-02-02
 * Time: 23:52
 */

namespace Inhere\Console\Contract;

use Closure;
use InvalidArgumentException;

/**
 * Interface RouterInterface
 *
 * @package Inhere\Console\Contract
 */
interface RouterInterface
{
    public const FOUND     = 1;

    public const NOT_FOUND = 2;

    public const TYPE_GROUP  = 1;

    public const TYPE_SINGLE = 2;

    /**
     * Register a app group command(by controller)
     *
     * @param string                     $name    The controller name
     * @param string|ControllerInterface $class   The controller class
     * @param array                      $options array:
     *                                            - aliases     The command aliases
     *                                            - description The description message
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public function addGroup(string $name, $class = null, array $options = []): self;

    /**
     * Register a app independent console command
     *
     * @param string|CommandInterface         $name
     * @param string|Closure|CommandInterface $handler
     * @param array                           $options
     *  array:
     *  - aliases     The command aliases
     *  - description The description message
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public function addCommand(string $name, $handler = null, array $options = []): self;

    /**
     * @param string $name The input command name
     *
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
    public function match(string $name): array;
}
