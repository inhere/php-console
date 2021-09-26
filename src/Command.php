<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console;

use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\Handler\AbstractHandler;
use ReflectionException;
use Toolkit\PFlag\FlagsParser;

/**
 * Class Command
 *
 * @package Inhere\Console
 *
 * ```php
 *  class MyCommand extends Command
 *  {
 *      protected function execute(Input $input, Output $output)
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
     * @param FlagsParser $fs
     */
    protected function beforeInitFlagsParser(FlagsParser $fs): void
    {
        $fs->setStopOnFistArg(false);

        // old mode: options and arguments at method annotations
        if ($this->compatible) {
            $fs->setSkipOnUndefined(true);
        }
    }

    /**
     * @param FlagsParser $fs
     *
     * @throws ReflectionException
     */
    protected function afterInitFlagsParser(FlagsParser $fs): void
    {
        $this->debugf('load flags configure for command: %s', $this->getRealName());
        $this->configure();

        $isEmpty = $this->flags->isEmpty();

        // load built in options
        $fs->addOptsByRules(GlobalOption::getAloneOptions());

        // not config flags. load rules from method doc-comments
        if ($isEmpty) {
            $this->loadRulesByDocblock(self::METHOD, $fs);
        }
    }

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
    public function getRoot(): Command
    {
        if ($this->parent) {
            return $this->parent->getRoot();
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

        $this->logf(Console::VERB_CRAZY, 'display help info for the command: %s', $this->commandName);

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
