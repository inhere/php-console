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
     * @var array
     */
    public static $signalMap = [
        SIGINT => 'SIGINT(Ctrl+C)',
        SIGTERM => 'SIGTERM',
        SIGKILL => 'SIGKILL',
    ];

    /**
     * @return bool
     */
    public static function pcntlIsEnabled(): bool
    {
        return !Helper::isWindows() && \function_exists('pcntl_fork');
    }

    /**
     * @return bool
     */
    public static function posixIsEnabled(): bool
    {
        return !Helper::isWindows() && \function_exists('posix_kill');
    }

    /**
     * run a command. it is support windows
     * @param string $command
     * @param string|null $cwd
     * @return array
     */
    public static function run(string $command, string $cwd = null): array
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin - read channel
            1 => ['pipe', 'w'], // stdout - write channel
            2 => ['pipe', 'w'], // stdout - error channel
            3 => ['pipe', 'r'], // stdin - This is the pipe we can feed the password into
        ];

        $process = proc_open($command, $descriptors, $pipes, $cwd);

        if (!\is_resource($process)) {
            throw new \RuntimeException("Can't open resource with proc_open.");
        }

        // Nothing to push to input.
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        // TODO: Write passphrase in pipes[3].
        fclose($pipes[3]);

        // Close all pipes before proc_close! $code === 0 is success.
        $code = proc_close($process);

        return [$code, $output, $error];
    }

    /**
     * Daemon, detach and run in the background
     * @param \Closure|null $beforeQuit
     * @return int Return new process PID
     */
    public static function daemonRun(\Closure $beforeQuit = null): int
    {
        if (!self::pcntlIsEnabled()) {
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
     * @return int|string
     */
    public static function runInBackground(string $cmd)
    {
        if (Helper::isWindows()) {
            $ret = pclose(popen('start /B ' . $cmd, 'r'));
        } else {
            $ret = exec($cmd . ' > /dev/null &');
        }

        return $ret;
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

        if (!self::pcntlIsEnabled()) {
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
        if (!self::pcntlIsEnabled()) {
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
    public static function wait(callable $onExit): bool
    {
        if (!self::pcntlIsEnabled()) {
            return false;
        }

        $status = null;

        // pid < 0：子进程都没了
        // pid > 0：捕获到一个子进程退出的情况
        // pid = 0：没有捕获到退出的子进程
        while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) >= 0) {
            if ($pid) {
                // handler(pid, exitCode, status)
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
    public static function stopWorkers(array $children, int $signal = SIGTERM, array $events = []): bool
    {
        if (!$children) {
            return false;
        }

        if (!self::pcntlIsEnabled()) {
            return false;
        }

        $events = array_merge([
            'beforeStops' => null,
            'beforeStop' => null,
        ], $events);

        if ($cb = $events['beforeStops']) {
            $cb($signal, self::$signalMap[$signal]);
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
    public static function kill(int $pid, bool $force = false, int $timeout = 3): bool
    {
        return self::sendSignal($pid, $force ? SIGKILL : SIGTERM, $timeout);
    }

    /**
     * Do shutdown process and wait it exit.
     * @param int $pid Master Pid
     * @param bool $force
     * @param int $waitTime
     * @param null $error
     * @param string $name
     * @return bool
     */
    public static function killAndWait(
        int $pid, &$error = null, $name = 'process', bool $force = false, int $waitTime = 10
    ): bool
    {
        // do stop
        if (!self::kill($pid, $force)) {
            $error = "Send stop signal to the $name(PID:$pid) failed!";

            return false;
        }

        // not wait, only send signal
        if ($waitTime <= 0) {
            $error = "The $name process stopped";

            return true;
        }

        $startTime = time();
        echo 'Stopping .';

        // wait exit
        while (true) {
            if (!self::isRunning($pid)) {
                break;
            }

            if (time() - $startTime > $waitTime) {
                $error = "Stop the $name(PID:$pid) failed(timeout)!";
                break;
            }

            echo '.';
            sleep(1);
        }

        if ($error) {
            return false;
        }

        Show::color(' OK');
        return true;
    }

    /**
     * 杀死所有进程
     * @param $name
     * @param int $sigNo
     * @return string
     */
    public static function killByName(string $name, int $sigNo = 9): string
    {
        $cmd = 'ps -eaf |grep "' . $name . '" | grep -v "grep"| awk "{print $2}"|xargs kill -' . $sigNo;

        return exec($cmd);
    }


    /**
     * @param int $pid
     * @return bool
     */
    public static function isRunning(int $pid): bool
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
    public static function sendSignal(int $pid, int $signal, int $timeout = 0): bool
    {
        if ($pid <= 0 || !self::posixIsEnabled()) {
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
    public static function installSignal($signal, callable $handler): bool
    {
        if (!self::pcntlIsEnabled()) {
            return false;
        }

        return pcntl_signal($signal, $handler, false);
    }

    /**
     * dispatch signal
     * @return bool
     */
    public static function dispatchSignal(): bool
    {
        if (!self::pcntlIsEnabled()) {
            return false;
        }

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
    public static function getPid(): int
    {
        return getmypid();// or use posix_getpid()
    }

    /**
     * get Pid from File
     * @param string $file
     * @param bool $checkLive
     * @return int
     */
    public static function getPidByFile(string $file, $checkLive = false): int
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
    public static function getCurrentUser(): array
    {
        return posix_getpwuid(posix_getuid());
    }

    /**
     * @param int $seconds
     * @param callable $handler
     * @return bool|int
     */
    public static function afterDo(int $seconds, callable $handler)
    {
        if (!self::pcntlIsEnabled()) {
            return false;
        }

        /* self::signal(SIGALRM, function () {
            static $i = 0;
            echo "#{$i}\talarm\n";
            $i++;
            if ($i > 20) {
                pcntl_alarm(-1);
            }
        });*/
        self::installSignal(SIGALRM, $handler);

        // self::alarm($seconds);
        return pcntl_alarm($seconds);
    }

    /**
     * Set process title.
     * @param string $title
     * @return bool
     */
    public static function setName(string $title): bool
    {
        return self::setTitle($title);
    }

    /**
     * Set process title.
     * @param string $title
     * @return bool
     */
    public static function setTitle(string $title): bool
    {
        if (Helper::isMac()) {
            return false;
        }

        if (\function_exists('cli_set_process_title')) {
            return cli_set_process_title($title);
        }

        return true;
    }

    /**
     * Set unix user and group for current process script.
     * @param string $user
     * @param string $group
     * @throws \RuntimeException
     */
    public static function changeScriptOwner(string $user, string $group = '')
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
