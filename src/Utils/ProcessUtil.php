<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-21
 * Time: 13:37
 */

namespace Inhere\Console\Utils;

/**
 * Class ProcessUtil
 * @package Inhere\Console\Utils
 */
class ProcessUtil
{
    /**
     * @return bool
     */
    public static function isSupported()
    {
        return !Helper::isWindows() && \function_exists('pcntl_fork');
    }

    /**
     * Daemon, detach and run in the background
     * @param \Closure|null $beforeQuit
     * @return int Return new process PID
     */
    public static function daemonRun(\Closure $beforeQuit = null)
    {
        if (!self::isSupported()) {
            return 0;
        }

        // umask(0);
        $pid = pcntl_fork();

        switch ($pid) {
            case 0: // at new process
                $pid = getmypid(); // can also use: posix_getpid()

                if (posix_setsid() < 0) {
                    Show::error('posix_setsid() execute failed! exiting');
                }

                // chdir('/');
                // umask(0);
                break;

            case -1: // fork failed.
                Show::error('Fork new process is failed! exiting');
                break;

            default: // at parent
                if ($beforeQuit) {
                    $beforeQuit($pid);
                }

                exit;
        }

        return $pid;
    }

    /**
     * run a command in background
     * @param string $cmd
     */
    public static function runInBackground($cmd)
    {
        if (Helper::isWindows()) {
            pclose(popen('start /B ' . $cmd, 'r'));
        } else {
            exec($cmd . ' > /dev/null &');
        }
    }

    /**
     * @see ProcessUtil::forks()
     * @param int $number
     * @param callable|null $onStart
     * @param callable|null $onError
     * @return array|false
     */
    public static function multi(int $number, callable $onStart = null, callable $onError = null)
    {
        return self::forks($number, $onStart, $onError);
    }

    /**
     * fork/create multi child processes.
     * @param int $number
     * @param callable|null $onStart Will running on the child processes.
     * @param callable|null $onError
     * @return array|false
     */
    public static function forks(int $number, callable $onStart = null, callable $onError = null)
    {
        if ($number <= 0) {
            return false;
        }

        if (!self::isSupported()) {
            return false;
        }

        $pidAry = [];

        for ($id = 0; $id < $number; $id++) {
            $info = self::fork($onStart, $onError, $id);
            $pidAry[$info['pid']] = $info;
        }

        return $pidAry;
    }

    /**
     * @see ProcessUtil::fork()
     * @param callable|null $onStart
     * @param callable|null $onError
     * @param int $id
     * @return array|false
     */
    public static function create(callable $onStart = null, callable $onError = null, $id = 0)
    {
        return self::fork($onStart, $onError, $id);
    }

    /**
     * fork/create a child process.
     * @param callable|null $onStart Will running on the child process start.
     * @param callable|null $onError
     * @param int $id The process index number. will use `forks()`
     * @return array|false
     */
    public static function fork(callable $onStart = null, callable $onError = null, $id = 0)
    {
        if (!self::isSupported()) {
            return false;
        }

        $info = [];
        $pid = pcntl_fork();

        // at parent, get forked child info
        if ($pid > 0) {
            $info = [
                'id' => $id,
                'pid' => $pid,
                'startTime' => time(),
            ];
        } elseif ($pid === 0) { // at child
            $pid = getmypid();

            if ($onStart) {
                $onStart($pid, $id);
            }
        } else {
            if ($onError) {
                $onError($pid);
            }

            Show::error('Fork child process failed! exiting.');
        }

        return $info;
    }

