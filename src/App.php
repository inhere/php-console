<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 17:56
 */

namespace inhere\console;


use inhere\console\io\Input;
use inhere\console\io\Output;

/**
 * Class App
 * @package inhere\console
 *
 */
class App
{
    // event name list
    const EVT_APP_INIT = 'appInit';
    const EVT_BEFORE_RUN = 'beforeRun';
    const EVT_AFTER_RUN  = 'afterRun';
    const EVT_APP_STOP   = 'appStop';
    const EVT_NOT_FOUND  = 'notFound';

    /**
     * @var array
     */
    protected static $eventHandlers = [
        'appInit' => '',
        'beforeRun' => '',
        'afterRun' => '',
        'appStop' => '',
        'notFound' => '',
    ];

    /**
     * app config
     * @var array
     */
    protected static $config = [
        'env' => 'pdt', // dev test pdt
        'debug' => false,
        'charset' => 'UTF-8',
        'timeZone' => 'Asia/Shanghai',
        // 'defaultRoute' => '',
    ];

    /**
     * @var Input
     */
    public $input;

    /**
     * @var Output
     */
    public $output;

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
        self::$config = array_merge(self::$config, $config);
        $this->input = $input ?: new Input();
        $this->output = $output ?: new Output();

        $this->init();

        // call 'onAppInit' service, if it is registered.
        if ( $cb = self::$eventHandlers[self::EVT_APP_INIT] ) {
            $cb($this);
        }
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
    public function run($exit=true)
    {
        $this->prepareRun();

        // call 'onBeforeRun' service, if it is registered.
        if ( $cb = self::$eventHandlers[self::EVT_BEFORE_RUN] ) {
            $cb($this);
        }

        // do run ...
        $returnCode = $this->doRun();

        // call 'onAfterRun' service, if it is registered.
        if ( $cb = self::$eventHandlers[self::EVT_AFTER_RUN] ) {
            $cb($this);
        }

        if ($exit) {
            $this->stop((int)$returnCode);
        }
    }

    /**
     * @inheritdoc
     */
    public function doRun()
    {
        try {
            $status = $this->dispatch();
        } catch (\Exception $e) {
            $status = $e->getCode();
            $this->dispatchExHandler($e);
        }

        return $status;
    }

    /**
     * @param int $code
     */
    public function stop($code = 0)
    {
        // call 'onAppStop' service, if it is registered.
        if ( $cb = self::$eventHandlers[self::EVT_APP_STOP] ) {
            $cb($this);
        }

        exit((int)$code);
    }

    /**********************************************************
     * register console controller/command
     **********************************************************/

    /**
     * register a app console controller
     * @param string $name the service name
     * @param string $controller controller class
     * @return static
     */
    public function controller($name, $controller)
    {
        $this->controllers[$name] = $controller;

        return $this;
    }

    /**
     * @param string $name
     * @param string|array $class
     * @return $this
     */
    public function command($name, $class)
    {
        if (!$class) {
            throw new \RuntimeException('Parameters are not allowed to is empty!');
        }

        // is an class name string
        $this->commands[$name] = $class;

        return $this;
    }

    /**********************************************************
     * dispatch and run console controller/command
     **********************************************************/

    /**
     * @return int|mixed
     */
    public function dispatch()
    {
        $sep = '/';
        $command = $name = trim($this->input->getCommand(), $sep);

        $this->handleSpecialCommand($command);

        // is a command name
        if ( isset($this->commands[$name]) ) {
            $handler = $this->commands[$name];

            return $this->executeCommand($handler);
        }

        // is a controller name
        $action = '';

        // like 'home/index'
        if ( strpos($name, $sep) > 0 ) {
            $input = array_filter(explode($sep, $name));
            list($name, $action) = count($input) > 2 ? array_splice($input, 2) : $input;
        }

        if ( isset($this->controllers[$name]) ) {
            $controller = $this->controllers[$name];

            return $this->runAction($controller, $action);
        }

        if ( $cb = self::$eventHandlers[self::EVT_NOT_FOUND] ) {
            $cb($command, $this);
        } else {
            // not match, output error message
            $this->output->error("Controller or Command [$command] not exists!");
        }

        return -1;
    }

