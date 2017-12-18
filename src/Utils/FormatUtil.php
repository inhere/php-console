<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-18
 * Time: 19:34
 */

namespace Inhere\Console\Utils;

/**
 * Class FormatUtil
 * @package Inhere\Console\Utils
 */
final class FormatUtil
{
    /**
     * to camel
     * @param string $name
     * @return mixed|string
     */
    public static function camelCase($name)
    {
        $name = trim($name, '-_');

        // convert 'first-second' to 'firstSecond'
        if (strpos($name, '-')) {
            $name = ucwords(str_replace('-', ' ', $name));
            $name = str_replace(' ', '', lcfirst($name));
        }

        return $name;
    }

    /**
     * @param array $options
     * @return array
     */
    public static function commandOptions(array $options)
    {
        // e.g '-h, --help'
        $hasShort = (bool)strpos(implode(array_keys($options), ''), ',');

        if (!$hasShort) {
            return $options;
        }

        $formatted = [];
        foreach ($options as $name => $des) {
            if (!strpos($name, ',')) {
                $name = '    ' . $name;
            }

            $formatted[$name] = $des;
        }

        return $formatted;
    }

    /**
     * 计算并格式化资源消耗
     * @param int $startTime
     * @param int|float $startMem
     * @param array $info
     * @return array
     */
    public static function runtime($startTime, $startMem, array $info = [])
    {
        $info['startTime'] = $startTime;
        $info['endTime'] = microtime(true);
        $info['endMemory'] = memory_get_usage(true);

        // 计算运行时间
        $info['runtime'] = number_format(($info['endTime'] - $startTime) * 1000, 3) . 'ms';

        if ($startMem) {
            $startMem = array_sum(explode(' ', $startMem));
            $endMem = array_sum(explode(' ', $info['endMemory']));

            $info['memory'] = number_format(($endMem - $startMem) / 1024, 3) . 'kb';
        }

        $peakMem = memory_get_peak_usage() / 1024 / 1024;
        $info['peakMemory'] = number_format($peakMem, 3) . 'Mb';

        return $info;
    }

    /**
     * @param float $memory
     * @return string
     * ```
     * FormatUtil::memoryUsage(memory_get_usage(true));
     * ```
     */
    public static function memoryUsage($memory)
    {
        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.1f GiB', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.1f MiB', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%d KiB', $memory / 1024);
        }

        return sprintf('%d B', $memory);
    }

    /**
     * format Timestamp
     * @param  int $secs
     * @return string
     */
    public static function timestamp($secs)
    {
        static $timeFormats = [
            [0, '< 1 sec'],
            [1, '1 sec'],
            [2, 'secs', 1],
            [60, '1 min'],
            [120, 'mins', 60],
            [3600, '1 hr'],
            [7200, 'hrs', 3600],
            [86400, '1 day'],
            [172800, 'days', 86400],
        ];

        foreach ($timeFormats as $index => $format) {
            if ($secs >= $format[0]) {
                if ((isset($timeFormats[$index + 1]) && $secs < $timeFormats[$index + 1][0])
                    || $index === \count($timeFormats) - 1
                ) {
                    if (2 === \count($format)) {
                        return $format[1];
                    }

                    return floor($secs / $format[2]) . ' ' . $format[1];
                }
            }
        }

        return date('Y-m-d H:i:s', $secs);
    }
}