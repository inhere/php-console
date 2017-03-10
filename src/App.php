<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 17:56
 */

namespace inhere\console;

use inhere\console\utils\Interact;
use inhere\console\utils\Show;

/**
 * Class App
 * @package inhere\console
 */
class App extends AbstractApp
{
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
            $status = - $e->getCode();
            $this->dispatchExHandler($e);
        }

        return $status;
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

        if ( $cb = self::$hooks[self::ON_NOT_FOUND] ) {
            $cb($command, $this);
        } else {
            // not match, output error message
            $this->output->error("Console Controller or Command [$command] not exists!");
            $this->showCommandList(false);
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

        /** @var Controller $object */
        $object = new $controller($this->input, $this->output);
        $object->setName($name);

        if ( !($object instanceof Controller) ) {
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
        if ( $this->isDebug() ) {
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
        // show help `./bin/app` OR `./bin/app help`
        $showHelp = !$command || $command === 'help';

        if ($showHelp) {
            $this->showHelpInfo(false);
            $this->showCommandList();
        }

        switch ($command) {
            case 'list':
                $this->showCommandList();
                break;
            case 'version':
                $this->showVersionInfo();
                break;
        }
    }

    /**
     * show the application help information
     * @param bool $quit
     */
    public function showHelpInfo($quit = true)
    {
        $script = $this->input->getScriptName();
        $message = <<<EOF
<comment>Usage:</comment>
  $script [route|command] [arg1=value1 arg2=value ...] [-v|-h ...]
    
<comment>Example:</comment>
  $script test
  $script home/index

EOF;
        $this->output->write($message);
        $quit && $this->stop();
    }

    /**
     * show the application version information
     * @param bool $quit
     */
    public function showVersionInfo($quit = true)
    {
        $version = $this->config('version', 'Unknown');
        $phpVersion = PHP_VERSION;
        $os = PHP_OS;

        $message = <<<EOF
Console App Version <comment>$version</comment>
 
<comment>System:</comment>
  PHP <info>$phpVersion</info>, on OS <info>$os</info>
EOF;
        $this->output->write($message);
        $quit && $this->stop();
    }

    /**
     * show the application command list information
     * @param bool $quit
     */
    public function showCommandList($quit = true)
    {
        $script = $this->input->getScriptName();
        $controllerArr = $commandArr = [];

        // built in commands
        $internalCommands = $this->internalCommands;
        ksort($internalCommands);

        // all console controllers
        $controllers = $this->controllers;
        ksort($controllers);
        foreach ($controllers as $name => $controller) {
            $controllerArr[$name] = $controller::DESCRIPTION ? : 'No description';
        }

        // all independent commands
        $commands = $this->commands;
        ksort($commands);
        foreach ($commands as $name => $command) {
            $desc = 'No description';

            if ( is_subclass_of($command, Command::class) ) {
                $desc = $command::DESCRIPTION ? : 'No description';
            } else if ( is_string($command) ) {
                $desc = 'A handler: ' . $command;
            } else if ( is_object($command) ) {
                $desc = $command instanceof \Closure ? 'A Closure' : 'A Object';
            }

            $commandArr[$name] = $desc;
        }

        $this->output->write("There are all console controllers and independent commands.");

        Show::multiList([
            '<comment>Group Commands:</comment>(by controller)' => $controllerArr,
            '<comment>Independent Commands:</comment>' => $commandArr,
            '<comment>Internal Commands:</comment>' => $internalCommands
        ]);

        $this->output->write("more please see: <info>$script [controller|command]</info>");
        $quit && $this->stop();
    }
}
