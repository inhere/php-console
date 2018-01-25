<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-18
 * Time: 19:27
 */

namespace Inhere\Console\Utils;

/**
 * Class CliUtil
 * @package Inhere\Console\Utils
 */
final class CliUtil
{
    /**
     * get bash is available
     * @return bool
     */
    public static function shIsAvailable(): bool
    {
        // $checkCmd = "/usr/bin/env bash -c 'echo OK'";
        // $shell = 'echo $0';
        $checkCmd = "sh -c 'echo OK'";

        return self::runCommand($checkCmd, false) === 'OK';
    }

    /**
     * get bash is available
     * @return bool
     */
    public static function bashIsAvailable(): bool
    {
        // $checkCmd = "/usr/bin/env bash -c 'echo OK'";
        // $shell = 'echo $0';
        $checkCmd = "bash -c 'echo OK'";

        return self::runCommand($checkCmd, false) === 'OK';
    }

    /**
     * @return string
     */
    public static function getNullDevice(): string
    {
        if (Helper::isUnix()) {
            return '/dev/null';
        }

        return 'NUL';
    }

    /**
     * @return string
     */
    public static function getOutsideIP(): string
    {
        list($code, $output) = ProcessUtil::run('ip addr | grep eth0');

        if ($code === 0 && $output && preg_match('#inet (.*)\/#', $output, $ms)) {
            return $ms[1];
        }

        return 'unknown';
    }

    /**
     * @param string $command
     * @param null|string $logfile
     * @param null|string $user
     * @return mixed
     * @throws \RuntimeException
     */
    public static function exec($command, $logfile = null, $user = null)
    {
        // If should run as another user, we must be on *nix and must have sudo privileges.
        $suDo = '';

        if ($user && Helper::isUnix() && Helper::isRoot()) {
            $suDo = "sudo -u $user";
        }

        // Start execution. Run in foreground (will block).
        $logfile = $logfile ?: self::getNullDevice();

        // Start execution. Run in foreground (will block).
        exec("$suDo $command 1>> \"$logfile\" 2>&1", $dummy, $retVal);

        if ($retVal !== 0) {
            throw new \RuntimeException("command exited with status '$retVal'.");
        }

        return $dummy;
    }

    /**
     * Method to execute a command in the sys
     * Uses :
     * 1. system
     * 2. passthru
     * 3. exec
     * 4. shell_exec
     * @param $command
     * @param bool $returnStatus
     * @return array|string
     */
    public static function runCommand($command, $returnStatus = true)
    {
        $status = 1;

        //system
        if (\function_exists('system')) {
            ob_start();
            system($command, $status);
            $output = ob_get_contents();
            ob_end_clean();

            // passthru
        } elseif (\function_exists('passthru')) {
            ob_start();
            passthru($command, $status);
            $output = ob_get_contents();
            ob_end_clean();
            //exec
        } else {
            if (\function_exists('exec')) {
                exec($command, $output, $status);
                $output = implode("\n", $output);

                //shell_exec
            } else {
                if (\function_exists('shell_exec')) {
                    $output = shell_exec($command);
                } else {
                    $output = 'Command execution not possible on this system';
                    $status = 0;
                }
            }
        }

        if ($returnStatus) {
            return ['output' => trim($output), 'status' => $status];
        }

        return trim($output);
    }

    /**
     * @return string
     */
    public static function getTempDir(): string
    {
        // @codeCoverageIgnoreStart
        if (\function_exists('sys_get_temp_dir')) {
            $tmp = sys_get_temp_dir();
        } elseif (!empty($_SERVER['TMP'])) {
            $tmp = $_SERVER['TMP'];
        } elseif (!empty($_SERVER['TEMP'])) {
            $tmp = $_SERVER['TEMP'];
        } elseif (!empty($_SERVER['TMPDIR'])) {
            $tmp = $_SERVER['TMPDIR'];
        } else {
            $tmp = getcwd();
        }
        // @codeCoverageIgnoreEnd

        return $tmp;
    }

    /**
     * get screen size
     *
     * ```php
     * list($width, $height) = Helper::getScreenSize();
     * ```
     * @from Yii2
     * @param boolean $refresh whether to force checking and not re-use cached size value.
     * This is useful to detect changing window size while the application is running but may
     * not get up to date values on every terminal.
     * @return array|boolean An array of ($width, $height) or false when it was not able to determine size.
     */
    public static function getScreenSize($refresh = false)
    {
        static $size;
        if ($size !== null && !$refresh) {
            return $size;
        }

        if (self::bashIsAvailable()) {
            // try stty if available
            $stty = [];

            if (
                exec('stty -a 2>&1', $stty) &&
                preg_match('/rows\s+(\d+);\s*columns\s+(\d+);/mi', implode(' ', $stty), $matches)
            ) {
                return ($size = [$matches[2], $matches[1]]);
            }

            // fallback to tput, which may not be updated on terminal resize
            if (($width = (int)exec('tput cols 2>&1')) > 0 && ($height = (int)exec('tput lines 2>&1')) > 0) {
                return ($size = [$width, $height]);
            }

            // fallback to ENV variables, which may not be updated on terminal resize
            if (($width = (int)getenv('COLUMNS')) > 0 && ($height = (int)getenv('LINES')) > 0) {
                return ($size = [$width, $height]);
            }
        }

        if (Helper::isOnWindows()) {
            $output = [];
            exec('mode con', $output);

            if (isset($output[1]) && strpos($output[1], 'CON') !== false) {
                return ($size = [
                    (int)preg_replace('~\D~', '', $output[3]),
                    (int)preg_replace('~\D~', '', $output[4])
                ]);
            }
        }

        return ($size = false);
    }

    /**
     * @param string $program
     * @return int|string
     */
    public static function getCpuUsage($program)
    {
        if (!$program) {
            return -1;
        }

        $info = exec('ps aux | grep ' . $program . ' | grep -v grep | grep -v su | awk {"print $3"}');

        return $info;
    }

    /**
     * @param $program
     * @return int|string
     */
    public static function getMemUsage($program)
    {
        if (!$program) {
            return -1;
        }

        $info = exec('ps aux | grep ' . $program . ' | grep -v grep | grep -v su | awk {"print $4"}');

        return $info;
    }
}
