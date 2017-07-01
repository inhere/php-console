<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace inhere\console;

use inhere\console\io\Input;
use inhere\console\io\Output;

/**
 * Class Command
 * @package inhere\console
 */
abstract class Command extends AbstractCommand
{
    /**
     * run command
     * @return int
     */
    public function run()
    {
        // load input definition
//        $this->configure();

        if ($this->input->sameOpt(['h','help'])) {
            return $this->showHelp();
        }

        $status = 0;

        try {
            App::fire(App::ON_BEFORE_EXEC, [$this]);

            $this->beforeRun();
            $status = $this->execute($this->input, $this->output);
            $this->afterRun();

            App::fire(App::ON_AFTER_EXEC, [$this]);

        } catch (\Throwable $e) {
            App::fire(App::ON_EXEC_ERROR, [$e, $this]);
            $this->handleRuntimeException($e);
        }

        return $status;
    }

    /**
     * do execute
     * @param  Input $input
     * @param  Output $output
     * @return int
     */
    abstract protected function execute($input, $output);

    /**
     * configure
     */
    protected function configure()
    {
        $this
            ->createDefinition()
            ->addArgument('test')
            ->addOption('test');
    }

    /**
     * @return int
     */
    public function showHelp()
    {
        return $this->showHelpByMethodAnnotation('execute');
    }
}
