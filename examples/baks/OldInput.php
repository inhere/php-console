<?php

namespace inhere\console\examples\baks;

/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/29
 * Time: 下午10:16
 */
class OldInput
{

    /////////////////////////////////////////////////////////////////////////////////////////
    /// argument and option parser
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param bool $fillToGlobal
     * @return array
     */
    public static function parseGlobalArgv($fillToGlobal = false)
    {
        // eg: `./bin/app server name=john city -s=test --page=23 -d -rf --debug`
        // eg: `php cli.php server name=john city -s=test --page=23 -d -rf --debug`
        global $argv;

        $tmp = $argv;
        $fullScript = implode(' ', $tmp);
        $script = array_shift($tmp);

        $args = $opts = [];

        // parse query params
        // `./bin/app server start name=john city=chengdu -s=test --page=23 -d -rf --debug --task=off`
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

        // collect command `server`
        $command = isset($args[0]) ? array_shift($args) : '';

        unset($tmp);
        return [$fullScript, $script, $command, $args, $opts];
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
        if (strpos($item, '=')) {
            $item = trim($item, '-= ');
            list($name, $val) = explode('=', $item);
            $tVal = strtolower($val);

            // check it is a bool value.
            if ($tVal === 'on' || $tVal === 'yes' || $tVal === 'true') {
                $opts[$name] = true;
            } elseif ($tVal === 'off' || $tVal === 'no' || $tVal === 'false') {
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
                $item = trim($item, '-');
                foreach (str_split($item) as $char) {
                    $opts[$char] = true;
                }

                // is a long option. eg: `--debug`
            } else {
                $item = trim($item, '-');
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
        $item = trim($item, '= ');

        // eg: `name=john`
        if (strpos($item, '=')) {
            list($name, $val) = explode('=', $item);

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
