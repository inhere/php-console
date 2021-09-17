<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace Inhere\Console;

use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\IO\Input;
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
    public const METHOD = 'execute';

    /**
     * @var Command
     */
    protected $parent;

    /**
     * @var string
     */
    protected $commandName = '';

    protected function init(): void
    {
        $this->commandName = self::getName();

        parent::init();
    }

    /**
     * @throws ReflectionException
     */
    protected function initForRun(Input $input): void
    {
        parent::initForRun($input);

        $this->flags->setStopOnFistArg(false);
        // old mode: options and arguments at method annotations
        if ($this->compatible) {
            $this->flags->setSkipOnUndefined(true);
        }

        $this->debugf('load flags configure for command: %s', self::getName());
        // load input definition configure
        $this->configure();

        // not config flags. load rules from method doc-comments
        if ($this->flags->isEmpty()) {
            $this->loadRulesByDocblock(self::METHOD, $this->flags);
        }
    }

    // protected function doRun(array $args)
    // {
    //     parent::doRun($args);
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

        $this->logf(Console::VERB_CRAZY, "display help info for the command: %s", $this->commandName);

        // $execMethod = self::METHOD;
        // return $this->showHelpByAnnotations($execMethod, '', $aliases) !== 0;
        return $this->showHelpByFlagsParser($this->flags, $aliases) !== 0;
    }

    /**
     * @return string
     */
    public function getCommandName(): string
    {
        return $this->commandName;
    }
}
