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
use function array_shift;

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
     * @var Controller|null
     */
    protected ?Controller $group = null;

    /**
     * command argument rules
     *
     * eg:
     *
     *  [
     *      'arg1' => 'type;desc',
     *  ]
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [];
    }

    /**
     * @param FlagsParser $fs
     */
    protected function beforeInitFlagsParser(FlagsParser $fs): void
    {
        $fs->addArgsByRules($this->getArguments());
        // $fs->setStopOnFistArg(false);
    }

    /**
     * @param FlagsParser $fs
     *
     * @throws ReflectionException
     */
    protected function afterInitFlagsParser(FlagsParser $fs): void
    {
        $this->debugf('cmd: %s - load command flags configure, class: %s', $this->getRealCName(), static::class);
        $this->configure();
        $this->configFlags($fs);

        $isEmpty = $this->flags->isEmpty();

        // load built in options
        $fs->addOptsByRules(GlobalOption::getAloneOptions());

        // not config flags. load rules from method doc-comments
        if ($isEmpty) {
            $this->loadRulesByDocblock(self::METHOD, $fs);
        }
    }

    /**
     * @param array $args
     *
     * @return mixed
     */
    protected function doRun(array $args): mixed
    {
        // if input sub-command name
        if (isset($args[0])) {
            $first = $args[0];
            $rName = $this->resolveAlias($first);

            if ($this->isSub($rName)) {
                array_shift($args);
                return $this->dispatchSub($rName, $args);
            }
        }

        return parent::doRun($args);
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
    public function getRealCName(): string
    {
        return self::getName();
    }

    /**
     * Get the group
     *
     * @return Controller|null
     */
    public function getGroup(): ?Controller
    {
        return $this->group;
    }

    /**
     * Set the value of group
     *
     * @param  Controller  $group
     */
    public function setGroup(Controller $group): void
    {
        $this->group = $group;
    }
}
