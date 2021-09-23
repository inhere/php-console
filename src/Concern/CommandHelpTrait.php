<?php declare(strict_types=1);

namespace Inhere\Console\Concern;

use Inhere\Console\Handler\AbstractHandler;
use Inhere\Console\Annotate\DocblockRules;
use Inhere\Console\Console;
use Inhere\Console\Util\FormatUtil;
use ReflectionException;
use Toolkit\PFlag\FlagsParser;
use Toolkit\Stdlib\Helper\PhpHelper;
use Toolkit\Stdlib\Util\PhpDoc;
use function implode;
use function is_string;
use function preg_replace;
use function sprintf;
use function strpos;
use function strtr;
use function ucfirst;
use const PHP_EOL;

/**
 * Trait CommandHelpTrait
 *
 * @package Inhere\Console\Concern
 */
trait CommandHelpTrait
{
    /**
     * @var array [name => value]
     * @see AbstractHandler::annotationVars()
     */
    private $commentsVars;

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
     * @param string|array $value
     */
    protected function addCommentsVar(string $name, $value): void
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
     * @param string|array $value
     */
    protected function setCommentsVar(string $name, $value): void
    {
        $this->commentsVars[$name] = is_array($value) ? implode(',', $value) : (string)$value;
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
        if (false === strpos($str, self::HELP_VAR_LEFT)) {
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
        $commandId = $this->input->getCommandId();
        $this->logf(Console::VERB_DEBUG, "render help for the command: %s", $commandId);

        if ($aliases) {
            $realName = $action ?: $this->getRealName();
            // command name
            $help['Command:'] = sprintf('%s(alias: <info>%s</info>)', $realName, implode(',', $aliases));
        }

        $binName = $this->input->getBinName();

        $path = $binName . ' ' . $name;
        if ($action) {
            $group = $this->getGroupName();
            $path  = "$binName $group $action";
        }

        $desc = $fs->getDesc();
        $this->writeln(ucfirst($this->parseCommentsVars($desc)));

        $help['Usage:'] = "$path [--options ...] [arguments ...]";

        $help['Options:']  = FormatUtil::alignOptions($fs->getOptsHelpData());
        $help['Argument:'] = $fs->getArgsHelpData();
        $help['Example:']  = $fs->getExampleHelp();

        $help['More Help:'] = $fs->getMoreHelp();

        // no group options. only set key position.
        $help['Group Options:'] = null;
        $this->beforeRenderCommandHelp($help);

        // attached to console app
        if ($app = $this->getApp()) {
            $help['Global Options:'] = FormatUtil::alignOptions($app->getFlags()->getOptsHelpData());
        }

        $this->output->mList($help, [
            'sepChar'     => '    ',
            'lastNewline' => false,
            'beforeWrite' => [$this, 'parseCommentsVars'],
        ]);

        return 0;
    }

    /**
     * Display command/action help by parse method annotations
     *
     * @param string $method
     * @param string $action action of an group
     * @param array $aliases
     *
     * @return int
     * @throws ReflectionException
     */
    protected function showHelpByAnnotations(string $method, string $action = '', array $aliases = []): int
    {
        $name = $this->input->getCommand();

        // subcommand: is a console controller subcommand
        $rftMth = PhpHelper::reflectMethod($this, $method);
        if ($action && !$rftMth->isPublic()) {
            $this->write("The command [<info>$name</info>] don't allow access in the class.");
            return 0;
        }

        $allowedTags = DocblockRules::getAllowedTags();
        $this->logf(Console::VERB_DEBUG, "render help for the command: %s", $this->input->getCommandId());

        $help = [];
        $doc  = $this->parseCommentsVars((string)$rftMth->getDocComment());
        $tags = PhpDoc::getTags($doc, [
            'allow' => $allowedTags,
        ]);

        if ($aliases) {
            $realName = $action ?: static::getName();
            // command name
            $help['Command:'] = sprintf('%s(alias: <info>%s</info>)', $realName, implode(',', $aliases));
        }

        $binName = $this->input->getBinName();

        $path = $binName . ' ' . $name;
        if ($action) {
            $group = static::getName();
            $path  = "$binName $group $action";
        }

        // is an command object
        $isCommand = $this->isCommand();
        foreach ($allowedTags as $tag) {
            if (empty($tags[$tag]) || !is_string($tags[$tag])) {
                // for alone command
                if ($tag === 'description' && $isCommand) {
                    $help['Description:'] = static::getDesc();
                    continue;
                }

                if ($tag === 'usage') {
                    $help['Usage:'] = "$path [--options ...] [arguments ...]";
                }
                continue;
            }

            // $msg = trim($tags[$tag]);
            $message   = $tags[$tag];
            $labelName = ucfirst($tag) . ':';

            // for alone command
            if ($tag === 'description' && $isCommand) {
                $message = static::getDescription();
            } else {
                $message = preg_replace('#(\n)#', '$1 ', $message);
            }

            $help[$labelName] = $message;
        }

        if (isset($help['Description:'])) {
            $description = $help['Description:'] ?: 'No description message for the command';
            $this->write(ucfirst($this->parseCommentsVars($description)) . PHP_EOL);
            unset($help['Description:']);
        }

        $help['Group Options:'] = null;
        $this->beforeRenderCommandHelp($help);

        if ($app = $this->getApp()) {
            $help['Global Options:'] = FormatUtil::alignOptions($app->getFlags()->getOptsHelpData());
        }

        $this->output->mList($help, [
            'sepChar'     => '  ',
            'lastNewline' => 0,
        ]);

        return 0;
    }

    /**
     * @param array $help
     */
    protected function beforeRenderCommandHelp(array &$help): void
    {
    }
}
