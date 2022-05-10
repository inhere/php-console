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
use Inhere\Console\Handler\AbstractHandler;
use Toolkit\PFlag\FlagsParser;
use Toolkit\PFlag\FlagUtil;
use function implode;
use function ksort;
use function sprintf;
use function strtr;
use function ucfirst;

/**
 * Trait CommandHelpTrait
 *
 * @package Inhere\Console\Decorate
 */
trait CommandHelpTrait
{
    /**
     * @var array [name => value]
     * @see AbstractHandler::annotationVars()
     */
    private array $commentsVars = [];

    /**
     * on display command help
     *
     * @var bool
     */
    protected bool $renderGlobalOption = false;

    /**
     * @return array
     */
    public function getCommentsVars(): array
    {
        return $this->commentsVars;
    }

    /**
     * @param array $commentsVars
     */
    public function setCommentsVars(array $commentsVars): void
    {
        $this->commentsVars = $commentsVars;
    }

    /**
     * @param string $name
     * @param array|string $value
     */
    protected function addCommentsVar(string $name, array|string $value): void
    {
        if (!isset($this->commentsVars[$name])) {
            $this->setCommentsVar($name, $value);
        }
    }

    /**
     * @param array $map
     */
    protected function addCommentsVars(array $map): void
    {
        foreach ($map as $name => $value) {
            $this->setCommentsVar($name, $value);
        }
    }

    /**
     * @param string $name
     * @param array|string $value
     */
    protected function setCommentsVar(string $name, array|string $value): void
    {
        $this->commentsVars[$name] = is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 替换注解中的变量为对应的值
     *
     * @param string $str
     *
     * @return string
     */
    public function parseCommentsVars(string $str): string
    {
        // not use vars
        if (!str_contains($str, self::HELP_VAR_LEFT)) {
            return $str;
        }

        static $map;

        if ($map === null) {
            foreach ($this->commentsVars as $key => $value) {
                $key = self::HELP_VAR_LEFT . $key . self::HELP_VAR_RIGHT;
                // save
                $map[$key] = $value;
            }
        }

        return $map ? strtr($str, $map) : $str;
    }

    /**
     * @param FlagsParser $fs
     * @param string $action
     * @param array $aliases
     *
     * @return int
     */
    public function showHelpByFlagsParser(FlagsParser $fs, array $aliases = [], string $action = ''): int
    {
        $help = [];
        $name = $this->getCommandName();

        // $isCommand = $this->isCommand();
        // $commandId = $this->input->getCommandId();
        $this->logf(Console::VERB_DEBUG, 'cmd: %s - begin render help for the command', $name);

        if ($aliases) {
            $realName = $action ?: $this->getRealName();
            // command name
            $help['Command:'] = sprintf('%s(alias: <info>%s</info>)', $realName, implode(',', $aliases));
        }

        $binName = $this->input->getBinName();
        $cmdPath = $binName . ' ' . $this->getPath();

        if ($action) {
            // $group = $this->getGroupName();
            $cmdPath .= " $action";
        } elseif ($this->hasSubs()) {
            $cmdPath .= ' <cyan>SUBCOMMAND</cyan>';
        }

        $desc = $fs->getDesc();
        $this->writeln(ucfirst($this->parseCommentsVars($desc)));

        $help['Usage:'] = "$cmdPath [--options ...] [arguments ...]";

        $help['Options:']  = FlagUtil::alignOptions($fs->getOptsHelpLines());
        $help['Argument:'] = $fs->getArgsHelpLines();

        // fix: action should not have sub-commands
        if (!$action && ($subCmds = $this->getSubsForHelp())) {
            // sort commands
            ksort($subCmds);
            $help['Commands:'] = $subCmds;
        }

        $help['Example:']   = $fs->getExampleHelp();
        $help['More Help:'] = $fs->getMoreHelp();

        // no group options. only set key position.
        $help['Group Options:'] = null;
        $this->beforeRenderCommandHelp($help);

        // attached to console app
        if ($this->renderGlobalOption && ($app = $this->getApp())) {
            $help['Global Options:'] = FlagUtil::alignOptions($app->getFlags()->getOptsHelpLines());
        }

        $this->output->mList($help, [
            'sepChar'     => '    ',
            'lastNewline' => false,
            'beforeWrite' => [$this, 'parseCommentsVars'],
        ]);

        return 0;
    }

    /**
     * @param array $help
     */
    protected function beforeRenderCommandHelp(array &$help): void
    {
    }

    /**
     * @param bool $renderGlobalOption
     */
    public function setRenderGlobalOption(bool $renderGlobalOption): void
    {
        $this->renderGlobalOption = $renderGlobalOption;
    }
}
