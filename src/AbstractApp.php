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
use inhere\console\utils\TraitInputOutput;

/**
 * Class AbstractApp
 * @package inhere\console
 */
abstract class AbstractApp
{
    use TraitInputOutput;

    // event name list
    const ON_BEFORE_RUN = 'beforeRun';
    const ON_AFTER_RUN = 'afterRun';
    const ON_APP_STOP = 'appStop';
    const ON_NOT_FOUND = 'notFound';

    /**
     * @var array
     */
    protected static $hooks = [
        // 'appInit' => '',
        'beforeRun' => '',
        'afterRun' => '',
        'appStop' => '',
        'notFound' => '',
    ];

    /**
     * app config
     * @var array
     */
    protected $config = [
        'env' => 'pdt', // dev test pdt
        'debug' => false,
        'name' => 'My Console',
        'version' => '0.5.1',
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
        // ...
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
        if ($cb = self::$hooks[self::ON_BEFORE_RUN]) {
            $cb($this);
        }

        // do run ...
        $returnCode = $this->doRun();

        // call 'onAfterRun' service, if it is registered.
        if ($cb = self::$hooks[self::ON_AFTER_RUN]) {
            $cb($this);
        }

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
        if ($cb = self::$hooks[self::ON_APP_STOP]) {
            $cb($this);
        }

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
    public function controller(string $name, string $controller)
    {
        if (!$name || !$controller) {
            throw new \InvalidArgumentException('Parameters are not allowed to is empty!');
        }

        $this->checkName($name, true);

        if (!class_exists($controller)) {
            throw new \InvalidArgumentException("The console controller class [$controller] not exists!");
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
    public function command(string $name, $handler)
    {
        if (!$name || !$handler) {
            throw new \InvalidArgumentException('Parameters are not allowed to is empty!');
        }

        $this->checkName($name);

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
    public static function hooks(): array
    {
        return array_keys(self::$hooks);
    }

    /**
     * @param $event
     * @param callable $handler
     */
    public function on(string $event, callable $handler)
    {
        if (isset(self::$hooks[$event])) {
            self::$hooks[$event] = $handler;
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
            [$topKey, $subKey] = explode('.', $name, 2);

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
    public function getConfig(): array
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
    protected function checkName(string $name, $isGroup = false)
    {
        $pattern = $isGroup ? '/^[a-z][\w-]+$/' : '/^[a-z][\w-]*:?([a-z][\w-]+)?$/';

        if (1 !== preg_match($pattern, $name)) {
            throw new \InvalidArgumentException('The command name is must match: ' . $pattern);
        }

        if ($this->isInternalCommand($name)) {
            throw new \InvalidArgumentException("The command name [$name] is not allowed. It is a built in command.");
        }
    }
}
