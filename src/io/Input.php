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
     * Input data
     * @var array
     */
    protected $args = [];

    /**
     * Input data
     * @var array
     */
    protected $opts = [];

    /**
     * the script name
     * e.g `./bin/app` OR `bin/cli.php`
     * @var string
     */
    public static $scriptName = '';

    /**
     * the script name
     * e.g `image/packTask` OR `start`
     * @var string
     */
    public static $command = '';

    public function __construct($parseArgv = true, $fillToGlobal = false)
    {
        if ($parseArgv) {
            list($this->args, $this->opts) = self::parseGlobalArgv($fillToGlobal);
        }
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
     * @param $name
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
    public function getArgument($name=null, $default = null)
    {
        return $this->get($name, $default);
    }
    public function getArg($name=null, $default = null)
    {
        return $this->get($name, $default);
    }
    public function get($name=null, $default = null)
    {
        if (null === $name) {
            return $this->args;
        }

        return isset($this->args[$name]) ? $this->args[$name] : $default;
    }

    /**
     * get first argument
     * @return string
     */
    public function getFirstArg()
    {
        return $this->get(0);
    }

    /**
     * get second argument
     * @return string
     */
    public function getSecondArg()
    {
        return $this->get(1);
    }

    /**
     * @param $key
     * @param int $default
     * @return bool
     */
    public function getInt($key, $default = 0)
    {
        $value = $this->get($key);

        return $value === null ? (int)$default : (int)$value;
    }

    /**
     * get bool value form args
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function getBool($key, $default = false)
    {
        if ( !$this->hasArg($key) ) {
            return (bool)$default;
        }

        $value = $this->args[$key];

        return !in_array(strtolower($value), ['0', 'false'], true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// options (eg: -d --help)
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param $name
     * @param null $default
     * @return bool|mixed|null
     */
    public function getOption($name, $default = null)
    {
        return $this->getOpt($name, $default);
    }
    public function getOpt($name, $default = null)
    {
        if ( !$this->hasOpt($name) ) {
            return $default;
        }

        $value = $this->opts[$name];

        // check it is a bool value.
        $tmp = strtolower($value);

        if ( 'false' === $tmp ) {
            return false;
        }

        if ( 'true' === $tmp ) {
            return false;
        }

        return $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasOpt($name)
    {
        return isset($this->opts[$name]);
    }

    /**
     * get option value(bool)
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function boolOpt($key, $default = false)
    {
        return $this->getBoolOpt($key, $default);
    }
    public function getBoolOpt($key, $default = false)
    {
        if ( !$this->hasOpt($key) ) {
            return (bool)$default;
        }

        $value = $this->opts[$key];

        return !in_array(strtolower($value), ['0', 'false'], true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// getter/setter
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @return string
     */
    public function getScriptName()
    {
        return self::$scriptName;
    }

    /**
     * @return string
     */
    public function getScript()
    {
        return self::$scriptName;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return self::$command;
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
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param array $opts
     */
    public function setOpts(array $opts)
    {
        $this->opts = $opts;
    }

    /**
     * @return array
     */
    public function getOpts()
    {
        return $this->opts;
    }

    /**
     * @return resource
     */
    public function getInputStream()
    {
        return $this->inputStream;
    }

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

        self::$scriptName = array_shift($tmp);

        // collect command
        if ( isset($tmp[0]) && $tmp[0]{0} !== '-' && (false === strpos($tmp[0], '=')) ) {
            self::$command = trim(array_shift($tmp), '/');
        }

        $args = $opts = [];

        // parse query params
        // `./bin/app image/packTask start name=john city -s=test --page=23 -d -rf --debug`
        // parse to
        // $args = [ 'name' => 'john', 0 => 'city' ];
        // $opts = [ 'd' => true, 'f' => true, 'r' => true, 's' => 'test', 'debug' => true ]
        if ($tmp) {
            foreach ($tmp as $item) {
                // is a option
                if ( $item{0} === '-' ) {
                    static::parseOption($item, $opts);

                // is a argument
                } else {
                    $item = trim($item,'= ');

                    // eg: `name=john`
                    if ( strpos($item, '=') ) {
                        list($name, $val) =  explode('=', $item);
                        $args[$name] = $val;

                    // only value. eg: `city`
                    } else {
                        $args[] = $item;
                    }
                }
            }

            if ($fillToGlobal) {
                $_REQUEST = $_GET = $args;
            }
        }

        return [$args, $opts];
    }

    /**
     * will parse option, like:
     *
     * ```
     * -s=test --page=23 -d -rf --debug
     * ```
     *
     * to:
     *
     * ```
     * $opts = [
     *  'd' => true,
     *  'f' => true,
     *  'r' => true,
     *  's' => 'test',
     *  'debug' => true
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
            list($name, $val) = explode('=', $item);
            $opts[$name] = $val;

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
}
