<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-09
 * Time: 18:37
 */

namespace Inhere\Console\Base;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputInterface;
use Inhere\Console\IO\Output;
use Inhere\Console\IO\OutputInterface;
use Inhere\Console\Traits\InputOutputAwareTrait;
use Inhere\Console\Traits\SimpleEventTrait;
use Inhere\Console\Components\Style\Style;
use Inhere\Console\Utils\FormatUtil;
use Inhere\Console\Utils\Helper;
use Toolkit\Cli\Highlighter;

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
        'help' => 'Show application help information',
        'list' => 'List all group and alone commands',
    ];

    /** @var array */
    protected static $internalOptions = [
        '--debug' => 'Setting the application runtime debug level',
        '--profile' => 'Display timing and memory usage information',
        '--no-color' => 'Disable color/ANSI for message output',
        '-h, --help' => 'Display this help message',
        '-V, --version' => 'Show application version information',
    ];

    /**
     * application meta info
     * @var array
     */
    private $meta = [
        'name' => 'My Console Application',
        'debug' => false,
        'profile' => false,
        'version' => '0.5.1',
        'publishAt' => '2017.03.24',
        'updateAt' => '2017.03.24',
        'rootPath' => '',
        'hideRootPath' => true,
        // 'timeZone' => 'Asia/Shanghai',
        // 'env' => 'pdt', // dev test pdt
        // 'charset' => 'UTF-8',

        'logoText' => '',
        'logoStyle' => 'info',

        // runtime stats
        '_stats' => [],
    ];

    /** @var string Command delimiter. e.g dev:serve */
    public $delimiter = ':'; // '/' ':'

    /** @var string Current command name */
    private $commandName;

    /** @var array Some metadata for command */
    private $commandsMeta = [];

    /** @var array Some message for command */
    private $commandMessages = [];

    /** @var array Save command aliases */
    private $commandAliases = [];

    /** @var array The independent commands */
    protected $commands = [];

    /** @var array The group commands */
    protected $controllers = [];

    /**
     * App constructor.
     * @param array $meta
     * @param Input $input
     * @param Output $output
     * @throws \InvalidArgumentException
     */
    public function __construct(array $meta = [], Input $input = null, Output $output = null)
    {
        $this->runtimeCheck();
        $this->setMeta($meta);

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
        $this->meta['_stats'] = [
            'startTime' => microtime(1),
            'endTime' => 0,
            'startMemory' => memory_get_usage(),
            'endMemory' => 0,
        ];

        $this->commandName = $this->input->getCommand();

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
        // date_default_timezone_set($this->config('timeZone', 'UTC'));
        //new AutoCompletion(array_merge($this->getCommandNames(), $this->getControllerNames()));
    }

    protected function beforeRun()
    {
    }

    /**
     * run app
     * @param bool $exit
     * @throws \InvalidArgumentException
     */
    public function run($exit = true)
    {
        $command = trim($this->input->getCommand(), $this->delimiter);

        $this->prepareRun();
        $this->filterSpecialCommand($command);

        // call 'onBeforeRun' service, if it is registered.
        $this->fire(self::ON_BEFORE_RUN, [$this]);
        $this->beforeRun();

        // do run ...
        try {
            $returnCode = $this->dispatch($command);
        } catch (\Throwable $e) {
            $this->fire(self::ON_RUN_ERROR, [$e, $this]);
            $returnCode = $e->getCode() === 0 ? $e->getLine() : $e->getCode();
            $this->handleException($e);
        }

        $this->meta['_stats']['endTime'] = microtime(1);

        // call 'onAfterRun' service, if it is registered.
        $this->fire(self::ON_AFTER_RUN, [$this]);
        $this->afterRun();

        if ($exit) {
            $this->stop((int)$returnCode);
        }
    }

    /**
     * dispatch command
     * @param string $command A command name
     * @return int|mixed
     */
    abstract protected function dispatch(string $command);

    /**
     * run a independent command
     * {@inheritdoc}
     */
    abstract public function runCommand($name, $believable = false);

    /**
     * run a controller's action
     * {@inheritdoc}
     */
    abstract public function runAction($name, $action, $believable = false, $standAlone = false);

    protected function afterRun()
    {
    }

    /**
     * @param int $code
     */
    public function stop($code = 0)
    {
        // call 'onAppStop' service, if it is registered.
        $this->fire(self::ON_STOP_RUN, [$this]);

        // display runtime info
        if ($this->isProfile()) {
            $title = '------ Runtime Stats(use --profile) ------';
            $stats = $this->meta['_stats'];
            $this->meta['_stats'] = FormatUtil::runtime($stats['startTime'], $stats['startMemory'], $stats);
            $this->output->write('');
            $this->output->aList($this->meta['_stats'], $title);
        }

        exit((int)$code);
    }

    /**
     * @param string $command
     * @param InputInterface $input
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
        if (!\in_array(PHP_SAPI, ['cli', 'cli-server'], true)) {
            header('HTTP/1.1 403 Forbidden');
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
     * @param int $num
     * @param string $str
     * @param string $file
     * @param int $line
     * @throws \InvalidArgumentException
     */
    public function handleError(int $num, string $str, string $file, int $line)
    {
        $this->handleException(new \ErrorException($str, 0, $num, $file, $line));
        $this->stop(-1);
    }

    /**
     * 运行异常处理
     * @param \Throwable $e
     * @throws \InvalidArgumentException
     */
    public function handleException($e)
    {
        $class = \get_class($e);
        $this->logError($e);

        // open debug, throw exception
        if ($this->isDebug()) {
            $tpl = <<<ERR
\n<error> Error </error> <mga>%s</mga>

At File <cyan>%s</cyan> line <bold>%d</bold>
Exception $class
<comment>Code View:</comment>\n\n%s
<comment>Code Trace:</comment>\n\n%s\n
ERR;
            $line = $e->getLine();
            $file = $e->getFile();
            $snippet = Highlighter::create()->highlightSnippet(file_get_contents($file), $line, 3, 3);
            $message = sprintf(
                $tpl,
                // $e->getCode(),
                $e->getMessage(),
                $file,
                $line,
                // __METHOD__,
                $snippet,
                \str_replace('):', "):\n  -", $e->getTraceAsString())
            );

            if ($this->meta['hideRootPath'] && ($rootPath = $this->meta['rootPath'])) {
                $message = \str_replace($rootPath, '{ROOT}', $message);
            }

            $this->output->write($message, false);
        } else {
            // simple output
            $this->output->error('An error occurred! MESSAGE: ' . $e->getMessage());
            $this->output->write("\nYou can use '--debug' to see error details.");
        }
    }

    /**
     * @param \Throwable $e
     */
    protected function logError($e)
    {
        // you can log error on sub class ...
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
     * @param $name
     * @param bool $isGroup
     * @throws \InvalidArgumentException
     */
    protected function validateName(string $name, $isGroup = false)
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
     * @param bool $quit
     * @param string $command
     */
    public function showHelpInfo($quit = true, string $command = null)
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
            'usage' => "$script <info>{command}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
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
        $os = PHP_OS;
        $date = date('Y.m.d');
        $logo = '';
        $name = $this->getMeta('name', 'Console Application');
        $version = $this->getMeta('version', 'Unknown');
        $publishAt = $this->getMeta('publishAt', 'Unknown');
        $updateAt = $this->getMeta('updateAt', 'Unknown');
        $phpVersion = PHP_VERSION;

        if ($logoTxt = $this->getLogoText()) {
            $logo = Helper::wrapTag($logoTxt, $this->getLogoStyle());
        }

        $this->output->aList([
            "$logo\n  <info>{$name}</info>, Version <comment>$version</comment>\n",
            'System Info' => "PHP version <info>$phpVersion</info>, on <info>$os</info> system",
            'Application Info' => "Update at <info>$updateAt</info>, publish at <info>$publishAt</info>(current $date)",
        ], null, [
            'leftChar' => '',
            'sepChar' => ' :  '
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
            \ksort($controllers);
            $controllerArr[] = PHP_EOL . '- <bold>Group Commands</bold>';
        }

        foreach ($controllers as $name => $controller) {
            $hasGroup = true;
            /** @var AbstractCommand $controller */
            $desc = $controller::getDescription() ?: $desPlaceholder;
            $aliases = $this->getCommandAliases($name);
            $extra = $aliases ? Helper::wrapTag(' [alias: ' . \implode(',', $aliases) . ']', 'info') : '';
            $controllerArr[$name] = $desc . $extra;
        }

        if (!$hasGroup && $this->isDebug()) {
            $controllerArr[] = '... Not register any group command(controller)';
        }

        // all independent commands, Independent, Single, Alone
        if ($commands = $this->commands) {
            $commandArr[] = PHP_EOL . '- <bold>Alone Commands</bold>';
            \ksort($commands);
        }

        foreach ($commands as $name => $command) {
            $desc = $desPlaceholder;
            $hasCommand = true;

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
        $internalOptions = FormatUtil::alignmentOptions(self::$internalOptions);

        $this->output->mList([
            'Usage:' => "$script <info>{command}</info> [arg0 arg1=value1 arg2=value2 ...] [--opt -v -h ...]",
            'Options:' => $internalOptions,
            'Internal Commands:' => $internalCommands,
            'Available Commands:' => \array_merge($controllerArr, $commandArr),
        ], [
            'sepChar' => '  ',
        ]);

        unset($controllerArr, $commandArr, $internalCommands);
        $this->output->write("More command information, please use: <cyan>$script {command} -h</cyan>");

        $quit && $this->stop();
    }

    /**
     * @param string $name
     * @param string $default
     * @return string|null
     */
    public function getCommandMessage($name, $default = null)
    {
        return $this->commandMessages[$name] ?? $default;
    }

    /**
     * @param string $name The command name
     * @param string $message
     * @return $this
     */
    public function addCommandMessage($name, $message): self
    {
        if ($name && $message) {
            $this->commandMessages[$name] = $message;
        }

        return $this;
    }

    /**
     * @param string $name
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
        return $this->meta['logoText'] ?? null;
    }

    /**
     * @param string $logoTxt
     * @param string|null $style
     */
    public function setLogo(string $logoTxt, string $style = null)
    {
        $this->meta['logoText'] = $logoTxt;

        if ($style) {
            $this->meta['logoStyle'] = $style;
        }
    }

    /**
     * @return string|null
     */
    public function getLogoStyle()
    {
        return $this->meta['logoStyle'] ?? 'info';
    }

    /**
     * @param string $style
     */
    public function setLogoStyle(string $style)
    {
        $this->meta['logoStyle'] = $style;
    }

    /**
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->getMeta('rootPath', '');
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
        return $this->meta['name'];
    }

    /**
     * set meta info
     * @param array $meta
     */
    public function setMeta(array $meta)
    {
        if ($meta) {
            $this->meta = array_merge($this->meta, $meta);
        }
    }

    /**
     * get meta info
     * @param null|string $name
     * @param null|string $default
     * @return array|string
     */
    public function getMeta(string $name = null, $default = null)
    {
        if (!$name) {
            return $this->meta;
        }

        return $this->meta[$name] ?? $default;
    }

    /**
     * is Debug
     * @return boolean|int
     */
    public function isDebug()
    {
        return $this->input->getOpt('debug', $this->meta['debug']);
    }

    /**
     * is profile
     * @return boolean
     */
    public function isProfile(): bool
    {
        return (bool)$this->input->getOpt('profile', $this->getMeta('profile'));
    }

    /**
     * @param null|string $name
     * @return array
     */
    public function getCommandAliases($name = null): array
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
     * @param array $meta
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
     * @param $value
     */
    public function setCommandMetaValue(string $command, string $key, $value)
    {
        $this->commandsMeta[$command][$key] = $value;
    }

    /**
     * @param string $command
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getCommandMetaValue(string $command, string $key, $default = null)
    {
        if (isset($this->commandsMeta[$command][$key])) {
            return $this->commandsMeta[$command][$key];
        }

        return $default;
    }
}
