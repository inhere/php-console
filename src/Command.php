<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace Inhere\Console;

use Inhere\Console\Base\AbstractCommand;

/**
 * Class Command
 * @package Inhere\Console
 */
abstract class Command extends AbstractCommand
{
    /*
     * do execute
     * @param  \Inhere\Console\IO\Input $input
     * @param  \Inhere\Console\IO\Output $output
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
