<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace Inhere\Console;

use Inhere\Console\Contract\CommandInterface;
use ReflectionException;

/**
 * Class Command
 *
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
    /**
     * @var Command
     */
    protected $parent;

    /**
     * @var Command[]
     */
    protected $commands = [];

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
     *
     * @return bool
     * @throws ReflectionException
     */
    protected function showHelp(): bool
    {
        // help info has been build by input definition.
        if (true === parent::showHelp()) {
            return true;
        }

        $execMethod = 'execute';
        $cmdAliases = static::aliases();

        $this->logf(Console::VERB_CRAZY, "display help info for the command: %s", static::$name);

        return $this->showHelpByMethodAnnotations($execMethod, '', $cmdAliases) !== 0;
    }
}
