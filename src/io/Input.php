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
class Input
{
    /**
     * @var @resource
     */
    protected $inputStream = STDIN;

    /**
     * the script name
     * e.g `./bin/app` OR `bin/cli.php`
     * @var string
     */
    private $scriptName = '';

    /**
     * the script name
     * e.g `image/packTask` OR `start`
     * @var string
     */
    private $command = '';

    /**
     * Input data
     * @var array
     */
    private $args = [];

    /**
     * Input data
     * @var array
     */
    private $opts = [];

    /**
     * Input constructor.
     * @param bool $fillToGlobal
     */
    public function __construct($fillToGlobal = false)
    {
        [$this->scriptName, $this->command, $this->args, $this->opts] = self::parseGlobalArgv($fillToGlobal);
    }

    /**
     * 读取输入信息
     * @param  string $question  若不为空，则先输出文本消息
     * @param  bool   $nl       true 会添加换行符 false 原样输出，不添加换行符
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
     * @param null|int|string $name
     * @param mixed $default
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        return $this->get($name, $default);
    }
    public function getArg($name, $default = null)
    {
        return $this->get($name, $default);
    }
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

    /**
     * get bool value form args
     * @param string|int $key
     * @param bool $default
     * @return bool
     */
    public function getBool($key, $default = false): bool
    {
        if ( !$this->hasArg($key) ) {
            return (bool)$default;
        }

        $value = strtolower($this->args[$key]);

        return 'true' === $value || 'on' === $value;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// options (eg: -d --help)
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param string $name
     * @param null $default
     * @return bool|mixed|null
     */
    public function getOption(string $name, $default = null)
    {
        return $this->getOpt($name, $default);
    }
    public function getOpt(string $name, $default = null)
    {
        if ( !$this->hasOpt($name) ) {
            return $default;
        }

        return $this->opts[$name];
    }

    /**
     * check option exists
     * @param $name
     * @return bool
     */
    public function hasOpt(string $name)
    {
        return isset($this->opts[$name]);
    }

    /**
     * check option is a bool value
     * @param string $name
     * @return bool
     */
    public function isBoolOpt(string $name)
    {
        return is_bool($this->opts[$name] ?? null);
    }

    /**
     * get option value(bool)
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public function boolOpt(string $name, $default = false)
    {
        return $this->getBoolOpt($name, $default);
    }
    public function getBoolOpt(string $name, $default = false)
    {
        if ($this->isBoolOpt($name)) {
            return $this->opts[$name];
        }

        return (bool)$default;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// getter/setter
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @return string
     */
    public function getScript(): string
    {
        return $this->scriptName;
    }

    /**
     * @return string
     */
    public function getScriptName(): string
    {
        return $this->scriptName;
    }

    /**
     * @param string $scriptName
     */
    public function setScriptName(string $scriptName)
    {
        $this->scriptName = $scriptName;
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
     * @return resource
     */
    public function getInputStream()
    {
        return $this->inputStream;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// argument and option parser
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param bool $fillToGlobal
     * @return array
     */
    public static function parseGlobalArgv($fillToGlobal = false)
    {
        // eg: `./bin/app image/packTask name=john city -s=test --page=23 -d -rf --debug`
        // eg: `php cli.php image/packTask name=john city -s=test --page=23 -d -rf --debug`
        global $argv;

        $tmp = $argv;
        $command = '';
        $scriptName = array_shift($tmp);

        // collect command `image/packTask`
        if ( isset($tmp[0]) && $tmp[0]{0} !== '-' && (false === strpos($tmp[0], '=')) ) {
            $command = trim(array_shift($tmp), '/');
        }

        $args = $opts = [];

        // parse query params
        // `./bin/app image/packTask start name=john city=chengdu -s=test --page=23 -d -rf --debug --task=off`
        // parse to
        // $args = [ 'name' => 'john', 0 => 'start', 'city' => 'chengdu' ];
        // $opts = [ 'd' => true, 'f' => true, 'r' => true, 's' => 'test', 'debug' => true, 'task' => false ]
        if ($tmp) {
            foreach ($tmp as $item) {
                // is a option
                if ($item{0} === '-') {
                    static::parseOption($item, $opts);

                // is a argument
                } else {
                    static::parseArgument($item, $args);
                }
            }

            if ($fillToGlobal) {
                $_REQUEST = $_GET = $args;
            }
        }

        unset($tmp);
        return [$scriptName, $command, $args, $opts];
    }

    /**
     * will parse option
     *
     * eg: `-s=test --page=23 -d -rf --debug --task=false  --id=23 --id=154`
     *
     * to:
     *
     * ```
     * $opts = [
     *  'd' => true,
     *  'f' => true,
     *  'r' => true,
     *  's' => 'test',
     *  'debug' => true,
     *  'task' => false
     * ]
     * ```
     * @param $item
     * @param $opts
     */
    protected static function parseOption($item, &$opts)
    {
        // is a have value option. eg: `-s=test --page=23`
        if ( strpos($item, '=') ) {
            $item = trim($item,'-= ');
            [$name, $val] = explode('=', $item);
            $tVal = strtolower($val);

            // check it is a bool value.
            if ($tVal === 'on' || $tVal === 'true') {
                $opts[$name] = true;
            } elseif ($tVal === 'off' || $tVal === 'false') {
                $opts[$name] = false;

            // is array. eg: `--id=23 --id=154`
            } elseif (isset($opts[$name])) {
                if (is_array($opts[$name])) {
                    $opts[$name][] = $val;

                // expect bool option. so not use `else`
                } elseif (is_string($opts[$name])) {
                    $prev = $opts[$name];
                    $opts[$name] = [$prev, $val];
                }
            } else {
                $opts[$name] = $val;
            }

        // is a no value option
        } else {
            // is a short option. eg: `-d -rf`
            if ($item{1} !== '-') {
                $item = trim($item,'-');
                foreach (str_split($item) as $char) {
                    $opts[$char] = true;
                }

            // is a long option. eg: `--debug`
            } else {
                $item = trim($item,'-');
                $opts[$item] = true;
            }
        }
    }

    /**
     * parse argument list
     *
     * eg: `start name=john name=tom city=chengdu`
     *
     * to:
     *
     * ```
     * [ 'name' => ['john', 'tom'], 0 => 'start', 'city' => 'chengdu' ];
     * ```
     *
     * @param $item
     * @param array $args
     */
    protected static function parseArgument($item, &$args)
    {
        $item = trim($item,'= ');

        // eg: `name=john`
        if ( strpos($item, '=') ) {
            [$name, $val] =  explode('=', $item);

            // is array. eg: `name=john name=tom`
            if (isset($args[$name])) {
                if (is_array($args[$name])) {
                    $args[$name][] = $val;
                } else {
                    $prev = $args[$name];
                    $args[$name] = [$prev, $val];
                }
            } else {
                $args[$name] = $val;
            }

            // only value. eg: `city`
        } else {
            $args[] = $item;
        }
    }
}
