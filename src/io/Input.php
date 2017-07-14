<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 19:23
 */

namespace inhere\console\io;

use inhere\console\utils\OptArgParse;

/**
 * Class Input
 * @package inhere\console\io
 */
class Input implements InputInterface
{
    /**
     * @var @resource
     */
    protected $inputStream = STDIN;

    /**
     * @var
     */
    private $pwd;

    /**
     * @var string
     */
    private $fullScript;

    /**
     * the script name
     * e.g `./bin/app` OR `bin/cli.php`
     * @var string
     */
    private $script;

    /**
     * the command name(Is first argument)
     * e.g `start` OR `start`
     * @var string
     */
    private $command;

    /**
     * raw argv data
     * @var array
     */
    private $tokens;

    /**
     * Input args data
     * @var array
     */
    private $args = [];

    /**
     * Input short-opts data
     * @var array
     */
    private $sOpts = [];

    /**
     * Input long-opts data
     * @var array
     */
    private $lOpts = [];

    /**
     * Input constructor.
     * @param null|array $argv
     */
    public function __construct($argv = null)
    {
        if (null === $argv) {
            $argv = $_SERVER['argv'];
        }

        $this->pwd = $this->getPwd();

        $this->fullScript = implode(' ', $argv);
        $this->script = array_shift($argv);
        $this->tokens = $argv;

        list($this->args, $this->sOpts, $this->lOpts) = OptArgParse::byArgv($argv);

        // collect command `server`
        $this->command = isset($this->args[0]) ? array_shift($this->args) : '';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $tokens = array_map(function ($token) {
            if (preg_match('{^(-[^=]+=)(.+)}', $token, $match)) {
                return $match[1] . OptArgParse::escapeToken($match[2]);
            }

            if ($token && $token[0] !== '-') {
                return OptArgParse::escapeToken($token);
            }

            return $token;
        }, $this->tokens);

        return implode(' ', $tokens);
    }

    /**
     * 读取输入信息
     * @param  string $question 若不为空，则先输出文本消息
     * @param  bool $nl true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public function read($question = null, $nl = false): string
    {
        fwrite(STDOUT, $question . ($nl ? "\n" : ''));

        return trim(fgets($this->inputStream));
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// arguments (eg: name=john city=chengdu)
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param string|int $name
     * @return bool
     */
    public function hasArg($name): bool
    {
        return isset($this->args[$name]);
    }

    /**
     * get Argument
     * @param null|int|string $name
     * @param mixed $default
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        return $this->get($name, $default);
    }

    /**
     * get Argument
     * @param null|int|string $name
     * @param mixed $default
     * @return mixed
     */
    public function getArg($name, $default = null)
    {
        return $this->get($name, $default);
    }

    /**
     * get Argument
     * @param null|int|string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return $this->args[$name] ?? $default;
    }

    /**
     * get a required argument
     * @param int|string $name
     * @return mixed
     */
    public function getRequiredArg($name)
    {
        if ('' !== $this->get($name, '')) {
            return $this->args[$name];
        }

        throw new \InvalidArgumentException("The argument '{$name}' is required");
    }

    /**
     * get first argument
     * @param string $default
     * @return string
     */
    public function getFirstArg($default = ''): string
    {
        return $this->get(0, $default);
    }

    /**
     * get second argument
     * @param string $default
     * @return string
     */
    public function getSecondArg($default = ''): string
    {
        return $this->get(1, $default);
    }

