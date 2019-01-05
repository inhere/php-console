<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace Inhere\Console;

use Inhere\Console\Face\CommandInterface;

/**
 * Class Command
 * @package Inhere\Console
 *
 * ```php
 *  class MyCommand extends Command
 *  {
 *      protected function execute($input, $output)
 *      {
 *          // some logic ...
 *      }
 *  }
 * ```
 */
abstract class Command extends AbstractCommand implements CommandInterface
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

    /*
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
     * @return bool
     * @throws \ReflectionException
     */
    protected function showHelp(): bool
    {
        if (true === parent::showHelp()) {
            return true;
        }

        return $this->showHelpByMethodAnnotations('execute', null, static::aliases());
    }
}
