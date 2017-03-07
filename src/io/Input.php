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
 * e.g:
 *     ./bin/app image/packTask name=john -d -s=test --debug=true
 *     php bin/cli.php start name=john -d -s=test --debug=true
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
    protected $data = [];

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

    public function __construct($parseArgv = true, $fixServer = false, $fillToGlobal = false)
    {
        if ($parseArgv) {
            $this->data = self::parseGlobalArgv($fixServer, $fillToGlobal);
        }
    }

    /**
     * 读取输入信息
     * @param  string $message  若不为空，则先输出文本消息
     * @param  bool   $nl       true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public function read($message = null, $nl = false)
    {
        fwrite(STDOUT, $message . ($nl ? "\n" : ''));

        return trim(fgets($this->inputStream));
    }

    /**
     * @param null|string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name=null, $default = null)
    {
        if (null === $name) {
            return $this->data;
        }

        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }
    public function getOption($name=null, $default = null)
    {
        return $this->get($name, $default);
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
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function getBool($key, $default = false)
    {
        $value = $this->get($key);

        return $value === null ? (bool)$default : !in_array($value, ['0', 0, 'false', false], true);
    }

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
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return resource
     */
    public function getInputStream()
    {
        return $this->inputStream;
    }

    /**
     * @param bool $fixServer
     * @param bool $fillToGlobal
     * @return array
     */
    public static function parseGlobalArgv($fixServer = false, $fillToGlobal = false)
    {
        // ./bin/app image/packTask start name=john -d -s=test --debug=true
        // php bin/cli.php image/packTask start name=john -d -s=test --debug=true
        global $argv;
        $args = $argv;

        self::$scriptName = array_shift($args);

        if ($fixServer) {
            // fixed: '/home' is not equals to '/home/'
            if (isset($_SERVER['REQUEST_URI'])) {
                $_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'],'/ ');
            }

            // fixed: PHP_SELF = 'index.php', it is should be '/index.php'
            if (isset($_SERVER['PHP_SELF'])) {
                $_SERVER['PHP_SELF'] = '/' . ltrim($_SERVER['PHP_SELF'],'/ ');
            }


            // $_SERVER['PHP_SELF'] = self::$scriptName;
            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = '/';
        }

        // collect command
        if ( isset($args[0]) && $args[0]{0} !== '-' && strpos($args[0], '=') === false ) {
            self::$command = trim(array_shift($args), '/');

            if ($fixServer) {
                $_SERVER['REQUEST_URI'] .= self::$command;
            }
        }

        $data = [];

        // parse query params
        // ./bin/app image/packTask start name=john -d -s=test --debug=true
        // parse to
        // ./bin/app image/packTask?start&name=john&d&s=test&debug=true
        if ($args) {
            $args = array_map(function($val){
                return trim($val,'- ');
            }, $args);

            parse_str(implode('&',$args), $data);

            if ($fillToGlobal) {
                $_REQUEST = $_GET = $data;
            }
        }

        return $data;
    }
}
