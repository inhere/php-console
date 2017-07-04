<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 17:56
 */

namespace inhere\console;

/**
 * Class App
 * @package inhere\console
 */
class App extends AbstractApp
{
    /**********************************************************
     * app run
     **********************************************************/

    protected function prepareRun()
    {
        parent::prepareRun();

        // like show help info
        $this->filterSpecialCommand($this->getCommandName());
    }

    /**
     * @inheritdoc
     */
    public function doRun()
    {
        try {
            $status = $this->dispatch();
        } catch (\Exception $e) {
            self::fire(self::ON_RUN_ERROR, [$e, $this]);
            $status = $e->getCode() === 0 ? __LINE__ : $e->getCode();
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
        $command = $name = $this->getCommandName();

        //// is a command name

        if (isset($this->commands[$name])) {
            return $this->runCommand($name, true);
        }

        //// is a controller name

        $action = '';

        // like 'home/index'
        if (strpos($name, $sep) > 0) {
            $input = array_filter(explode($sep, $name));
            list($name, $action) = count($input) > 2 ? array_splice($input, 2) : $input;
        }

        if (isset($this->controllers[$name])) {
            return $this->runAction($name, $action, true);
        }

        if (true !== self::fire(self::ON_NOT_FOUND, [$this])) {
            $this->output->error("Console controller or command [$command] not exists!");
            $this->showCommandList(false);
        }

        return 404;
    }

    /**
     * run a command
     * @param string $name Command name
     * @param bool $believable The `$name` is believable
     * @return mixed
     */
    public function runCommand($name, $believable = false)
    {
        // if $believable = true, will skip check.
        if (!$believable && !isset($this->commands[$name])) {
            throw new \InvalidArgumentException("The console independent-command [$name] not exists!");
        }

        // Command class
        $handler = $this->commands[$name];

        if (is_object($handler) && ($handler instanceof \Closure)) {
            $status = $handler($this->input, $this->output);
        } else {
            if (!class_exists($handler)) {
                throw new \InvalidArgumentException("The console command class [$handler] not exists!");
            }

            /** @var Command $object */
            $object = new $handler($this->input, $this->output);

            if (!($object instanceof Command)) {
                throw new \InvalidArgumentException("The console command class [$handler] must instanceof the " . Command::class);
            }

            $object::setName($name);
            $status = $object->run();
        }

        return $status;
    }

    /**
     * @param string $name Controller name
     * @param string $action
     * @param bool $believable The `$name` is believable
     * @return mixed
     */
    public function runAction($name, $action, $believable = false)
    {
        // if $believable = true, will skip check.
        if (!$believable && !isset($this->controllers[$name])) {
            throw new \InvalidArgumentException("The console controller-command [$name] not exists!");
        }

        // Controller class
        $controller = $this->controllers[$name];

        if (!class_exists($controller)) {
            throw new \InvalidArgumentException("The console controller class [$controller] not exists!");
        }

        /** @var Controller $object */
        $object = new $controller($this->input, $this->output);

        if (!($object instanceof Controller)) {
            throw new \InvalidArgumentException("The console controller class [$object] must instanceof the " . Controller::class);
        }

        $object::setName($name);

        return $object->setAction($action)->run();
    }

    /**
     * 运行异常处理
     * @param \Exception $e
     * @throws \Exception
     */
    public function dispatchExHandler(\Exception $e)
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
        // show help `./bin/app` OR `./bin/app help`
        if (!$command || $command === 'help') {
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
        $script = $this->input->getScript();

        $this->output->helpPanel([
            'usage' => "$script [route|command] [arg1=value1 arg2=value ...] [-v|-h ...]",
            'example' => [
                "$script test",
                "$script home/index"
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
        $internalCommands = $this->internalCommands;
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
                $desc = $command instanceof \Closure ? 'A Closure' : 'A Object';
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

        $this->output->write("more please see: <info>$script [controller|command]</info>");
        $quit && $this->stop();
    }
}
