<?php declare(strict_types=1);
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
    public const METHOD = 'execute';

    /**
     * @var Command
     */
    protected $parent;

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
    //     $this
    //      ->createDefinition()
    //      ->addArgument('test')
    //      ->addOption('test');
    // }

    /**
     * @param Command $parent
     */
    public function setParent(Command $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return $this
     */
    public function getRootCommand(): Command
    {
        if ($this->parent) {
            return $this->parent->getRootCommand();
        }

        return $this;
    }

    /**
     * @return Command|null
     */
    public function getParent(): ?Command
    {
        return $this->parent;
    }

    /**
     * Show help information
     *
     * @return bool
     */
    protected function showHelp(): bool
    {
        $aliases = $this->getAliases();

        // render help by input definition.
        if ($definition = $this->getDefinition()) {
            $this->showHelpByDefinition($definition, $aliases);
            return true;
        }

        $execMethod = self::METHOD;

        $this->logf(Console::VERB_CRAZY, "display help info for the command: %s", self::getName());

        return $this->showHelpByAnnotations($execMethod, '', $aliases) !== 0;
    }
}
