<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-15
 * Time: 10:51
 */

namespace inhere\console;

/**
 * Class LiteApp
 */
class LiteApp
{

///////////////////////////////////////////////////////////////////
/// simple cli support
///////////////////////////////////////////////////////////////////

    private $args = [];
    private $opts = [];
    private $script = '';
    private $command = '';

    private $commands = [];
    private $messages = [];

    /**
     * @param bool $exit
     */
    public function dispatchCli($exit = true)
    {
        $this->parseCliArgv();

        if (isset($this->args[0])) {
            $this->command = $this->args[0];
            unset($this->args[0]);
        }

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
            $text = sprintf(
                "Exception(%d): %s\nFile: %s(Line %d)\nTrace:\n%s\n",
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            exit($text);
        }

        if ($exit) {
            exit((int)$status);
        }
    }

    /**
     * @param $command
     * @param $handler
     * @return mixed
     */
    public function runHandler($command, $handler)
    {
        if (is_string($handler)) {
            // function name
            if (function_exists($handler)) {
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
     * @param string $desc
     */
    public function addCommand($command, $handler, $desc = '')
    {
        if (!$command || !$handler) {
            throw new \InvalidArgumentException('Invalid arguments');
        }

        $this->commands[$command] = $handler;
        $this->messages[$command] = trim($desc);
    }

    /**
     * @param string $err
     */
    public function showCommands($err = '')
    {
        if ($err) {
            echo "ERROR: $err\n\n";
        }

        $help = "Available Commands:\n";

        foreach ($this->messages as $command => $desc) {
            $command = str_pad($command, 18, ' ');
            $desc = $desc ?: 'No description for the command';
            $help .= "  $command   $desc\n";
        }

        echo $help . PHP_EOL;
        exit(0);
    }

}