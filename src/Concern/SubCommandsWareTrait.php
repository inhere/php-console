<?php declare(strict_types=1);

namespace Inhere\Console\Concern;

use Closure;
use Inhere\Console\Command;
use Inhere\Console\Console;
use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\Util\Helper;
use InvalidArgumentException;
use function array_keys;
use function array_merge;
use function class_exists;
use function in_array;
use function is_int;
use function is_object;
use function is_string;
use function is_subclass_of;
use function method_exists;
use function preg_match;

/**
 * Trait SubCommandsWareTrait
 *
 * @package Inhere\Console\Concern
 */
trait SubCommandsWareTrait
{
    use \Toolkit\Stdlib\Obj\Traits\NameAliasTrait;

    /**
     * @var array
     */
    private $blocked = ['help', 'version'];

    /**
     * The sub-commands of the command
     *
     * @var array
     * [
     *  'name' => [
     *      'handler' => MyCommand::class, // allow: string|Closure|CommandInterface
     *      'options' => []
     *  ]
     * ]
     */
    private $commands = [];

    /**
     * Can attach sub-commands
     *
     * @return array
     */
    protected function commands(): array
    {
        // [
        //  'cmd1' => function(){},
        //  MySubCommand::class,
        //  'cmd2' => MySubCommand2::class,
        //  new FooCommand,
        //  'cmd3' => new FooCommand2(),
        // ]
        return [];
    }

    /**
     * @param string $name
     */
    protected function dispatchCommand(string $name): void
    {

    }

    /**
     * Register a app independent console command
     *
     * @param string|CommandInterface              $name
     * @param string|Closure|CommandInterface|null $handler
     * @param array                                $options
     *  array:
     *  - aliases     The command aliases
     *  - description The description message
     *
     * @throws InvalidArgumentException
     */
    public function addCommand(string $name, $handler = null, array $options = []): void
    {
        if (!$handler && class_exists($name)) {
            /** @var Command $name name is an command class */
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

        $options['aliases'] = isset($options['aliases']) ? (array)$options['aliases'] : [];

        if (is_string($handler)) {
            if (!class_exists($handler)) {
                Helper::throwInvalidArgument("The command handler class [$handler] not exists!");
            }

            if (!is_subclass_of($handler, Command::class)) {
                Helper::throwInvalidArgument('The command handler class must is subclass of the: ' . Command::class);
            }

            // not enable
            /** @var Command $handler */
            if (!$handler::isEnabled()) {
                return;
            }

            // allow define aliases in Command class by Command::aliases()
            if ($aliases = $handler::aliases()) {
                $options['aliases'] = array_merge($options['aliases'], $aliases);
            }
        } elseif (!is_object($handler) || !method_exists($handler, '__invoke')) {
            Helper::throwInvalidArgument(
                'The console command handler must is an subclass of %s OR a Closure OR a object have method __invoke()',
                Command::class
            );
        }

        // is an class name string
        $this->commands[$name] = [
            'type'    => Console::CMD_SINGLE,
            'handler' => $handler,
            'options' => $options,
        ];

        // has alias option
        if (isset($options['aliases'])) {
            $this->setAlias($name, $options['aliases'], true);
        }
    }

    /**
     * @param array $commands
     *
     * @throws InvalidArgumentException
     */
    public function addCommands(array $commands): void
    {
        foreach ($commands as $name => $handler) {
            if (is_int($name)) {
                $this->addCommand($handler);
            } else {
                $this->addCommand($name, $handler);
            }
        }
    }

    /**********************************************************
     * helper methods
     **********************************************************/

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isSubCommand(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     */
    protected function validateName(string $name): void
    {
        // '/^[a-z][\w-]*:?([a-z][\w-]+)?$/'
        $pattern = '/^[a-z][\w:-]+$/';

        if (1 !== preg_match($pattern, $name)) {
            throw new InvalidArgumentException("The command name '$name' is must match: $pattern");
        }

        // cannot be override. like: help, version
        if ($this->isBlocked($name)) {
            throw new InvalidArgumentException("The command name '$name' is not allowed. It is a built in command.");
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isBlocked(string $name): bool
    {
        return in_array($name, $this->blocked, true);
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
     * @return array
     */
    public function getCommandNames(): array
    {
        return array_keys($this->commands);
    }
}
