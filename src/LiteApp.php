<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-15
 * Time: 10:51
 */

namespace Inhere\Console;

use Toolkit\Cli\Color;

/**
 * Class LiteApp - Lite Application
 * @package Inhere\Console
 */
class LiteApp
{
    /****************************************************************************
     * simple cli support
     ****************************************************************************/

    /**
     * parse from `name=val var2=val2`
     * @var array
     */
    private $args = [];

    /**
     * parse from `--name=val --var2=val2 -d`
     * @var array
     */
    private $opts = [];

    /** @var string */
    private $script = '';

    /** @var string */
    private $command = '';

    /**
     * user add commands
     * @var array
     */
    private $commands = [];

    /**
     * description message for the command
     * @var array
     */
    private $messages = [];

    /**
     * @param bool $exit
     * @throws \InvalidArgumentException
     */
    public function run(bool $exit = true)
    {
        $this->parseCliArgv();

        if (isset($this->args[0])) {
            $this->command = $this->args[0];
            unset($this->args[0]);
        }

        $this->dispatch($exit);
    }

    /**
     * @param bool $exit
     * @throws \InvalidArgumentException
     */
    public function dispatch(bool $exit = true)
    {
        if (!$command = $this->command) {
            $this->showCommands();
        }

        $status = 0;

        try {
            if (isset($this->commands[$command])) {
                $status = $this->runHandler($command, $this->commands[$command]);
            } else {
                $this->showCommands("The command {$command} not exists!");
            }
        } catch (\Throwable $e) {
            $status = $this->handleException($e);
        }

        if ($exit) {
            $this->stop($status);
        }
    }

    /**
     * @param int $code
     */
    public function stop($code = 0)
    {
        exit((int)$code);
    }

    /**
     * @param string $command
     * @param $handler
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function runHandler(string $command, $handler)
    {
        if (\is_string($handler)) {
            // function name
            if (\function_exists($handler)) {
                return $handler($this);
            }

            if (class_exists($handler)) {
                $handler = new $handler;

                // $handler->execute()
                if (method_exists($handler, 'execute')) {
                    return $handler->execute($this);
                }
            }
        }

        // a \Closure OR $handler->__invoke()
        if (method_exists($handler, '__invoke')) {
            return $handler($this);
        }

        throw new \InvalidArgumentException("Invalid handler of the command: $command");
    }

    /**
     * @param \Throwable $e
     * @return int
     */
    protected function handleException(\Throwable $e): int
    {
        $code = $e->getCode() !== 0 ? $e->getCode() : 133;

        printf(
            "Exception(%d): %s\nFile: %s(Line %d)\nTrace:\n%s\n",
            $code,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        return $code;
    }

    /**
     * parseCliArgv
     */
    public function parseCliArgv()
    {
        /** @var array $argv */
        $argv = $_SERVER['argv'];
        $this->script = array_shift($argv);

        foreach ($argv as $key => $value) {
            // opts
            if (strpos($value, '-') === 0) {
                $value = trim($value, '-');

                if (!$value) {
                    continue;
                }

                if (strpos($value, '=')) {
                    list($n, $v) = explode('=', $value);
                    $this->opts[$n] = $v;
                } else {
                    $this->opts[$value] = true;
                }
            } else {
                if (strpos($value, '=')) {
                    list($n, $v) = explode('=', $value);
                    $this->args[$n] = $v;
                } else {
                    $this->args[] = $value;
                }
            }
        }
    }

    /**
     * @param string $command
     * @param string|\Closure $handler
     * @param string $description
     * @throws \InvalidArgumentException
     */
    public function addCommand(string $command, $handler, $description = '')
    {
        if (!$command || !$handler) {
            throw new \InvalidArgumentException('Invalid arguments');
        }

        $this->commands[$command] = $handler;
        $this->messages[$command] = trim($description);
    }

    /**
     * @param array $commands
     * @throws \InvalidArgumentException
     */
    public function commands(array $commands)
    {
        foreach ($commands as $command => $handler) {
            $des = '';

            if (\is_array($handler)) {
                $conf = array_values($handler);
                $handler = $conf[0];
                $des = $conf[1] ?? '';
            }

            $this->addCommand($command, $handler, $des);
        }
    }

    /****************************************************************************
     * helper methods
     ****************************************************************************/

    /**
     * @param string $err
     */
    public function showCommands($err = '')
    {
        if ($err) {
            echo Color::render("<red>ERROR</red>: $err\n\n");
        }

        $commandWidth = 12;
        $help = "Welcome to the Lite Console Application.\n\n<comment>Available Commands:</comment>\n";

        foreach ($this->messages as $command => $desc) {
            $command = str_pad($command, $commandWidth, ' ');
            $desc = $desc ?: 'No description for the command';
            $help .= "  $command   $desc\n";
        }

        echo Color::render($help) . PHP_EOL;
        exit(0);
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function getArg($name, $default = null)
    {
        return $this->args[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function getOpt($name, $default = null)
    {
        return $this->opts[$name] ?? $default;
    }

    /****************************************************************************
     * getter/setter methods
     ****************************************************************************/

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array $args
     */
    public function setArgs(array $args)
    {
        $this->args = $args;
    }

    /**
     * @return array
     */
    public function getOpts(): array
    {
        return $this->opts;
    }

    /**
     * @param array $opts
     */
    public function setOpts(array $opts)
    {
        $this->opts = $opts;
    }

    /**
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * @param string $script
     */
    public function setScript(string $script)
    {
        $this->script = $script;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand(string $command)
    {
        $this->command = $command;
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param array $commands
     */
    public function setCommands(array $commands)
    {
        $this->commands = $commands;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param array $messages
     */
    public function setMessages(array $messages)
    {
        $this->messages = $messages;
    }

}
