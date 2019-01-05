<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-09
 * Time: 18:37
 */

namespace Inhere\Console;

use Inhere\Console\Component\ErrorHandler;
use Inhere\Console\Component\Style\Style;
use Inhere\Console\Face\ApplicationInterface;
use Inhere\Console\Face\CommandInterface;
use Inhere\Console\Face\ErrorHandlerInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputInterface;
use Inhere\Console\IO\Output;
use Inhere\Console\IO\OutputInterface;
use Inhere\Console\Traits\InputOutputAwareTrait;
use Inhere\Console\Traits\SimpleEventTrait;
use Inhere\Console\Util\FormatUtil;
use Inhere\Console\Util\Helper;
use Toolkit\PhpUtil\PhpHelper;

/**
 * Class AbstractApplication
 * @package Inhere\Console
 */
abstract class AbstractApplication implements ApplicationInterface
{
    use InputOutputAwareTrait, SimpleEventTrait;

    /** @var array */
    protected static $internalCommands = [
        'version' => 'Show application version information',
        'help'    => 'Show application help information',
        'list'    => 'List all group and alone commands',
    ];

    /** @var array */
    protected static $internalOptions = [
        '--debug'       => 'Setting the application runtime debug level(0 - 4)',
        '--profile'     => 'Display timing and memory usage information',
        '--no-color'    => 'Disable color/ANSI for message output',
        '-h, --help'    => 'Display this help message',
        '-V, --version' => 'Show application version information',
    ];

    /**
     * @var array App runtime stats info
     */
    private $stats = [
        'startTime'   => 0,
        'endTime'     => 0,
        'startMemory' => 0,
        'endMemory'   => 0,
    ];

    /**
     * @var array Application config data
     */
    private $config = [
        'name'         => 'My Console Application',
        'debug'        => Console::VERB_ERROR,
        'profile'      => false,
        'version'      => '0.5.1',
        'publishAt'    => '2017.03.24',
        'updateAt'     => '2019.01.01',
        'rootPath'     => '',
        'hideRootPath' => true,

        // 'timeZone' => 'Asia/Shanghai',
        // 'env' => 'prod', // dev test prod
        // 'charset' => 'UTF-8',

        'logoText'  => '',
        'logoStyle' => 'info',
    ];

    /**
     * @var string Command delimiter char. e.g dev:serve
     */
    public $delimiter = ':'; // '/' ':'

    /**
     * @var ErrorHandlerInterface Can custom error handler
     */
    private $errorHandler;

    /**
     * @var array Some metadata for command
     * - description
     */
    private $commandsMeta = [];

    /** @var array Save command aliases */
    private $commandAliases = [];

    /** @var array The independent commands */
    protected $commands = [];

    /** @var array The group commands(controller) */
    protected $controllers = [];

    /**
     * Class constructor.
     * @param array  $meta
     * @param Input  $input
     * @param Output $output
     * @throws \InvalidArgumentException
     */
    public function __construct(array $meta = [], Input $input = null, Output $output = null)
    {
        $this->runtimeCheck();
        $this->setConfig($meta);

        $this->input = $input ?: new Input();
        $this->output = $output ?: new Output();

        $this->init();
    }

    /**
     *
     * @throws \InvalidArgumentException
     */
    protected function init()
    {
        $this->stats = [
            'startTime'   => \microtime(1),
            'endTime'     => 0,
            'startMemory' => \memory_get_usage(),
            'endMemory'   => 0,
        ];

        $this->registerErrorHandle();
    }

    /**
     * @return array
     */
    public static function getInternalOptions(): array
    {
        return self::$internalOptions;
    }

    /**********************************************************
     * app run
     **********************************************************/

    protected function prepareRun()
    {
        if (!$this->errorHandler) {
            $this->errorHandler = new ErrorHandler();
        }

        // date_default_timezone_set($this->config('timeZone', 'UTC'));
        // new AutoCompletion(array_merge($this->getCommandNames(), $this->getControllerNames()));
    }

    protected function beforeRun()
    {
    }

    /**
     * run application
     * @param bool $exit
     * @return int|mixed
     * @throws \InvalidArgumentException
     */
    public function run(bool $exit = true)
    {
        $command = \trim($this->input->getCommand(), $this->delimiter);

        $this->prepareRun();
        $this->filterSpecialCommand($command);

        // call 'onBeforeRun' service, if it is registered.
        $this->fire(self::ON_BEFORE_RUN, $this);
        $this->beforeRun();

        // do run ...
        try {
            $result = $this->dispatch($command);
        } catch (\Throwable $e) {
            $this->fire(self::ON_RUN_ERROR, $e, $this);
            $result = $e->getCode() === 0 ? $e->getLine() : $e->getCode();
            $this->handleException($e);
        }

        $this->stats['endTime'] = \microtime(1);

        // call 'onAfterRun' service, if it is registered.
        $this->fire(self::ON_AFTER_RUN, $this);
        $this->afterRun();

        if ($exit) {
            $this->stop(\is_int($result) ? $result : 0);
        }

        return $result;
    }

