<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-14
 * Time: 16:51
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
     * @return int|mixed
     */
    public function run(bool $exit = true);

    /**
     * Dispatch input command, exec found command handler.
     *
     * @param string $name Inputted command name. allow:
     *                            - 'command'
     *                            - 'group:action'
     *                            - 'group action'
     * @param array $args
     *
     * @return int|mixed
     */
    public function dispatch(string $name, array $args = []);

    /**
     * @param int $code
     *
     * @return mixed
     */
    public function stop(int $code = 0);

    /**
     * Register a app group command(by controller)
     *
     * @param string $name The controller name
     * @param string|ControllerInterface $class The controller class
     * @param array $config config the controller
     *                      - aliases   The command aliases
     *                      - desc      The description message
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public function controller(string $name, $class = null, array $config = []): ApplicationInterface;

    /**
     * Register a app independent console command
     *
     * @param string|CommandInterface $name
     * @param string|Closure|CommandInterface $handler
     * @param array $config config the command
     *                      - aliases     The command aliases
     *                      - desc        The description message
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function command(string $name, $handler = null, array $config = []);

    public function showCommandList();

    /**
     * @return string
     */
    public function getRootPath(): string;
}