    /**
     * @param string|int $key
     * @param int $default
     * @return int
     */
    public function getInt($key, $default = 0): int
    {
        $value = $this->get($key);

        return $value === null ? (int)$default : (int)$value;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// long/short options (eg: -d --help)
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * get (long/short)opt value
     * eg: -e dev --name sam
     * @param string $name
     * @param null $default
     * @return bool|mixed|null
     */
    public function getOpt(string $name, $default = null)
    {
        // is long-opt
        if (isset($name{1})) {
            return $this->lOpt($name, $default);
        }

        return $this->sOpt($name, $default);
    }

    /**
     * alias of the getOpt()
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $name, $default = null)
    {
        return $this->getOpt($name, $default);
    }

    /**
     * get a required argument
     * @param int|string $name
     * @return mixed
     */
    public function getRequiredOpt($name)
    {
        if (null === ($val = $this->getOpt($name))) {
            throw new \InvalidArgumentException("The option '{$name}' is required");
        }

        return $val;
    }

    /**
     * get (long/short)opt value(bool)
     * eg: -h --help
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public function getBoolOpt(string $name, $default = false): bool
    {
        return (bool)$this->getOpt($name, $default);
    }
    public function boolOpt(string $name, $default = false): bool
    {
        return (bool)$this->getOpt($name, $default);
    }

    /**
     * check option exists
     * @param $name
     * @return bool
     */
    public function hasOpt(string $name): bool
    {
        return isset($this->sOpts[$name]) || isset($this->lOpts[$name]);
    }

    /**
     * get same opts value
     * eg: -h --help
     *
     * ```php
     * $input->sameOpt(['h','help']);
     * ```
     *
     * @param array $names
     * @param mixed $default
     * @return bool|mixed|null
     */
    public function getSameOpt(array $names, $default = null)
    {
        return $this->sameOpt($names, $default);
    }
    public function sameOpt(array $names, $default = null)
    {
        foreach ($names as $name) {
            if ($this->hasOpt($name)) {
                return $this->getOpt($name);
            }
        }

        return $default;
    }

    /////////////////// short-opts /////////////////////

    /**
     * get short-opt value
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function sOpt($name, $default = null)
    {
        return $this->sOpts[$name] ?? $default;
    }
    public function getShortOpt($name, $default = null)
    {
        return $this->sOpts[$name] ?? $default;
    }

    /**
     * check short-opt exists
     * @param $name
     * @return bool
     */
    public function hasSOpt(string $name): bool
    {
        return isset($this->sOpts[$name]);
    }

    /**
     * get short-opt value(bool)
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public function sBoolOpt(string $name, $default = false): bool
    {
        $val = $this->sOpt($name);

        return is_bool($val) ? $val : (bool)$default;
    }

    /////////////////// long-opts /////////////////////

    /**
     * get long-opt value
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function lOpt($name, $default = null)
    {
        return $this->lOpts[$name] ?? $default;
    }
    public function getLongOpt($name, $default = null)
    {
        return $this->lOpts[$name] ?? $default;
    }

    /**
     * check long-opt exists
     * @param $name
     * @return bool
     */
    public function hasLOpt(string $name): bool
    {
        return isset($this->lOpts[$name]);
    }

    /**
     * get long-opt value(bool)
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public function lBoolOpt(string $name, $default = false): bool
    {
        $val = $this->lOpt($name);

        return is_bool($val) ? $val : (bool)$default;
    }

    /**
     * @return array
     */
    public function getOpts(): array
    {
        return array_merge($this->sOpts, $this->lOpts);
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// getter/setter
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @return string
     */
    public function getFullScript(): string
    {
        return $this->fullScript;
    }

    /**
     * @return string
     */
    public function getScriptName(): string
    {
        return $this->script;
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
    public function getArguments(): array
    {
        return $this->args;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array $args
     * @param bool $replace
     */
    public function setArgs(array $args, $replace = false)
    {
        $this->args = $replace ? $args : array_merge($this->args, $args);
    }

    /**
     * @return array
     */
    public function getShortOpts(): array
    {
        return $this->sOpts;
    }

    /**
     * @return array
     */
    public function getSOpts(): array
    {
        return $this->sOpts;
    }

    /**
     * @param array $sOpts
     * @param bool $replace
     */
    public function setSOpts(array $sOpts, $replace = false)
    {
        $this->sOpts = $replace ? $sOpts : array_merge($this->sOpts, $sOpts);
    }

    /**
     * @return array
     */
    public function getLongOpts(): array
    {
        return $this->lOpts;
    }

    /**
     * @return array
     */
    public function getLOpts(): array
    {
        return $this->lOpts;
    }

    /**
     * @param array $lOpts
     * @param bool $replace
     */
    public function setLOpts(array $lOpts, $replace = false)
    {
        $this->lOpts = $replace ? $lOpts : array_merge($this->lOpts, $lOpts);
    }

    /**
     * @return resource
     */
    public function getInputStream()
    {
        return $this->inputStream;
    }

    /**
     * @return string
     */
    public function getPwd(): string
    {
        if (!$this->pwd) {
            $this->pwd = getcwd();
        }

        return $this->pwd;
    }
}