    /**
     * dispatch command
     * @param string $command A command name
     * @return int|mixed
     */
    abstract protected function dispatch(string $command);

    protected function afterRun()
    {
    }

    /**
     * @param int $code
     */
    public function stop(int $code = 0)
    {
        // call 'onAppStop' event, if it is registered.
        $this->fire(self::ON_STOP_RUN, $this);

        // display runtime info
        if ($this->isProfile()) {
            $title = '------ Runtime Stats(use --profile) ------';
            $stats = $this->stats;
            $this->stats = PhpHelper::runtime($stats['startTime'], $stats['startMemory'], $stats);
            $this->output->write('');
            $this->output->aList($this->stats, $title);
        }

        exit($code);
    }

    /**
     * @param string          $command
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|mixed
     */
    public function subRun(string $command, InputInterface $input, OutputInterface $output)
    {
        $app = clone $this;
        $app->setInput($input);
        $app->setOutput($output);

        return $app->dispatch($command);
    }

    /**********************************************************
     * helper method for the application
     **********************************************************/

    /**
     * runtime env check
     */
    protected function runtimeCheck()
    {
        // check env
        if (!\in_array(\PHP_SAPI, ['cli', 'cli-server'], true)) {
            \header('HTTP/1.1 403 Forbidden');
            exit("  403 Forbidden \n\n"
                . " current environment is CLI. \n"
                . " :( Sorry! Run this script is only allowed in the terminal environment!\n,You are not allowed to access this file.\n");
        }
    }

    /**
     * register error handle
     * @throws \InvalidArgumentException
     */
    protected function registerErrorHandle()
    {
        \set_error_handler([$this, 'handleError']);
        \set_exception_handler([$this, 'handleException']);
        \register_shutdown_function(function () {
            if ($e = \error_get_last()) {
                $this->handleError($e['type'], $e['message'], $e['file'], $e['line']);
            }
        });
    }

    /**
     * 运行异常处理
     * @param int    $num
     * @param string $str
     * @param string $file
     * @param int    $line
     * @throws \InvalidArgumentException
     */
    public function handleError(int $num, string $str, string $file, int $line)
    {
        $this->handleException(new \ErrorException($str, 0, $num, $file, $line));
        $this->stop(-1);
    }

    /**
     * Running exception handling
     * @param \Throwable $e
     * @throws \InvalidArgumentException
     */
    public function handleException($e)
    {
        // you can log error on sub class ...

        $this->errorHandler->handle($e, $this);
    }

    /**
     * @param $command
     */
    protected function filterSpecialCommand(string $command)
    {
        if (!$command) {
            if ($this->input->getSameOpt(['V', 'version'])) {
                $this->showVersionInfo();
            }

            if ($this->input->getSameOpt(['h', 'help'])) {
                $this->showHelpInfo();
            }
        }

        if ($this->input->getSameOpt(['no-color'])) {
            Style::setNoColor();
        }

        $command = $command ?: 'list';

        switch ($command) {
            case 'help':
                $this->showHelpInfo(true, $this->input->getFirstArg());
                break;
            case 'list':
                $this->showCommandList();
                break;
            case 'version':
                $this->showVersionInfo();
                break;
        }
    }

    /**
     * @param      $name
     * @param bool $isGroup
     * @throws \InvalidArgumentException
     */
    protected function validateName(string $name, bool $isGroup = false)
    {
        $pattern = $isGroup ? '/^[a-z][\w-]+$/' : '/^[a-z][\w-]*:?([a-z][\w-]+)?$/';

        if (1 !== \preg_match($pattern, $name)) {
            throw new \InvalidArgumentException("The command name '$name' is must match: $pattern");
        }

        if ($this->isInternalCommand($name)) {
            throw new \InvalidArgumentException("The command name '$name' is not allowed. It is a built in command.");
        }
    }

    /***************************************************************************
     * some information for the application
     ***************************************************************************/

    /**
     * show the application help information
     * @param bool   $quit
     * @param string $command
     */
    public function showHelpInfo(bool $quit = true, string $command = '')
    {
        // display help for a special command
        if ($command) {
            $this->input->setCommand($command);
            $this->input->setSOpt('h', true);
            $this->input->clearArgs();
            $this->dispatch($command);
            $this->stop();
        }

        $script = $this->input->getScript();
        $sep = $this->delimiter;

        $this->output->helpPanel([
            'usage'   => "$script <info>{command}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
            'example' => [
                "$script test (run a independent command)",
                "$script home{$sep}index (run a command of the group)",
                "$script help {command} (see a command help information)",
                "$script home{$sep}index -h (see a command help of the group)",
            ]
        ], $quit);
    }

