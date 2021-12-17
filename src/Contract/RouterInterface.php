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
     * @param string|class-string  $name    The controller name
     * @param string|ControllerInterface|null $class   The controller class
     * @param array{aliases: array, desc: string} $config The options config.
     *                                            - aliases The command aliases
     *                                            - desc    The description message
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public function addGroup(string $name, ControllerInterface|string $class = null, array $config = []): static;

    /**
     * Register a app independent console command
     *
     * @param string|class-string        $name
     * @param string|Closure|CommandInterface|null $handler
     * @param array{aliases: array, desc: string} $config The options config.
     *                                            - aliases The command aliases
     *                                            - desc    The description message
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public function addCommand(string $name, string|Closure|CommandInterface $handler = null, array $config = []): static;

    /**
     * ```php
     * return  [
     *  type    => 1, // 1 group 2 command
     *  handler => handler class/object/func ...
     *  options => [
     *      aliases => [],
     *      description => '',
     *  ],
     * ]
     * ```
     *
     * @param string $name The input command name
     *
     * @return array return route info array. If not found, will return empty array.
     */
    public function match(string $name): array;
}
