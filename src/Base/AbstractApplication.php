<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-09
 * Time: 18:37
 */

namespace Inhere\Console\Base;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Traits\InputOutputTrait;
use Inhere\Console\Traits\SimpleEventTrait;

/**
 * Class AbstractApplication
 * @package Inhere\Console
 */
abstract class AbstractApplication implements ApplicationInterface
{
    use InputOutputTrait;
    use SimpleEventTrait;

    /** @var bool render no color */
    private static $noColor = false;

    /**
     * @var string
     */
    public $delimiter = ':'; // '/' ':'

    /**
     * app meta config
     * @var array
     */
    private $meta = [
        'name' => 'My Console',
        'version' => '0.5.1',
        'publishAt' => '2017.03.24',
        'updateAt' => '2017.03.24',
        'rootPath' => '',
        'hideRootPath' => true,
        // 'env' => 'pdt', // dev test pdt
        // 'debug' => false,
        // 'charset' => 'UTF-8',
        // 'timeZone' => 'Asia/Shanghai',
    ];

    /**
     * @var array
     */
    protected static $internalCommands = [
        'version' => 'Show application version information',
        'help' => 'Show application help information',
        'list' => 'List all group and independent commands',
    ];

    /**
     * @var array
     */
    protected static $internalOptions = [
        '--debug' => 'setting the application runtime debug level',
        '--no-color' => 'setting no color for message output',
        '--version' => 'Show application version information',
    ];

    /**
     * @var array
     */
    protected $controllers = [];

    /**
     * @var array
     */
    protected $commands = [];

    /**
     * @var array
     */
    private $commandMessages = [];

    /**
     * @var string
     */
    private $commandName;

    /**
     * @var bool
     */
    //private $hideRootPath = true;

    /**
     * App constructor.
     * @param array $meta
     * @param Input $input
     * @param Output $output
     */
    public function __construct(array $meta = [], Input $input = null, Output $output = null)
    {
        $this->runtimeCheck();
        $this->setMeta($meta);

        $this->input = $input ?: new Input();
        $this->output = $output ?: new Output();

        $this->init();
    }

    protected function runtimeCheck()
    {
        // check env
        if (!in_array(PHP_SAPI, ['cli', 'cli-server'], true)) {
            header('HTTP/1.1 403 Forbidden');
            exit("  403 Forbidden \n\n"
            . " current environment is CLI. \n"
            . " :( Sorry! Run this script is only allowed in the terminal environment!\n,You are not allowed to access this file.\n");
        }
    }

    protected function init()
    {
        $this->commandName = $this->input->getCommand();
        set_exception_handler([$this, 'handleException']);
    }

    /**********************************************************
     * app run
     **********************************************************/

    protected function prepareRun()
    {
        // date_default_timezone_set($this->config('timeZone', 'UTC'));
        //new AutoCompletion(array_merge($this->getCommandNames(), $this->getControllerNames()));
    }

    /**
     * run app
     * @param bool $exit
     */
    public function run($exit = true)
    {
        $command = trim($this->input->getCommand(), $this->delimiter);

        $this->prepareRun();
        $this->filterSpecialCommand($command);

        // call 'onBeforeRun' service, if it is registered.
        self::fire(self::ON_BEFORE_RUN, [$this]);

        // do run ...
        try {
            $returnCode = $this->dispatch($command);
        } catch (\Throwable $e) {
            self::fire(self::ON_RUN_ERROR, [$e, $this]);
            $returnCode = $e->getCode() === 0 ? __LINE__ : $e->getCode();
            $this->handleException($e);
        }

        // call 'onAfterRun' service, if it is registered.
        self::fire(self::ON_AFTER_RUN, [$this]);

        if ($exit) {
            $this->stop((int) $returnCode);
        }
    }

    /**
     * @param string $command A command name
     * @return int|mixed
     */
    abstract protected function dispatch($command);

    /**
     * @param int $code
     */
    public function stop($code = 0)
    {
        // call 'onAppStop' service, if it is registered.
        self::fire(self::ON_STOP_RUN, [$this]);

        exit((int) $code);
    }


    /**********************************************************
     * helper method for the application
     **********************************************************/

    /**
     * 运行异常处理
     * @param \Exception|\Throwable $e
     */
    public function handleException($e)
    {
        // $this->logger->ex($e);

        // open debug, throw exception
        if ($this->isDebug()) {
            $message = sprintf(
                "<red>Exception</red>: %s\nCalled At %s, Line: <cyan>%d</cyan>\nCatch the exception by: %s\nCode Trace:\n%s\n",
                // $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                get_class($e),
                $e->getTraceAsString()
            );

            if ($this->meta['hideRootPath'] && $rootPath = $this->meta['rootPath']) {
                $message = str_replace($rootPath, '{ROOT}', $message);
            }

            $this->output->write($message, false);
        } else {
            // simple output
            $this->output->error('An error occurred! MESSAGE: ' . $e->getMessage());
        }
    }