    /**
     * show the application version information
     * @param bool $quit
     */
    public function showVersionInfo($quit = true)
    {
        $os = \PHP_OS;
        $date = \date('Y.m.d');
        $logo = '';
        $name = $this->getConfig('name', 'Console Application');
        $version = $this->getConfig('version', 'Unknown');
        $publishAt = $this->getConfig('publishAt', 'Unknown');
        $updateAt = $this->getConfig('updateAt', 'Unknown');
        $phpVersion = \PHP_VERSION;

        if ($logoTxt = $this->getLogoText()) {
            $logo = Helper::wrapTag($logoTxt, $this->getLogoStyle());
        }

        $this->output->aList([
            "$logo\n  <info>{$name}</info>, Version <comment>$version</comment>\n",
            'System Info'      => "PHP version <info>$phpVersion</info>, on <info>$os</info> system",
            'Application Info' => "Update at <info>$updateAt</info>, publish at <info>$publishAt</info>(current $date)",
        ], null, [
            'leftChar' => '',
            'sepChar'  => ' :  '
        ]);

        $quit && $this->stop();
    }

    /**
     * show the application command list information
     * @param bool $quit
     */
    public function showCommandList($quit = true)
    {
        $script = $this->getScriptName();
        $hasGroup = $hasCommand = false;
        $controllerArr = $commandArr = [];
        $desPlaceholder = 'No description of the command';

        // all console controllers
        if ($controllers = $this->controllers) {
            $hasGroup = true;
            \ksort($controllers);
        }

        // all independent commands, Independent, Single, Alone
        if ($commands = $this->commands) {
            $hasCommand = true;
            \ksort($commands);
        }

        // add split title on both exists.
        if ($hasCommand && $hasGroup) {
            $commandArr[] = \PHP_EOL . '- <bold>Alone Commands</bold>';
            $controllerArr[] = \PHP_EOL . '- <bold>Group Commands</bold>';
        }

        foreach ($controllers as $name => $controller) {
            /** @var AbstractCommand $controller */
            $desc = $controller::getDescription() ?: $desPlaceholder;
            $aliases = $this->getCommandAliases($name);
            $extra = $aliases ? Helper::wrapTag(' [alias: ' . \implode(',', $aliases) . ']', 'info') : '';
            $controllerArr[$name] = $desc . $extra;
        }

        if (!$hasGroup && $this->isDebug()) {
            $controllerArr[] = '... Not register any group command(controller)';
        }

        foreach ($commands as $name => $command) {
            $desc = $desPlaceholder;

            /** @var AbstractCommand $command */
            if (\is_subclass_of($command, CommandInterface::class)) {
                $desc = $command::getDescription() ?: $desPlaceholder;
            } elseif ($msg = $this->getCommandMetaValue($name, 'description')) {
                $desc = $msg;
            } elseif (\is_string($command)) {
                $desc = 'A handler : ' . $command;
            } elseif (\is_object($command)) {
                $desc = 'A handler by ' . \get_class($command);
            }

            $aliases = $this->getCommandAliases($name);
            $extra = $aliases ? Helper::wrapTag(' [alias: ' . \implode(',', $aliases) . ']', 'info') : '';
            $commandArr[$name] = $desc . $extra;
        }

        if (!$hasCommand && $this->isDebug()) {
            $commandArr[] = '... Not register any alone command';
        }

        // built in commands
        $internalCommands = static::$internalCommands;
        \ksort($internalCommands);

        // built in options
        $internalOptions = FormatUtil::alignOptions(self::$internalOptions);

        $this->output->mList([
            'Usage:'              => "$script <info>{command}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
            'Options:'            => $internalOptions,
            'Internal Commands:'  => $internalCommands,
            'Available Commands:' => \array_merge($controllerArr, $commandArr),
        ], [
            'sepChar' => '  ',
        ]);

        unset($controllerArr, $commandArr, $internalCommands);
        $this->output->write("More command information, please use: <cyan>$script {command} -h</cyan>");

        $quit && $this->stop();
    }

