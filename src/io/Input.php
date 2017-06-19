<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 19:23
 */

namespace inhere\console\io;

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
     */
    public function __construct()
    {
        $this->pwd = $this->getPwd();

        [
            $this->fullScript,
            $this->script,
            $this->args,
            $this->sOpts,
            $this->lOpts
        ] = self::parseOptArgs();

        // collect command `server`
        $this->command = isset($this->args[0]) ? array_shift($this->args) : '';
    }

    /**
     * 读取输入信息
     * @param  string $question 若不为空，则先输出文本消息
     * @param  bool $nl true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public function read($question = null, $nl = false)
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
    public function hasArg($name)
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
     * get (long/short)opt value(bool)
     * eg: -h --help
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public function boolOpt(string $name, $default = false)
    {
        return (bool)$this->getOpt($name, $default);
    }

    /**
     * check option exists
     * @param $name
     * @return bool
     */
    public function hasOpt(string $name)
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

    /**
     * check short-opt exists
     * @param $name
     * @return bool
     */
    public function hasSOpt(string $name)
    {
        return isset($this->sOpts[$name]);
    }

    /**
     * get short-opt value(bool)
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public function sBoolOpt(string $name, $default = false)
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

    /**
     * check long-opt exists
     * @param $name
     * @return bool
     */
    public function hasLOpt(string $name)
    {
        return isset($this->lOpts[$name]);
    }

    /**
     * get long-opt value(bool)
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public function lBoolOpt(string $name, $default = false)
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
    public function getSOpts(): array
    {
        return $this->sOpts;
    }

    /**
     * @param array $sOpts
     */
    public function setSOpts(array $sOpts)
    {
        $this->sOpts = $sOpts;
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
     */
    public function setLOpts(array $lOpts)
    {
        $this->lOpts = $lOpts;
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
    public function getPwd()
    {
        if (!$this->pwd) {
            $this->pwd =getcwd();
        }

        return $this->pwd;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// argument and option parser
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
     *
     * eg:
     *
     * ```
     * php cli.php server start name=john city=chengdu -s=test --page=23 -d -rf --debug --task=off -y=false -D -e dev -v vvv
     * ```
     *
     * Supports args:
     * <value>
     * arg=<value>
     *
     * Supports opts:
     * -e
     * -e <value>
     * -e=<value>
     * --long-opt
     * --long-opt <value>
     * --long-opt=<value>
     *
     * @link http://php.net/manual/zh/function.getopt.php#83414
     * @param array $noValues List of parameters without values(bool option keys)
     * @param bool $mergeOpts Whether merge short-opts and long-opts
     * @return array
     */
    public static function parseOptArgs($noValues = [], $mergeOpts = false)
    {
        $params = $GLOBALS['argv'];
        reset($params);

        $args = $sOpts = $lOpts = [];
        $fullScript = implode(' ', $params);
        $script = array_shift($params);

        while (list(, $p) = each($params)) {
            // is options
            if ($p{0} === '-') {
                $isLong = false;
                $opt = substr($p, 1);
                $value = true;

                // long-opt: (--<opt>)
                if ($opt{0} === '-') {
                    $isLong = true;
                    $opt = substr($opt, 1);

                    // long-opt: value specified inline (--<opt>=<value>)
                    if (strpos($opt, '=') !== false) {
                        list($opt, $value) = explode('=', $opt, 2);
                    }

                    // short-opt: value specified inline (-<opt>=<value>)
                } elseif (strlen($opt) > 2 && $opt{1} === '=') {
                    list($opt, $value) = explode('=', $opt, 2);
                }

                // check if next parameter is a descriptor or a value
                $nxp = current($params);

                if (!in_array($opt, $noValues) && $value === true && $nxp !== false && $nxp{0} !== '-') {
                    list(, $value) = each($params);

                    // short-opt: bool opts. like -e -abc
                } elseif (!$isLong && $value === true) {
                    foreach (str_split($opt) as $char) {
                        $sOpts[$char] = true;
                    }

                    continue;
                }

                if ($isLong) {
                    $lOpts[$opt] = self::filterBool($value);
                } else {
                    $sOpts[$opt] = self::filterBool($value);
                }

                // arguments: param doesn't belong to any option, define it is args
            } else {
                // value specified inline (<arg>=<value>)
                if (strpos($p, '=') !== false) {
                    list($name, $value) = explode('=', $p, 2);
                    $args[$name] = self::filterBool($value);
                } else {
                    $args[] = $p;
                }
            }
        }

        unset($params);

        if ($mergeOpts) {
            return [$fullScript, $script, $args, array_merge($sOpts, $lOpts)];
        }

        return [$fullScript, $script, $args, $sOpts, $lOpts];
    }

    /**
     * @param string $val
     * @param bool $enable
     * @return bool
     */
    private static function filterBool($val, $enable = true)
    {
        if ($enable) {
            if (is_bool($val) || is_numeric($val)) {
                return $val;
            }

            $tVal = strtolower($val);

            // check it is a bool value.
            if (false !== strpos(self::TRUE_WORDS, "|$tVal|")) {
                return true;
            } elseif (false !== strpos(self::FALSE_WORDS, "|$tVal|")) {
                return false;
            }
        }

        return $val;
    }
}
