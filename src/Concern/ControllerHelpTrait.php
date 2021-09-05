<?php declare(strict_types=1);

namespace Inhere\Console\Concern;

use Inhere\Console\Application;
use Inhere\Console\Console;
use Inhere\Console\Util\FormatUtil;
use ReflectionClass;
use Toolkit\Cli\ColorTag;
use Toolkit\Stdlib\Str;
use Toolkit\Stdlib\Util\PhpDoc;
use function array_merge;
use function implode;
use function ksort;
use function lcfirst;
use function sprintf;
use function strpos;
use function ucfirst;
use const PHP_EOL;

/**
 * Trait ControllerHelpTrait
 *
 * @package Inhere\Console\Concern
 */
trait ControllerHelpTrait
{
    /**
     * Show help of the controller command group or specified command action
     * @usage <info>{name}:[command] -h</info> OR <info>{command} [command]</info> OR <info>{name} [command] -h</info>
     *
     * @options
     *  -s, --search  Search command by input keywords
     *  --format      Set the help information dump format(raw, xml, json, markdown)
     * @return int
     * @example
     *  {script} {name} -h
     *  {script} {name}:help
     *  {script} {name}:help index
     *  {script} {name}:index -h
     *  {script} {name} index
     */
    public function helpCommand(): int
    {
        $action = $this->action;

        // Not input action, for all sub-commands of the controller
        if (!$action) {
            $this->showCommandList();
            return 0;
        }

        $action  = Str::camelCase($action);
        $method  = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;
        $aliases = $this->getCommandAliases($action);

        // up: find global aliases from app
        if ($this->app) {
            $commandId = $this->input->getCommandId();
            $gAliases  = $this->app->getAliases($commandId);

            if ($gAliases) {
                $aliases = array_merge($aliases, $gAliases);
            }
        }

        $this->log(Console::VERB_DEBUG, "display help for the controller method: $method", [
            'group'  => static::$name,
            'action' => $action,
        ]);

        // For a specified sub-command.
        return $this->showHelpByMethodAnnotations($method, $action, $aliases);
    }

    protected function beforeShowCommandList(): void
    {
        // do something ...
    }

    /**
     * Display all sub-commands list of the controller class
     */
    public function showCommandList(): void
    {
        $this->logf(Console::VERB_DEBUG, 'display all sub-commands list of the group: %s', static::$name);

        $this->beforeShowCommandList();

        $ref   = new ReflectionClass($this);
        $sName = lcfirst(self::getName() ?: $ref->getShortName());

        if (!($classDes = self::getDescription())) {
            $classDes = PhpDoc::description($ref->getDocComment()) ?: 'No description for the command group';
        }

        $commands     = [];
        $showDisabled = (bool)$this->getOpt('show-disabled', false);
        $defaultDes   = 'No description message';

        /**
         * @var $cmd string The command name.
         */
        foreach ($this->getAllCommandMethods($ref) as $cmd => $m) {
            if (!$cmd) {
                continue;
            }

            $desc = $this->getCommandMeta('desc', $defaultDes, $cmd);
            if ($phpDoc = $m->getDocComment()) {
                $desc = PhpDoc::firstLine($phpDoc);
            }

            // is a annotation tag
            if (strpos($desc, '@') === 0) {
                $desc = $defaultDes;
            }

            if ($this->isDisabled($cmd)) {
                if (!$showDisabled) {
                    continue;
                }

                $desc .= '(<red>DISABLED</red>)';
            }

            $aliases = $this->getCommandAliases($cmd);
            $desc    .= $aliases ? ColorTag::wrap(' (alias: ' . implode(',', $aliases) . ')', 'info') : '';

            $commands[$cmd] = $desc;
        }

        // sort commands
        ksort($commands);

        // move 'help' to last.
        if ($helpCmd = $commands['help'] ?? null) {
            unset($commands['help']);
            $commands['help'] = $helpCmd;
        }

        $script = $this->getScriptName();

        // if is alone running.
        if ($detached = $this->isDetached()) {
            $name  = $sName . ' ';
            $usage = "$script <info>{command}</info> [--options ...] [arguments ...]";
        } else {
            $name  = $sName . $this->delimiter;
            // $usage = "$script {$name}<info>{command}</info> [--options ...] [arguments ...]";
            $usage = [
                "$script $name<info>{command}</info> [--options ...] [arguments ...]",
                "$script $sName <info>{command}</info> [--options ...] [arguments ...]",
            ];
        }

        $globalOptions = array_merge(Application::getGlobalOptions(), static::$globalOptions);

        $this->output->startBuffer();
        $this->output->write(ucfirst($classDes) . PHP_EOL);

        if ($aliases = $this->getAliases()) {
            $this->output->writef("<comment>Alias:</comment> %s\n", implode(',', $aliases));
        }

        $this->output->mList([
            'Usage:'              => $usage,
            //'Group Name:' => "<info>$sName</info>",
            'Global Options:'     => FormatUtil::alignOptions($globalOptions),
            'Available Commands:' => $commands,
        ], [
            'sepChar' => '  ',
        ]);

        $msgTpl = 'More information about a command, please see: <cyan>%s %s {command} -h</cyan>';
        $this->output->write(sprintf($msgTpl, $script, $detached ? '' : $sName));
        $this->output->flush();
    }

}
