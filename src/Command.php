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
    /*
     * do execute
     * @param  Input $input
     * @param  Output $output
     * @return int
     */
    // protected function execute($input, $output)
    // {
    //      // something logic ...
    // }

    /**
     * configure
     */
    // protected function configure()
    // {
        // $this
        //     ->createDefinition()
        //     ->addArgument('test')
        //     ->addOption('test');
    // }

    /**
     * @return int
     */
    protected function showHelp()
    {
        if (true === parent::showHelp()) {
            return 0;
        }

        return $this->showHelpByMethodAnnotation('execute');
    }
}
