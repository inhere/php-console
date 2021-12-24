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
 * Interface ApplicationInterface
 *
 * @package Inhere\Console\Contract
 */
interface ApplicationInterface
{
    // event name list
    public const ON_BEFORE_RUN = 'app.beforeRun';

    public const ON_AFTER_RUN = 'app.afterRun';

    public const ON_RUN_ERROR = 'app.runError';

    public const ON_STOP_RUN = 'app.stopRun';

    public const ON_NOT_FOUND = 'app.notFound';

    /**
     * @param bool $exit
     *
     * @return mixed
     */
    public function run(bool $exit = true): mixed;

    /**
     * Dispatch input command, exec found command handler.
     *
     * @param string $name Inputted command name. allow:
     *                            - 'command'
     *                            - 'group:action'
     *                            - 'group action'
     * @param array $args
     *
     * @return mixed
     */
    public function dispatch(string $name, array $args = []): mixed;

    /**
     * @param int $code
     */
    public function stop(int $code = 0): void;

    /**
     * Register a app group command(by controller)
     *
     * @param string $name The controller name
     * @param class-string|ControllerInterface|null $class The controller class
     * @param array{desc: string, aliases: array} $config config the controller.
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public function controller(string $name, ControllerInterface|string $class = null, array $config = []): static;

    /**
     * Register a app independent console command
     *
     * @param string $name
     * @param string|CommandInterface|null|Closure():void $handler
     * @param array{desc: string, aliases: array} $config config the command.
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function command(string $name, string|Closure|CommandInterface $handler = null, array $config = []): static;

    public function showCommandList();

    /**
     * @return string
     */
    public function getRootPath(): string;
}
