<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-22
 * Time: 11:44
 */

namespace Inhere\Console\Examples\Controller;

use Inhere\Console\Controller;
use RuntimeException;
use Toolkit\Sys\ProcessUtil;
use Toolkit\Sys\Sys;
use function is_resource;

/**
 * Class ProcessController
 * @package Inhere\Console\Examples\Controller
 */
class ProcessController extends Controller
{
    protected static $name = 'process';

    protected static $description = 'Some simple process to create and use examples';

    protected static function commandAliases(): array
    {
        return [
            'cpr' => 'childProcess',
            'mpr' => 'multiProcess',
            'dr' => 'daemonRun',
            'rs' => 'runScript',
            'rb' => 'runInBackground',
        ];
    }

    /**
     * simple process example for child-process
     */
    public function runScriptCommand(): void
    {
        /*$script = '<?php echo "foo"; ?>';*/
        $script = '<?php print_r($_SERVER); ?>';

        // $tmpDir = CliUtil::getTempDir();
        // $tmpFile = $tmpDir . '/' . md5($script) . '.php';
        // file_put_contents($tmpFile, $script);

        $descriptorSpec = [
            0 => ['pipe', 'r'],  // 标准输入，子进程从此管道中读取数据
            1 => ['pipe', 'w'],  // 标准输出，子进程向此管道中写入数据
            2 => ['file', $this->app->getRootPath() . '/examples/tmp/error-output.log', 'a'] // 标准错误，写入到一个文件
        ];

        $process = proc_open('php', $descriptorSpec, $pipes);

        if (is_resource($process)) {
            // $pipes 现在看起来是这样的：
            // 0 => 可以向子进程标准输入写入的句柄
            // 1 => 可以从子进程标准输出读取的句柄
            // 错误输出将被追加到文件 error-output.txt

            fwrite($pipes[0], $script);
            fclose($pipes[0]);

            $result = stream_get_contents($pipes[1]);

            fclose($pipes[1]);

            $this->write("RESULT:\n" . $result);

            // 切记：在调用 proc_close 之前关闭所有的管道以避免死锁。
            $retVal = proc_close($process);

            echo "command returned $retVal\n";
        }
    }

    /**
     * simple process example for child-process
     */
    public function childProcessCommand(): void
    {
        $ret = ProcessUtil::create(function ($pid) {
            echo "print in process $pid";

            sleep(5);
        });

        if ($ret === false) {
            $this->output->liteError('current env is not support process create.');
        }
    }

    /**
     * simple process example for daemon run
     * @throws RuntimeException
     */
    public function daemonRunCommand(): void
    {
        $ret = ProcessUtil::daemonRun(function ($pid) {
            $this->output->info("will running background by new process: $pid");
        });

        if ($ret === 0) {
            $this->output->liteError('current env is not support process create.');
        }
    }

    /**
     * simple process example for run In Background
     */
    public function runInBackgroundCommand(): void
    {
        $script = '<?php print_r($_SERVER); ?>';
        $ret = Sys::execInBackground("php $script");

        if ($ret === false) {
            $this->output->liteError('current env is not support process create.');
        }
    }

    /**
     * simple process example for multi-process
     * @options
     *
     */
    public function multiProcessCommand(): void
    {
    }
}