    /**
     * @param $command
     */
    protected function filterSpecialCommand($command)
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
            self::$noColor = true;
        }

        $command = $command ?: 'list';

        switch ($command) {
            case 'help':
                $this->showHelpInfo();
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
     */
    protected function validateName(string $name, $isGroup = false)
    {
        $pattern = $isGroup ? '/^[a-z][\w-]+$/' : '/^[a-z][\w-]*:?([a-z][\w-]+)?$/';

        if (1 !== preg_match($pattern, $name)) {
            throw new \InvalidArgumentException('The command name is must match: ' . $pattern);
        }

        if ($this->isInternalCommand($name)) {
            throw new \InvalidArgumentException("The command name [$name] is not allowed. It is a built in command.");
        }
    }

    /**********************************************************
     * some information for the application
     **********************************************************/

    /**
     * show the application help information
     * @param bool $quit
     */
    public function showHelpInfo($quit = true)
    {
        $script = $this->input->getScript();
        $sep = $this->delimiter;

        $this->output->helpPanel([
            'usage' => "$script [route|command] [arg0 arg1=value1 arg2=value2 ...] [--opt -v -h ...]",
            'example' => [
                "$script test (run a independent command)",
                "$script home{$sep}index (run a command of the group)",
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
        $date = date('Y-m-d');
        $name = $this->getMeta('name', 'Console Application');
        $version = $this->getMeta('version', 'Unknown');
        $publishAt = $this->getMeta('publishAt', 'Unknown');
        $updateAt = $this->getMeta('updateAt', 'Unknown');
        $phpVersion = PHP_VERSION;
        $os = PHP_OS;

        $this->output->aList([
            "\n  <info>{$name}</info>, Version <comment>$version</comment>\n",
            'System Info' => "PHP version <info>$phpVersion</info>, on <info>$os</info> system",
            'Application Info' => "Update at <info>$updateAt</info>, publish at <info>$publishAt</info>(current $date)",
        ], null, [
            'leftChar' => ''
        ]);

        $quit && $this->stop();
    }

    /**
     * show the application command list information
     * @param bool $quit
     */
    public function showCommandList($quit = true)
    {
        $desPlaceholder = 'No description of the command';
        $script = $this->getScriptName();
        $controllerArr = $commandArr = [];

        // built in commands
        $internalCommands = static::$internalCommands;
        ksort($internalCommands);

        // all console controllers
        $controllers = $this->controllers;
        ksort($controllers);
        foreach ($controllers as $name => $controller) {
            /** @var AbstractCommand $controller */
            $controllerArr[$name] = $controller::getDescription() ?: $desPlaceholder;
        }

        // all independent commands
        $commands = $this->commands;
        ksort($commands);
        foreach ($commands as $name => $command) {
            $desc = $desPlaceholder;

            /** @var AbstractCommand $command */
            if (is_subclass_of($command, CommandInterface::class)) {
                $desc = $command::getDescription() ?: $desPlaceholder;
            } else if ($msg = $this->getCommandMessage($name)) {
                $desc = $msg;
            } else if (is_string($command)) {
                $desc = 'A handler: ' . $command;
            } else if (is_object($command)) {
                $desc = 'A handler by ' . get_class($command);
            }

            $commandArr[$name] = $desc;
        }

        // $this->output->write('There are all console controllers and independent commands.');
        $this->output->mList([
            //'There are all console controllers and independent commands.',
            'Usage:' => "$script [route|command] [arg0 arg1=value1 arg2=value2 ...] [--opt -v -h ...]",
            'Group Commands:(by controller)' => $controllerArr ?: '... No register any group command(controller)',
            'Independent Commands:' => $commandArr ?: '... No register any independent command',
            'Internal Commands:' => $internalCommands,
            'Internal Options:' => self::$internalOptions
        ]);

        $this->output->write("More please see: <cyan>$script [controller|command] -h</cyan>");
        $quit && $this->stop();
    }

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getCommandMessage($name, $default = null)
    {
        return $this->commandMessages[$name] ?? $default;
    }

    /**
     * @param string $name The command name
     * @param string $message
     * @return string
     */
    public function addCommandMessage($name, $message)
    {
        return $this->commandMessages[$name] = $message;
    }

    /**********************************************************
     * getter/setter methods
     **********************************************************/

    /**
     * @return bool
     */
    public static function isNoColor(): bool
    {
        return self::$noColor;
    }

    /**
     * @return array
     */
    public function getControllerNames()
    {
        return array_keys($this->controllers);
    }

    /**
     * @return array
     */
    public function getCommandNames()
    {
        return array_keys($this->commands);
    }

    /**
     * @param array $controllers
     */
    public function setControllers(array $controllers)
    {
        foreach ($controllers as $name => $controller) {
            if (is_int($name)) {
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
    public function isController($name)
    {
        return isset($this->controllers[$name]);
    }

    /**
     * @param array $commands
     */
    public function setCommands(array $commands)
    {
        foreach ($commands as $name => $handler) {
            if (is_int($name)) {
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
    public function isCommand($name)
    {
        return isset($this->commands[$name]);
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
    public function getName()
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
    public function getMeta($name = null, $default = null)
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
        return $this->input->getOpt('debug');
    }

    /**
     * @return array
     */
    public function getCommandMessages(): array
    {
        return $this->commandMessages;
    }

    /**
     * @param array $commandMessages
     */
    public function setCommandMessages(array $commandMessages)
    {
        $this->commandMessages = $commandMessages;
    }
}
