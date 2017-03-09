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
class App extends AbstractApp
{
    /**
     * app config
     * @var array
     */
    protected static $config = [
        'env' => 'pdt', // dev test pdt
        'debug' => false,
        'charset' => 'UTF-8',
        'timeZone' => 'Asia/Shanghai',
        'version' => '0.5.1',
    ];

    /**
     * @var array
     */
    protected $builtInCommands = [
        'help', 'list'
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

    /**********************************************************
     * app run
     **********************************************************/

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
        if (!$controller) {
            throw new \InvalidArgumentException('Parameters are not allowed to is empty!');
        }

        if ( isset($this->builtInCommands[$name]) ) {
            throw new \InvalidArgumentException("The controller name [$name] is not allowed. It is a built in command.");
        }

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
            throw new \InvalidArgumentException('Parameters are not allowed to is empty!');
        }

        if ( isset($this->builtInCommands[$name]) ) {
            throw new \InvalidArgumentException("The command name [$name] is not allowed. It is a built in command.");
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

        $this->filterSpecialCommand($command);

        //// is a command name

        if ( isset($this->commands[$name]) ) {
            return $this->runCommand($name, true);
        }

        //// is a controller name

        $action = '';

        // like 'home/index'
        if ( strpos($name, $sep) > 0 ) {
            $input = array_filter(explode($sep, $name));
            list($name, $action) = count($input) > 2 ? array_splice($input, 2) : $input;
        }

        if ( isset($this->controllers[$name]) ) {
            return $this->runAction($name, $action, true);
        }

        if ( $cb = self::$eventHandlers[self::EVT_NOT_FOUND] ) {
            $cb($command, $this);
        } else {
            // not match, output error message
            $this->output->error("Console Controller or Command [$command] not exists!");
        }

        return 404;
    }

    /**
     * run a command
     * @param string $name       Command name
     * @param bool   $believable The `$name` is believable
     * @return mixed
     */
    public function runCommand($name, $believable = false)
    {
        // if $believable = true, will skip check.
        if ( !$believable && !isset($this->commands[$name]) ) {
            throw new \InvalidArgumentException("The console independent-command [$name] not exists!");
        }

        // Command class
        $handler = $this->commands[$name];

        if ( is_object($handler) && ($handler instanceof \Closure) ) {
            $status = $handler($this->input, $this->output);
        } else {
            if ( !class_exists($handler, false) ) {
                throw new \InvalidArgumentException("The console command class [$handler] not exists!");
            }

            /** @var Command $object */
            $object = new $handler($this->input, $this->output);
            $object->setName($name);

            if ( !($object instanceof Command ) ) {
                throw new \InvalidArgumentException("The console command class [$handler] must instanceof the " . Command::class);
            }

            $status = $object->execute();
        }

        return $status;
    }

    /**
     * @param string $name       Controller name
     * @param string $action
     * @param bool   $believable The `$name` is believable
     * @return mixed
     */
    public function runAction($name, $action, $believable = false)
    {
        // if $believable = true, will skip check.
        if ( !$believable && !isset($this->controllers[$name]) ) {
            throw new \InvalidArgumentException("The console controller-command [$name] not exists!");
        }

        // Controller class
        $controller = $this->controllers[$name];

        if ( !class_exists($controller, false) ) {
            throw new \InvalidArgumentException("The console controller class [$controller] not exists!");
        }

        /** @var Controller $object */
        $object = new $controller($this->input, $this->output);
        $object->setName($name);

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

    /**
     * @param $command
     */
    protected function filterSpecialCommand($command)
    {

        // show help `./bin/app` OR `./bin/app -h` OR `./bin/app --help`
        $showHelp = !$command || $this->input->getBool('h') || $this->input->getBool('help');

        if ($showHelp) {
            $this->output->write($this->appHelp());
            $this->stop();
        }

        switch ($command) {
            case 'list':
                $this->appCommandList();
                $this->stop();
                break;
        }
    }

    protected function appHelp()
    {
        $script = $this->input->getScriptName();

        return <<<EOF
 <comment>Usage:</comment>
    $script [route|command] [arg1=value1 arg2=value ...] [-v|-h ...]
    
 <comment>Example:</comment>
    $script test
    $script home/index
    $script home/help  Run this command can get more help info.
EOF;
    }

    protected function appVersionInfo()
    {}

    protected function appCommandList()
    {
        // all console controllers
        $controllers = '';

        foreach ($this->controllers as $name => $controller) {
            $desc = $controller::DESCRIPTION ? : $controller;

            $controllers .= "    <info>$name</info>  $desc\n";
        }

        // all independent commands
        $commands = '';

        foreach ($this->commands as $name => $command) {
            $desc = 'Unknown';

            if ( is_subclass_of($command, Command::class) ) {
                $desc = $command::DESCRIPTION;
            } else if ( is_string($command) ) {
                $desc = $command;
            } else if ( is_object($command) ) {
                $desc = $command instanceof \Closure ? 'A Closure' : 'A Object';
            }

            $commands .= "    <info>$name</info>  $desc\n";
        }

        $string = <<<EOF
 There are all console controllers and commands.
    
 <comment>Console Controllers:</comment>
$controllers
 <comment>Independent Commands:</comment>
$commands
EOF;
        $this->output->write($string);
    }

    /**
     * @return array
     */
    public function getBuiltInCommands()
    {
        return $this->builtInCommands;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isBuiltInCommand($name)
    {
        return isset($this->builtInCommands[$name]);
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
        return (bool)self::$config['debug'];
    }
}
