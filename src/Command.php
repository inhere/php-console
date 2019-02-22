<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace Inhere\Console;

use Inhere\Console\Contract\CommandInterface;

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
abstract class Command extends AbstractHandler implements CommandInterface
{
    /*
     * Do execute command
     */
    // protected function execute($input, $output)
    // {
    //      // something logic ...
    // }

    /*
     * Configure command
     */
    // protected function configure()
    // {
    // $this
    //     ->createDefinition()
    //     ->addArgument('test')
    //     ->addOption('test');
    // }

    /**
     * Show help information
     * @return bool
     * @throws \ReflectionException
     */
    protected function showHelp(): bool
    {
        // help info has been build by input definition.
        if (true === parent::showHelp()) {
            return true;
        }

        return $this->showHelpByMethodAnnotations('execute', '', static::aliases());
    }
}
