<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-09
 * Time: 18:37
 */

namespace inhere\console;

use inhere\console\io\Input;
use inhere\console\io\Output;
use inhere\console\traits\InputOutputTrait;
use inhere\console\traits\SimpleEventStaticTrait;

/**
 * Class AbstractApp
 * @package inhere\console
 */
abstract class AbstractApp
{
    use InputOutputTrait;
    use SimpleEventStaticTrait;

    // event name list
    const ON_BEFORE_RUN = 'beforeRun';
    const ON_AFTER_RUN = 'afterRun';
    const ON_RUN_ERROR = 'runError';
    const ON_BEFORE_EXEC = 'beforeExec';
    const ON_AFTER_EXEC = 'afterExec';
    const ON_EXEC_ERROR = 'execError';
    const ON_STOP_RUN = 'stopRun';
    const ON_NOT_FOUND = 'notFound';

    /**
     * app config
     * @var array
     */
    protected $config = [
        'env' => 'pdt', // dev test pdt
        'debug' => false,
        'name' => 'My Console',
        'version' => '0.5.1',
        'publishAt' => '2017.03.24',
        'charset' => 'UTF-8',
        'timeZone' => 'Asia/Shanghai',
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
    protected $controllers = [];

    /**
     * @var array
     */
    protected $commands = [];

    /**
     * @var string
     */
    private $commandName;

    /**
     * App constructor.
     * @param array $config
     * @param Input $input
     * @param Output $output
     */
    public function __construct(array $config = [], Input $input = null, Output $output = null)
    {
        $this->input = $input ?: new Input();
        $this->output = $output ?: new Output();

        $this->setConfig($config);
        $this->init();
    }

    protected function init()
    {
        $this->commandName = $this->input->getCommand();
    }

    /**********************************************************
     * app run
     **********************************************************/

    protected function prepareRun()
    {
        date_default_timezone_set($this->config('timeZone', 'UTC'));
    }

    /**
     * run app
     * @param bool $exit
     */
    public function run($exit = true)
    {
        $this->prepareRun();

        $command = $this->input->getCommand();

        // like show help info
        $this->filterSpecialCommand($command);

        // call 'onBeforeRun' service, if it is registered.
        self::fire(self::ON_BEFORE_RUN, [$this]);

        // do run ...
        try {
            $returnCode = $this->dispatch($command);
        } catch (\Exception $e) {
            self::fire(self::ON_RUN_ERROR, [$e, $this]);
            $returnCode = $e->getCode() === 0 ? __LINE__ : $e->getCode();
            $this->dispatchExHandler($e);
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
     * register console controller/command
     **********************************************************/

    /**
     * Register a app group command(by controller)
     * @param string $name The controller name
     * @param string $controller The controller class
     * @return static
     */
    public function controller(string $name, string $controller = null)
    {
        if (class_exists($name, false)) {
            /** @var Controller $controller */
            $controller = $name;
            $name = $controller::getName();
        }

        if (!$name || !$controller) {
            throw new \InvalidArgumentException('Group-command "name" and "controller" not allowed to is empty!');
        }

        $this->validateName($name, true);

        if (!class_exists($controller)) {
            throw new \InvalidArgumentException("The console controller class [$controller] not exists!");
        }

        if (!is_subclass_of($controller, Controller::class)) {
            throw new \InvalidArgumentException('The console controller class must is subclass of the: ' . Controller::class);
        }

        $this->controllers[$name] = $controller;

        return $this;
    }

    /**
     * @param array $controllers
     */
    public function controllers(array $controllers)
    {
        foreach ($controllers as $name => $controller) {
            $this->controller($name, $controller);
        }
    }

    /**
     * Register a app independent console command
     * @param string $name
     * @param string|\Closure $handler
     * @return $this
     */
    public function command(string $name, $handler = null)
    {
        if (class_exists($name)) {
            /** @var Command $handler */
            $handler = $name;
            $name = $handler::getName();
        }

        if (!$name || !$handler) {
            throw new \InvalidArgumentException('Command "name" and "handler" not allowed to is empty!');
        }

        $this->validateName($name);

        if (is_string($handler)) {
            if (!class_exists($handler)) {
                throw new \InvalidArgumentException("The console command class [$handler] not exists!");
            }

            if (!is_subclass_of($handler, Command::class)) {
                throw new \InvalidArgumentException('The console command class must is subclass of the: ' . Command::class);
            }
        } elseif (!is_object($handler) || !method_exists($handler, '__invoke')) {
            throw new \InvalidArgumentException(sprintf(
                'The console command handler must is an subclass of %s OR a Closure OR a object have method __invoke()',
                Command::class
            ));
        }

        // is an class name string
        $this->commands[$name] = $handler;

        return $this;
    }

    /**
     * @param array $commands
     */
    public function commands(array $commands)
    {
        foreach ($commands as $name => $handler) {
            $this->command($name, $handler);
        }
    }

    /**********************************************************
     * helper method for the application
     **********************************************************/

    /**
     * 运行异常处理
     * @param \Exception $e
     * @throws \Exception
     */
    protected function dispatchExHandler(\Exception $e)
    {
        // $this->logger->ex($e);

        // open debug, throw exception
        if ($this->isDebug()) {
            throw $e;
        }

        // no output
        $this->output->error('An error occurred! MESSAGE: ' . $e->getMessage());
    }

    /**
     * @param $command
     */
    protected function filterSpecialCommand($command)
    {
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

        $this->output->helpPanel([
            'usage' => "$script [route|command] [arg0 arg1=value1 arg2=value2 ...] [--opt -v -h ...]",
            'example' => [
                "$script test (run a independent command)",
                "$script home/index (run a command of the group)"
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
        $version = $this->config('version', 'Unknown');
        $publishAt = $this->config['publishAt'];
        $phpVersion = PHP_VERSION;
        $os = PHP_OS;

        $this->output->aList([
            "Console Application <info>{$this->config['name']}</info> Version <comment>$version</comment>(publish at $publishAt)",
            'System' => "PHP version <info>$phpVersion</info>, on OS <info>$os</info>, current Date $date",
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
            if (is_subclass_of($command, Command::class)) {
                $desc = $command::getDescription() ?: $desPlaceholder;
            } else if (is_string($command)) {
                $desc = 'A handler: ' . $command;
            } else if (is_object($command)) {
                $desc = 'A handler by ' . get_class($command);
            }

            $commandArr[$name] = $desc;
        }

        $this->output->write('There are all console controllers and independent commands.');
        $this->output->mList([
            //'There are all console controllers and independent commands.',
            'Group Commands:(by controller)' => $controllerArr ?: '... No register any group command(controller)',
            'Independent Commands:' => $commandArr ?: '... No register any independent command',
            'Internal Commands:' => $internalCommands
        ]);

        $this->output->write("More please see: <cyan>$script [controller|command] -h</cyan>");
        $quit && $this->stop();
    }

    /**********************************************************
     * getter/setter methods
     **********************************************************/

    /**
     * @param array $controllers
     */
    public function setControllers(array $controllers)
    {
        $this->controllers($controllers);
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
        $this->commands($commands);
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
     * get/set config
     * @param  array|string $name
     * @param  mixed $default
     * @return mixed
     */
    public function config($name, $default = null)
    {
        // `$name` is array, set config.
        if (is_array($name)) {
            foreach ((array) $name as $key => $value) {
                $this->config[$key] = $value;
            }

            return true;
        }

        // is string, get config
        if (!is_string($name)) {
            return $default;
        }

        // allow get $config['top']['sub'] by 'top.sub'
        if (strpos($name, '.') > 1) {
            list($topKey, $subKey) = explode('.', $name, 2);

            if (isset($this->config[$topKey], $this->config[$topKey][$subKey])) {
                return $this->config[$topKey][$subKey];
            }
        }

        return $this->config[$name] ?? $default;
    }

    /**
     * set config
     * @param array $config
     */
    public function setConfig($config)
    {
        if ($config) {
            $this->config = array_merge($this->config, (array)$config);
        }
    }

    /**
     * get config
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * is Debug
     * @return boolean
     */
    public function isDebug(): bool
    {
        return (bool) $this->config('debug');
    }
}
