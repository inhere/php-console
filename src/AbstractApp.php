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
    protected $internalCommands = [
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
        // ...
    }

    /**
     * run app
     * @param bool $exit
     */
    public function run($exit = true)
    {
        $this->prepareRun();

        // call 'onBeforeRun' service, if it is registered.
        self::fire(self::ON_BEFORE_RUN, [$this]);

        // do run ...
        $returnCode = $this->doRun();

        // call 'onAfterRun' service, if it is registered.
        self::fire(self::ON_AFTER_RUN, [$this]);

        if ($exit) {
            $this->stop((int)$returnCode);
        }
    }

    /**
     * do run
     */
    abstract public function doRun();

    /**
     * @param int $code
     */
    public function stop($code = 0)
    {
        // call 'onAppStop' service, if it is registered.
        self::fire(self::ON_STOP_RUN, [$this]);

        exit((int)$code);
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

    /**
     * @return array
     */
    public function getInternalCommands(): array
    {
        return $this->internalCommands;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isInternalCommand(string $name): bool
    {
        return isset($this->internalCommands[$name]);
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
            foreach ((array)$name as $key => $value) {
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
    public function setConfig(array $config)
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
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
        return (bool)$this->config['debug'];
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

    /**
     * @return string
     */
    public function getCommandName(): string
    {
        return $this->commandName;
    }
}
