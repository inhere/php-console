<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-22
 * Time: 11:44
 */

namespace Inhere\Console\Examples\Controllers;

use Inhere\Console\Controller;
use Inhere\Console\Utils\ProcessUtil;

/**
 * Class ProcessController
 * @package Inhere\Console\Examples\Controllers
 */
class ProcessController extends Controller
{
    protected static $name = 'process';

    protected static $description = 'Some simple process to create and use examples';

    protected static function commandAliases()
    {
        return [
            'cpr' => 'childProcess',
            'mpr' => 'multiProcess',
            'dr' => 'daemonRun',
        ];
    }

    /**
     * simple process example for child-process
     */
    public function runScriptCommand()
    {
        $script = '<?php echo "foo"; ?>';


    }

    /**
     * simple process example for child-process
     */
    public function childProcessCommand()
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
     */
    public function daemonRunCommand()
    {
        $ret = ProcessUtil::daemonRun(function ($pid){
            $this->output->info("will running background by new process: $pid");
        });

        if ($ret === false) {
            $this->output->liteError('current env is not support process create.');
        }
    }

    /**
     * simple process example for multi-process
     * @options
     *
     */
    public function multiProcessCommand()
    {

    }
}
