<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/30
 * Time: 下午7:49
 */

namespace inhere\console\utils;

/**
 * Class OptArgParse - console argument and option parse
 * @package inhere\console\utils
 */
final class OptArgParse
{
    /**
     * These words will be as a Boolean value
     */
    const TRUE_WORDS = '|on|yes|true|';
    const FALSE_WORDS = '|off|no|false|';

    /**
     * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
     *
     * eg:
     *
     * ```
     * php cli.php server start name=john city=chengdu -s=test --page=23 -d -rf --debug --task=off -y=false -D -e dev -v vvv
     * ```
     *
     * ```php
     * $result = OptArgParse::byArgv($_SERVER['argv']);
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
     * @param array $params
     * @param array $noValues List of parameters without values(bool option keys)
     * @param bool $mergeOpts Whether merge short-opts and long-opts
     * @return array
     */
    public static function byArgv(array $params, array $noValues = [], $mergeOpts = false): array
    {
        $args = $sOpts = $lOpts = [];

        while (list(,$p) = each($params)) {
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

                // fix: allow empty string ''
                if ($value === true && $nxp !== false && (!$nxp || $nxp{0} !== '-') && !in_array($opt, $noValues, true)) {
                    list(,$value) = each($params);

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

        if ($mergeOpts) {
            return [$args, array_merge($sOpts, $lOpts)];
        }

        return [$args, $sOpts, $lOpts];
    }

    /**
     * parse custom array params
     *
     * ```php
     * $result = OptArgParse::byArray([
     *  'arg' => 'val',
     *  '--lp' => 'val2',
     *  '--s' => 'val3',
     * ]);
     * ```
     *
     * @param array $params
     * @return array
     */
    public static function byArray(array $params)
    {
        $args = $sOpts = $lOpts = [];

        foreach ($params as $key => $value) {
            if ($key === '--' || $key === '-') {
                continue;
            }

            if (0 === strpos($key, '--')) {
                $lOpts[substr($key, 2)] = $value;
            } elseif ('-' === $key[0]) {
                $sOpts[substr($key, 1)] = $value;
            } else {
                $args[$key] = $value;
            }
        }

        return [$args, $sOpts, $lOpts];
    }

    /**
     *
     * ```php
     * $result = OptArgParse::byString('foo --bar="foobar"');
     * ```
     * @todo ...
     * @param string $string
     */
    public static function byString(string $string)
    {

    }

    /**
     * @param string|bool $val
     * @param bool $enable
     * @return bool|mixed
     */
    public static function filterBool($val, $enable = true)
    {
        if ($enable) {
            if (is_bool($val) || is_numeric($val)) {
                return $val;
            }

            $tVal = strtolower($val);

            // check it is a bool value.
            if (false !== strpos(self::TRUE_WORDS, "|$tVal|")) {
                return true;
            }

            if (false !== strpos(self::FALSE_WORDS, "|$tVal|")) {
                return false;
            }
        }

        return $val;
    }

    /**
     * Escapes a token through escapeshellarg if it contains unsafe chars.
     *
     * @param string $token
     * @return string
     */
    public static function escapeToken($token)
    {
        return preg_match('{^[\w-]+$}', $token) ? $token : escapeshellarg($token);
    }
}