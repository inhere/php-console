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
     * @return string
     */
    public static function getNullDevice()
    {
        if (Helper::isUnix()) {
            return '/dev/null';
        }

        return 'NUL';
    }

    /**
     * run a command in background
     * @param string $cmd
     */
    public static function execInBackground($cmd)
    {
        if (Helper::isWindows()) {
            pclose(popen('start /B ' . $cmd, 'r'));
        } else {
            exec($cmd . ' > /dev/null &');
        }
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
        $return_var = 1;

        //system
        if (\function_exists('system')) {
            ob_start();
            system($command, $return_var);
            $output = ob_get_contents();
            ob_end_clean();

            // passthru
        } elseif (\function_exists('passthru')) {
            ob_start();
            passthru($command, $return_var);
            $output = ob_get_contents();
            ob_end_clean();
            //exec
        } else {
            if (\function_exists('exec')) {
                exec($command, $output, $return_var);
                $output = implode("\n", $output);

                //shell_exec
            } else {
                if (\function_exists('shell_exec')) {
                    $output = shell_exec($command);
                } else {
                    $output = 'Command execution not possible on this system';
                    $return_var = 0;
                }
            }
        }

        if ($returnStatus) {
            return ['output' => trim($output), 'status' => $return_var];
        }

        return trim($output);
    }

    /**
     * @return string
     */
    public static function getTempDir()
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