    /**
     * wait child exit.
     * @param  callable $onExit
     * @return bool
     */
    public static function wait(callable $onExit)
    {
        if (!self::isSupported()) {
            return false;
        }

        $status = null;

        //pid<0：子进程都没了
        //pid>0：捕获到一个子进程退出的情况
        //pid=0：没有捕获到退出的子进程
        while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) >= 0) {
            if ($pid) {
                // ... (callback, pid, exitCode, status)
                $onExit($pid, pcntl_wexitstatus($status), $status);
            } else {
                usleep(50000);
            }
        }

        return true;
    }

    /**
     * Stops all running children
     * @param array $children
     * [
     *  'pid' => [
     *      'id' => worker id
     *  ],
     *  ... ...
     * ]
     * @param int $signal
     * @param array $events
     * [
     *   'beforeStops' => function ($sigText) {
     *      echo "Stopping processes({$sigText}) ...\n";
     *  },
     *  'beforeStop' => function ($pid, $info) {
     *      echo "Stopping process(PID:$pid)\n";
     *  }
     * ]
     * @return bool
     */
    public static function stopChildren(array $children, $signal = SIGTERM, array $events = [])
    {
        if (!$children) {
            return false;
        }

        if (!self::isSupported()) {
            return false;
        }

        $events = array_merge([
            'beforeStops' => null,
            'beforeStop' => null,
        ], $events);
        $signals = [
            SIGINT => 'SIGINT(Ctrl+C)',
            SIGTERM => 'SIGTERM',
            SIGKILL => 'SIGKILL',
        ];

        if ($cb = $events['beforeStops']) {
            $cb($signal, $signals[$signal]);
        }

        foreach ($children as $pid => $child) {
            if ($cb = $events['beforeStop']) {
                $cb($pid, $child);
            }

            // send exit signal.
            self::sendSignal($pid, $signal);
        }

        return true;
    }

    /**************************************************************************************
     * basic signal methods
     *************************************************************************************/

    /**
     * send kill signal to the process
     * @param int $pid
     * @param bool $force
     * @param int $timeout
     * @return bool
     */
    public static function kill($pid, $force = false, $timeout = 3)
    {
        return self::sendSignal($pid, $force ? SIGKILL : SIGTERM, $timeout);
    }

    /**
     * Do shutdown process and wait it exit.
     * @param  int $pid Master Pid
     * @param int $signal
     * @param int $waitTime
     * @param null $error
     * @param string $name
     * @return bool
     */
    public static function killAndWait($pid, $signal = SIGTERM, $waitTime = 30, &$error = null, $name = 'process')
    {
        // $opts = array_merge([], $opts);
        // do stop
        if (!self::kill($signal)) {
            $error = "Send stop signal to the $name(PID:$pid) failed!";

            return false;
        }

        // not wait, only send signal
        if ($waitTime <= 0) {
            $error = "The $name process stopped";

            return true;
        }

        $startTime = time();
        Show::write('Stopping .', false);

        // wait exit
        while (true) {
            if (!self::isRunning($pid)) {
                break;
            }

            if (time() - $startTime > $waitTime) {
                $error = "Stop the $name(PID:$pid) failed(timeout)!";
                break;
            }

            Show::write('.', false);
            sleep(1);
        }

        return true;
    }

    /**
     * 杀死所有进程
     * @param $name
     * @param int $sigNo
     * @return string
     */
    public static function killByName($name, $sigNo = 9)
    {
        $cmd = 'ps -eaf |grep "' . $name . '" | grep -v "grep"| awk "{print $2}"|xargs kill -' . $sigNo;

        return exec($cmd);
    }


    /**
     * @param int $pid
     * @return bool
     */
    public static function isRunning($pid)
    {
        return ($pid > 0) && @posix_kill($pid, 0);
    }

    /**
     * exit
     * @param int $code
     */
    public static function quit($code = 0)
    {
        exit((int)$code);
    }

    /**************************************************************************************
     * process signal handle
     *************************************************************************************/

    /**
     * send signal to the process
     * @param int $pid
     * @param int $signal
     * @param int $timeout
     * @return bool
     */
    public static function sendSignal($pid, $signal, $timeout = 0)
    {
        if ($pid <= 0) {
            return false;
        }

        if (!self::isSupported()) {
            return false;
        }

        // do send
        if ($ret = posix_kill($pid, $signal)) {
            return true;
        }

        // don't want retry
        if ($timeout <= 0) {
            return $ret;
        }

        // failed, try again ...

        $timeout = $timeout > 0 && $timeout < 10 ? $timeout : 3;
        $startTime = time();

        // retry stop if not stopped.
        while (true) {
            // success
            if (!$isRunning = @posix_kill($pid, 0)) {
                break;
            }

            // have been timeout
            if ((time() - $startTime) >= $timeout) {
                return false;
            }

            // try again kill
            $ret = posix_kill($pid, $signal);
            usleep(10000);
        }

        return $ret;
    }

    /**
     * install signal
     * @param  int $signal e.g: SIGTERM SIGINT(Ctrl+C) SIGUSR1 SIGUSR2 SIGHUP
     * @param  callable $handler
     * @return bool
     */
    public static function installSignal($signal, callable $handler)
    {
        return pcntl_signal($signal, $handler, false);
    }

    /**
     * dispatch signal
     * @return bool
     */
    public static function dispatchSignal()
    {
        // receive and dispatch sig
        return pcntl_signal_dispatch();
    }

    /**************************************************************************************
     * some help method
     *************************************************************************************/

    /**
     * get current process id
     * @return int
     */
    public static function getPid()
    {
        return getmypid();// or use posix_getpid()
    }

    /**
     * get Pid from File
     * @param string $file
     * @param bool $checkLive
     * @return int
     */
    public static function getPidByFile($file, $checkLive = false)
    {
        if ($file && file_exists($file)) {
            $pid = (int)file_get_contents($file);

            // check live
            if ($checkLive && self::isRunning($pid)) {
                return $pid;
            }

            unlink($file);
        }

        return 0;
    }

    /**
     * Get unix user of current process.
     * @return array
     */
    public static function getCurrentUser()
    {
        return posix_getpwuid(posix_getuid());
    }

    /**
     * @param int $seconds
     * @param callable $handler
     */
    public static function afterDo($seconds, callable $handler)
    {
        /*
        self::signal(SIGALRM, function () {
            static $i = 0;
            echo "#{$i}\talarm\n";
            $i++;
            if ($i > 20) {
                pcntl_alarm(-1);
            }
        });*/
        self::installSignal(SIGALRM, $handler);

        // self::alarm($seconds);
        pcntl_alarm($seconds);
    }

    /**
     * Set process title.
     * @param string $title
     * @return bool
     */
    public static function setName($title)
    {
        return self::setTitle($title);
    }

    /**
     * Set process title.
     * @param string $title
     * @return bool
     */
    public static function setTitle($title)
    {
        if (Helper::isMac()) {
            return false;
        }

        if (\function_exists('cli_set_process_title')) {
            cli_set_process_title($title);
        }

        return true;
    }

    /**
     * Set unix user and group for current process script.
     * @param string $user
     * @param string $group
     * @throws \RuntimeException
     */
    public static function changeScriptOwner($user, $group = '')
    {
        $uInfo = posix_getpwnam($user);

        if (!$uInfo || !isset($uInfo['uid'])) {
            throw new \RuntimeException("User ({$user}) not found.");
        }

        $uid = (int)$uInfo['uid'];

        // Get gid.
        if ($group) {
            if (!$gInfo = posix_getgrnam($group)) {
                throw new \RuntimeException("Group {$group} not exists", -300);
            }

            $gid = (int)$gInfo['gid'];
        } else {
            $gid = (int)$uInfo['gid'];
        }

        if (!posix_initgroups($uInfo['name'], $gid)) {
            throw new \RuntimeException("The user [{$user}] is not in the user group ID [GID:{$gid}]", -300);
        }

        posix_setgid($gid);

        if (posix_geteuid() !== $gid) {
            throw new \RuntimeException("Unable to change group to {$user} (UID: {$gid}).", -300);
        }

        posix_setuid($uid);

        if (posix_geteuid() !== $uid) {
            throw new \RuntimeException("Unable to change user to {$user} (UID: {$uid}).", -300);
        }
    }

}
