<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Decorate;

use Closure;
use Inhere\Console\Command;
use Inhere\Console\Console;
use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\Handler\AbstractHandler;
use Inhere\Console\Handler\CommandWrapper;
use Inhere\Console\Util\ConsoleUtil;
use Inhere\Console\Util\Helper;
use InvalidArgumentException;
use Toolkit\Stdlib\Helper\Assert;
use Toolkit\Stdlib\Obj\Traits\NameAliasTrait;
use function array_keys;
use function array_merge;
use function class_exists;
use function in_array;
use function is_int;
use function is_object;
use function is_string;
use function is_subclass_of;
use function preg_match;

/**
 * Trait SubCommandsWareTrait
 *
 * @package Inhere\Console\Decorate
 */
trait SubCommandsWareTrait
{
    use NameAliasTrait;

    /**
     * @var AbstractHandler|null
     */
    protected ?AbstractHandler $parent = null;

    /**
     * @var array
     */
    private array $blocked = ['help', 'version'];

    /**
     * The sub-commands of the command
     *
     * ```php
     * [
     *  'name' => [
     *      'handler' => MyCommand::class,
     *      'config' => [
     *          'name'    => 'string',
     *          'desc'    => 'string',
     *          'options' => [],
     *          'arguments' => [],
     *      ]
     *  ]
     * ]
     * ```
     *
     * @var array<string, array{handler:mixed, config:array}>
     */
    private array $commands = [];

    /**
     * Can attach sub-commands to current command
     *
     * @return array
     */
    protected function subCommands(): array
    {
        // [
        //  'cmd1' => function(){},
        //  // class name
        //  MySubCommand::class,
        //  'cmd2' => MySubCommand2::class,
        //  // no key
        //  new FooCommand(),
        //  'cmd3' => new FooCommand2(),
        // ]
        return [];
    }

    /**
     * @param string $name
     * @param array $args
     *
     * @return mixed
     */
    protected function dispatchSub(string $name, array $args): mixed
    {
        $subInfo = $this->commands[$name];
        $this->debugf('dispatch the attached subcommand: %s', $name);

        // create and init sub-command
        $subCmd = $this->createSubCommand($subInfo);
        $subCmd->setParent($this);
        $subCmd->setInputOutput($this->input, $this->output);

        return $subCmd->run($args);
    }

    /**
     * @param array{name: string, desc: string, options: array, arguments: array} $subInfo
     *
     * @return Command
     */
    protected function createSubCommand(array $subInfo): Command
    {
        $handler = $subInfo['handler'];
        if (is_object($handler)) {
            if ($handler instanceof Command) {
                return $handler;
            }

            return CommandWrapper::wrap($handler, $subInfo['config']);
        }

        // class-string of Command
        return new $handler;
    }

    /**
     * Register a app independent console command
     *
     * @param string|class-string  $name
     * @param class-string|CommandInterface|null $handler
     * @param array $config
     */
    public function addSub(string $name, string|CommandInterface $handler = null, array $config = []): void
    {
        if (!$handler && class_exists($name)) {
            /** @var Command $name name is an command class */
            $handler = $name;
            $name    = $name::getName();
        } elseif (!$name && $handler instanceof Command) {
            $name = $handler->getRealName();
        } elseif (!$name && class_exists($handler)) {
            $name = $handler::getName();
        }

        Assert::isFalse(!$name || !$handler, "Command 'name' and 'handler' cannot be empty! name: $name");
        Assert::isFalse(isset($this->commands[$name]), "Command '$name' have been registered!");

        $this->validateName($name);

        $config['aliases'] = isset($config['aliases']) ? (array)$config['aliases'] : [];

        if (is_string($handler)) {
            Assert::isTrue(class_exists($handler), "The console command class '$handler' not exists!");

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
                $config['aliases'] = array_merge($config['aliases'], $aliases);
            }
        } elseif (!is_object($handler) || !$handler instanceof Command) {
            Helper::throwInvalidArgument(
                'The subcommand handler must be an subclass of %s OR a sub-object of %s',
                Command::class,
                Command::class,
            );
        }

        // has alias option
        if ($config['aliases']) {
            $this->setAlias($name, $config['aliases'], true);
        }

        $config['name'] = $name;
        // save
        $this->commands[$name] = [
            'type'    => Console::CMD_SINGLE,
            'handler' => $handler,
            'config'  => $config,
        ];
    }

    /**
     * @param CommandInterface $handler
     *
     * @return $this
     */
    public function addSubHandler(CommandInterface $handler): static
    {
        $name = $handler->getRealName();

        // is an class name string
        $this->commands[$name] = [
            'type'    => Console::CMD_SINGLE,
            'handler' => $handler,
            'config'  => [],
        ];

        return $this;
    }

    /**
     * @param array $commands
     */
    public function addCommands(array $commands): void
    {
        foreach ($commands as $name => $handler) {
            if (is_int($name)) {
                $this->addSub('', $handler);
            } else {
                $this->addSub($name, $handler);
            }
        }
    }

    /**
     * @param AbstractHandler $parent
     */
    public function setParent(AbstractHandler $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return $this
     */
    public function getRoot(): static
    {
        if ($this->parent) {
            return $this->parent->getRoot();
        }

        return $this;
    }

    /**
     * @return static|null
     */
    public function getParent(): ?static
    {
        return $this->parent;
    }

    /**********************************************************
     * helper methods
     **********************************************************/

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isSub(string $name): bool
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
    public function getSubNames(): array
    {
        return array_keys($this->commands);
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return array
     */
    public function getSubsForHelp(): array
    {
        $subs = [];
        foreach ($this->commands as $name => $subInfo) {
            $sub = $subInfo['handler'];
            if ($sub instanceof Command) {
                $subs[$name] = $sub->getRealDesc();
            } elseif (is_string($sub)) {
                $subs[$name] = $sub::getDesc();
            } else {
                $subConf = $subInfo['config'];

                $subs[$name] = $subConf['desc'] ?? 'no description';
            }
        }

        return $subs;
    }
}
