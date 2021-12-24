<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Contract;

use Closure;

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
     * @param string|class-string  $name    The controller name
     * @param string|ControllerInterface|null $class   The controller class
     * @param array{aliases: array, desc: string} $config The options config.
     *
     * @return static
     */
    public function addGroup(string $name, ControllerInterface|string $class = null, array $config = []): static;

    /**
     * Register a app independent console command
     *
     * @param string|class-string        $name
     * @param string|CommandInterface|null|Closure():void $handler
     * @param array{aliases: array, desc: string, options: array, arguments: array} $config The config.
     *
     * @return static
     */
    public function addCommand(string $name, string|Closure|CommandInterface $handler = null, array $config = []): static;

    /**
     * ```php
     * return  [
     *  type    => 1, // 1 group 2 command
     *  handler => handler class/object/func ...
     *  config => [
     *      desc => '',
     *      aliases => [],
     *  ],
     * ]
     * ```
     *
     * @param string $name The input command name
     *
     * @return array{name:string, cmdId: string, config: array, handler: mixed} return route info. If not found, will return empty array.
     */
    public function match(string $name): array;
}
