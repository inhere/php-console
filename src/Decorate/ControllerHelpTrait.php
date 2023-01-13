<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Decorate;

use Inhere\Console\Console;
use Inhere\Console\GlobalOption;
use ReflectionClass;
use Toolkit\Cli\Color\ColorTag;
use Toolkit\PFlag\FlagUtil;
use Toolkit\Stdlib\Str;
use Toolkit\Stdlib\Util\PhpDoc;
use function array_merge;
use function implode;
use function ksort;
use function lcfirst;
use function sprintf;
use function ucfirst;
use const PHP_EOL;

/**
 * Trait ControllerHelpTrait
 *
 * @package Inhere\Console\Decorate
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
     *
     * @return int
     * @example
     *  {binName} {name} -h
     *  {binName} {name}:help
     *  {binName} {name}:help index
     *  {binName} {name}:index -h
     *  {binName} {name} index
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
        $aliases = $this->getNameAliases($action);

        // up: find global aliases from app
        if ($this->app) {
            $commandId = $this->input->getCommandId();
            $gAliases  = $this->app->getAliases($commandId);

            if ($gAliases) {
                $aliases = array_merge($aliases, $gAliases);
            }
        }

        $this->log(Console::VERB_DEBUG, "display help for the controller method: $method", [
            'group'  => $this->getGroupName(),
            'action' => $action,
        ]);

        // For a specified sub-command.
        // return $this->showHelpByAnnotations($method, $action, $aliases);
        return $this->showHelpByFlagsParser($this->curActionFlags(), $aliases, $action);
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
        $name = self::getName();
        $this->logf(Console::VERB_DEBUG, 'cmd: %s - display all sub-commands list of the group', $name);
        $this->beforeShowCommandList();

        $refCls = new ReflectionClass($this);
        $sName  = $name ?: lcfirst($refCls->getShortName());

        if (!($classDes = self::getDesc())) {
            $classDes = PhpDoc::description($refCls->getDocComment()) ?: 'No description for the command group';
        }

        $commands     = [];
        $showDisabled = $this->flags->getOpt(GlobalOption::SHOW_DISABLED, false);
        $defaultDes   = 'No description message';

        /**
         * @var $cmd string The command name.
         */
        foreach ($this->getAllCommandMethods($refCls) as $cmd => $m) {
            if (!$cmd) {
                continue;
            }

            $desc = $this->getCommandMeta('desc', $defaultDes, $cmd);
            if ($phpDoc = $m->getDocComment()) {
                $desc = PhpDoc::firstLine($phpDoc);
            }

            // is a annotation tag
            if (str_starts_with($desc, '@')) {
                $desc = $defaultDes;
            }

            $desc = ucfirst($desc);
            if ($this->isDisabled($cmd)) {
                if (!$showDisabled) {
                    continue;
                }

                $desc .= '(<red>DISABLED</red>)';
            }

            $aliases = $this->getNameAliases($cmd);
            $desc    .= $aliases ? ColorTag::wrap(' (alias: ' . implode(',', $aliases) . ')', 'info') : '';

            $commands[$cmd] = $desc;
        }

        if ($subCmds = $this->getSubsForHelp()) {
            $commands = array_merge($commands, $subCmds);
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
            $usage = "$script <info>COMMAND</info> [--options ...] [arguments ...]";
        } else {
            $name = $sName . $this->delimiter;
            // $usage = "$script {$name}<info>{command}</info> [--options ...] [arguments ...]";
            $usage = [
                "$script [--global options] $sName [--group options] <info>COMMAND</info> [--options ...] [arguments ...]",
                "$script [--global options] $name<info>COMMAND</info> [--options ...] [arguments ...]",
            ];
        }

        // $globalOptions = [];
        // if ($app = $this->app) {
        //     $globalOptions = $app->getFlags()->getOptsHelpLines();
        // }

        $this->output->startBuffer();
        $this->output->write(ucfirst($classDes) . PHP_EOL);

        $alias = '';
        if ($aliases = $this->getAliases()) {
            $alias = ' (alias: <info>' . implode(',', $aliases) . '</info>)';
        }
        $this->output->writef("<comment>Name :</comment> %s%s\n", $sName, $alias);

        $groupOptions = $this->flags->getOptsHelpLines();
        $this->output->mList([
            'Usage:'              => $usage,
            'Group Options:'      => FlagUtil::alignOptions($groupOptions),
            // 'Global Options:'     => FlagUtil::alignOptions($globalOptions),
            'Available Commands:' => $commands,
        ], [
            'sepChar' => '  ',
        ]);

        $msgTpl = 'More information about a command, please see: <cyan>%s%s COMMAND -h</cyan>';
        $this->output->writeln(sprintf($msgTpl, $script, $detached ? '' : ' ' . $sName));
        $this->output->flush();
    }
}