    /**
     * @param string       $name
     * @param string|array $aliases
     * @return $this
     */
    public function addCommandAliases(string $name, $aliases): self
    {
        if (!$name || !$aliases) {
            return $this;
        }

        foreach ((array)$aliases as $alias) {
            if ($alias = trim($alias)) {
                $this->commandAliases[$alias] = $name;
            }
        }

        return $this;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getRealCommandName(string $name): string
    {
        return $this->commandAliases[$name] ?? $name;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function findCommand(string $name)
    {
        if (isset($this->commands[$name])) {
            return $this->commands[$name];
        }

        return $this->controllers[$name] ?? null;
    }

    /**********************************************************
     * getter/setter methods
     **********************************************************/

    /**
     * @return array
     */
    public function getControllerNames(): array
    {
        return \array_keys($this->controllers);
    }

    /**
     * @return array
     */
    public function getCommandNames(): array
    {
        return \array_keys($this->commands);
    }

    /**
     * @param array $controllers
     * @throws \InvalidArgumentException
     */
    public function setControllers(array $controllers)
    {
        foreach ($controllers as $name => $controller) {
            if (\is_int($name)) {
                $this->controller($controller);
            } else {
                $this->controller($name, $controller);
            }
        }
    }

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
     * @param array $commands
     * @throws \InvalidArgumentException
     */
    public function setCommands(array $commands)
    {
        foreach ($commands as $name => $handler) {
            if (\is_int($name)) {
                $this->command($handler);
            } else {
                $this->command($name, $handler);
            }
        }
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
     * @return string|null
     */
    public function getLogoText()
    {
        return $this->config['logoText'] ?? null;
    }

    /**
     * @param string      $logoTxt
     * @param string|null $style
     */
    public function setLogo(string $logoTxt, string $style = null)
    {
        $this->config['logoText'] = $logoTxt;

        if ($style) {
            $this->config['logoStyle'] = $style;
        }
    }

    /**
     * @return string|null
     */
    public function getLogoStyle()
    {
        return $this->config['logoStyle'] ?? 'info';
    }

    /**
     * @param string $style
     */
    public function setLogoStyle(string $style)
    {
        $this->config['logoStyle'] = $style;
    }

    /**
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->getConfig('rootPath', '');
    }

    /**
     * @return array
     */
    public static function getInternalCommands(): array
    {
        return static::$internalCommands;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isInternalCommand(string $name): bool
    {
        return isset(static::$internalCommands[$name]);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->config['name'];
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->config['version'];
    }

    /**
     * set meta info
     * @param array $config
     */
    public function setConfig(array $config)
    {
        if ($config) {
            $this->config = \array_merge($this->config, $config);
        }
    }

    /**
     * get meta info
     * @param null|string $name
     * @param null|string $default
     * @return array|string
     */
    public function getConfig(string $name = null, $default = null)
    {
        if (!$name) {
            return $this->config;
        }

        return $this->config[$name] ?? $default;
    }

    /**
     * get current debug level value
     * @return int
     */
    public function getVerbLevel(): int
    {
        return (int)$this->input->getLongOpt('debug', (int)$this->config['debug']);
    }

    /**
     * is profile
     * @return boolean
     */
    public function isProfile(): bool
    {
        return (bool)$this->input->getOpt('profile', $this->getConfig('profile'));
    }

    /**
     * @param null|string $name
     * @return array
     */
    public function getCommandAliases(string $name = null): array
    {
        if (!$name) {
            return $this->commandAliases;
        }

        return \array_keys($this->commandAliases, $name, true);
    }

    /**
     * @param array $commandAliases
     */
    public function setCommandAliases(array $commandAliases)
    {
        $this->commandAliases = $commandAliases;
    }

    /**
     * @return array
     */
    public function getCommandsMeta(): array
    {
        return $this->commandsMeta;
    }

    /**
     * @param string $command
     * @param array  $meta
     */
    public function setCommandMeta(string $command, array $meta)
    {
        if (isset($this->commandsMeta[$command])) {
            $this->commandsMeta[$command] = \array_merge($this->commandsMeta[$command], $meta);
        } else {
            $this->commandsMeta[$command] = $meta;
        }
    }

    /**
     * @param string $command
     * @return array
     */
    public function getCommandMeta(string $command): array
    {
        return $this->commandsMeta[$command] ?? [];
    }

    /**
     * @param string $command
     * @param string $key
     * @param        $value
     */
    public function setCommandMetaValue(string $command, string $key, $value)
    {
        if ($value !== null) {
            $this->commandsMeta[$command][$key] = $value;
        }
    }

    /**
     * @param string $command
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getCommandMetaValue(string $command, string $key, $default = null)
    {
        return $this->commandsMeta[$command][$key] ?? $default;
    }

    /**
     * @return ErrorHandlerInterface
     */
    public function getErrorHandler(): ErrorHandlerInterface
    {
        return $this->errorHandler;
    }

    /**
     * @param ErrorHandlerInterface $errorHandler
     */
    public function setErrorHandler(ErrorHandlerInterface $errorHandler): void
    {
        $this->errorHandler = $errorHandler;
    }
}