    /**
     * @param $handler
     * @return mixed
     */
    protected function executeCommand($handler)
    {
        if ( is_object($handler) && ($handler instanceof \Closure) ) {
            $status = $handler($this->input, $this->output);
        } else {
            if ( class_exists($handler, false) ) {
                throw new \InvalidArgumentException("The console command class [$handler] not exists!");
            }

            $object = new $handler;

            if ( !($object instanceof Command ) ) {
                throw new \InvalidArgumentException("The console command class [$handler] must instanceof the " . Command::class);
            }

            $status = $object->execute();
        }

        return $status;
    }

    /**
     * @param $controller
     * @param $action
     * @return mixed
     */
    protected function runAction($controller, $action)
    {
        if ( class_exists($controller, false) ) {
            throw new \InvalidArgumentException("The console controller class [$controller] not exists!");
        }

        $object = new $controller;

        if ( !($object instanceof Controller ) ) {
            throw new \InvalidArgumentException("The console controller class [$object] must instanceof the " . Controller::class);
        }

        return $object->run($action);
    }

    /**
     * 运行异常处理
     * @param \Exception $e
     * @throws \Exception
     */
    public function dispatchExHandler(\Exception $e )
    {
        // $this->logger->ex($e);

        // open debug, throw exception
        if ( self::$config['debug'] ) {
            throw $e;
        }

        // no output
        $this->output->error('An error occurred! MESSAGE: ' . $e->getMessage());
    }


    protected function handleSpecialCommand($command)
    {
        $this->showHelp($command);

        switch ($command) {
            case 'list':
                $this->output->info('command list. TODO', 0);
                break;
        }
    }

    protected function showHelp($command)
    {
        // show help `./bin/app` OR `./bin/app -h` OR `./bin/app --help`
        $showHelp = !$command || $this->input->getBool('h') || $this->input->getBool('help');

        if ( $showHelp ) {
            $script = $this->input->getScriptName();
            $this->output->write(<<<EOF
 <comment>Usage:</comment>
    $script [route|command] [arg1=value1 arg2=value ...]
 <comment>Example:</comment>
    $script test
    $script home/index
    $script home/help  Run this command can get more help info.

 <warning>Notice: 'home/index' don't write '/home/index'</warning>\n
EOF
    , 1, 0);
        }
    }

    /**
     * @return array
     */
    public static function events()
    {
        return [self::EVT_APP_INIT, self::EVT_BEFORE_RUN, self::EVT_AFTER_RUN, self::EVT_APP_STOP, self::EVT_NOT_FOUND];
    }

    /**
     * @param $event
     * @param callable $handler
     */
    public function on($event, callable $handler)
    {
        if (isset(self::events()[$event])) {
            self::$eventHandlers[$event] = $handler;
        }
    }

    /**
     * get/set config
     * @param  array|string $name
     * @param  mixed $default
     * @return mixed
     */
    public function config($name, $default=null)
    {
        // `$name` is array, set config.
        if (is_array($name)) {
            foreach ((array)$name as $key => $value) {
                self::$config[$key] = $value;
            }

            return true;
        }

        // is string, get config
        if (!is_string($name)) {
            return $default;
        }

        // allow get $config['top']['sub'] by 'top.sub'
        if ( strpos($name, '.') > 1 ) {
            list($topKey, $subKey) = explode('.', $name, 2);

            if ( isset(self::$config[$topKey]) && isset(self::$config[$topKey][$subKey])) {
                return self::$config[$topKey][$subKey];
            }
        }

        return isset(self::$config[$name]) ? self::$config[$name]: $default;
    }

    /**
     * @return Input
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param Input $input
     */
    public function setInput(Input $input)
    {
        $this->input = $input;
    }

    /**
     * @return Output
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param Output $output
     */
    public function setOutput(Output $output)
    {
        $this->output = $output;
    }

    /**
     * get config
     * @return array
     */
    public function getConfig()
    {
        return self::$config;
    }

    /**
     * is Debug
     * @return boolean
     */
    public function isDebug()
    {
        return (bool)self::$config('debug', false);
    }
}
