<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace Inhere\Console;

use Generator;
use Inhere\Console\Contract\ControllerInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\FormatUtil;
use Inhere\Console\Util\Helper;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionObject;
use Toolkit\Cli\ColorTag;
use Toolkit\PhpUtil\PhpDoc;
use Toolkit\StrUtil\Str;
use function array_flip;
use function array_keys;
use function array_merge;
use function implode;
use function ksort;
use function lcfirst;
use function method_exists;
use function sprintf;
use function strpos;
use function substr;
use function trim;
use function ucfirst;
use const PHP_EOL;

/**
 * Class Controller
 *
 * @package Inhere\Console
 */
abstract class Controller extends AbstractHandler implements ControllerInterface
{
    /** @var array sub-command aliases */
    private static $commandAliases = [];

    /** @var array global options for the group command */
    protected static $globalOptions = [
        '--show-disabled' => 'Whether display disabled commands',
    ];

    /** @var string Action name, no suffix. */
    private $action;

    /** @var string */
    private $delimiter = ':'; // '/' ':'

    /** @var bool Execution alone */
    private $executionAlone = false;

    /** @var string */
    private $defaultAction = '';

    /** @var string */
    private $actionSuffix = 'Command';

    /** @var string */
    protected $notFoundCallback = 'notFound';

    /** @var array Common options for all sub-commands in the group */
    private $groupOptions = [];

    /** @var array From disabledCommands() */
    private $disabledCommands = [];

    /**
     * define command alias map
     *
     * @return array
     */
    protected static function commandAliases(): array
    {
        return [
            // alias => command
            // 'i'  => 'install',
        ];
    }

    protected function init(): void
    {
        $list = $this->disabledCommands();
        // save to property
        $this->disabledCommands = $list ? array_flip($list) : [];
        self::$commandAliases   = static::commandAliases();
        $this->groupOptions     = $this->groupOptions();

        if (!$this->actionSuffix) {
            $this->actionSuffix = 'Command';
        }
    }

    /**
     * Options for the group all commands.
     * you can set common options for all sub-commands
     *
     * @return array
     */
    protected function groupOptions(): array
    {
        return [// '--skip-invalid' => 'Whether ignore invalid arguments and options, when use input definition',
        ];
    }

    /**
     * define disabled command list.
     *
     * @return array
     */
    protected function disabledCommands(): array
    {
        return [// 'command1', 'command2'
        ];
    }

    /**
     * @param string $command
     *
     * @return int|mixed
     * @throws ReflectionException
     */
    public function run(string $command = '')
    {
        if (!$command = trim($command, $this->delimiter)) {
            $command = $this->defaultAction;
        }

        $this->action = Str::camelCase($this->getRealCommandName($command));

        if (!$this->action) {
            return $this->showHelp();
        }

        return parent::run($command);
    }

    /**
     * Load command configure
     */
    protected function configure(): void
    {
        // eg. IndexConfigure() for indexCommand()
        $method = $this->action . 'Configure';

        if (method_exists($this, $method)) {
            $this->$method();
        }
    }

    /**
     * Run command action in the group
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return mixed
     * @throws ReflectionException
     */
    final public function execute($input, $output)
    {
        $action = $this->action;
        $group  = static::getName();

        if ($this->isDisabled($action)) {
            $output->liteError(sprintf("Sorry, The command '%s' is invalid in the group '%s'!", $action, $group));
            return -1;
        }

        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;

        // the action method exists and only allow access public method.
        if (method_exists($this, $method) && (($rfm = new ReflectionMethod($this, $method)) && $rfm->isPublic())) {
            // before
            if (method_exists($this, $before = 'before' . ucfirst($action))) {
                $this->$before($input, $output);
            }

            // run action
            $result = $this->$method($input, $output);

            // after
            if (method_exists($this, $after = 'after' . ucfirst($action))) {
                $this->$after($input, $output);
            }

            return $result;
        }

        // if you defined the method '$this->notFoundCallback' , will call it
        if (($notFoundCallback = $this->notFoundCallback) && method_exists($this, $notFoundCallback)) {
            $result = $this->{$notFoundCallback}($action);
        } else {
            $result = -1;
            $output->liteError("Sorry, The command '$action' not exist of the group '{$group}'!");

            // find similar command names
            $similar = Helper::findSimilar($action, $this->getAllCommandMethods(null, true));

            if ($similar) {
                $output->write(sprintf("\nMaybe what you mean is:\n    <info>%s</info>", implode(', ', $similar)));
            } else {
                $this->showCommandList();
            }
        }

        return $result;
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    protected function showHelp(): bool
    {
        // help info has been build by input definition.
        if (true === parent::showHelp()) {
            return true;
        }

        return $this->helpCommand();
    }

    protected function beforeRenderCommandHelp(array &$help): void
    {
        $help['Group Options:'] = FormatUtil::alignOptions($this->groupOptions);
    }

    /**
     * @return array
     */
    public function getGroupOptions(): array
    {
        return $this->groupOptions;
    }

    /**
     * Show help of the controller command group or specified command action
     * @usage <info>{name}:[command] -h</info> OR <info>{command} [command]</info> OR <info>{name} [command] -h</info>
     *
     * @options
     *  -s, --search  Search command by input keywords
     *  --format      Set the help information dump format(raw, xml, json, markdown)
     * @return int
     * @throws ReflectionException
     * @example
     *  {script} {name} -h
     *  {script} {name}:help
     *  {script} {name}:help index
     *  {script} {name}:index -h
     *  {script} {name} index
     *
     */
    final public function helpCommand(): int
    {
        $action = $this->action;

        // For all sub-commands of the controller
        if (!$action && !($action = $this->getFirstArg())) {
            $this->showCommandList();
            return 0;
        }

        $action  = Str::camelCase($action);
        $method  = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;
        $aliases = $this->getCommandAliases($action);

        // For a specified sub-command.
        return $this->showHelpByMethodAnnotations($method, $action, $aliases);
    }

    protected function beforeShowCommandList()
    {
        // do something ...
    }

    /**
     * Display all sub-commands list of the controller class
     *
     * @throws ReflectionException
     */
    final public function showCommandList(): void
    {
        $this->beforeShowCommandList();

        $ref   = new ReflectionClass($this);
        $sName = lcfirst(self::getName() ?: $ref->getShortName());

        if (!($classDes = self::getDescription())) {
            $classDes = PhpDoc::description($ref->getDocComment()) ?: 'No description for the command group';
        }

        $commands     = [];
        $showDisabled = (bool)$this->getOpt('show-disabled', false);
        $defaultDes   = 'No description message';

        foreach ($this->getAllCommandMethods($ref) as $cmd => $m) {
            if (!$cmd) {
                continue;
            }

            $desc = PhpDoc::firstLine($m->getDocComment()) ?: $defaultDes;

            // is a annotation tag
            if (strpos($desc, '@') === 0) {
                $desc = $defaultDes;
            }

            if ($this->isDisabled($cmd)) {
                if (!$showDisabled) {
                    continue;
                }

                $desc .= '[<red>DISABLED</red>]';
            }

            $aliases = $this->getCommandAliases($cmd);
            $desc    .= $aliases ? ColorTag::wrap(' [alias: ' . implode(',', $aliases) . ']', 'info') : '';

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

        if ($this->executionAlone) {
            $name  = $sName . ' ';
            $usage = "$script <info>{command}</info> [--options ...] [arguments ...]";
        } else {
            $name  = $sName . $this->delimiter;
            $usage = "$script {$name}<info>{command}</info> [--options ...] [arguments ...]";
        }

        $globalOptions = array_merge(Application::getGlobalOptions(), static::$globalOptions);

        $this->output->startBuffer();
        $this->output->write(ucfirst($classDes) . PHP_EOL);
        $this->output->mList([
            'Usage:'              => $usage,
            //'Group Name:' => "<info>$sName</info>",
            'Global Options:'     => FormatUtil::alignOptions($globalOptions),
            'Available Commands:' => $commands,
        ], [
            'sepChar' => '  ',
        ]);

        $this->write(sprintf('More information about a command, please use: <cyan>%s %s{command} -h</cyan>', $script,
            $this->executionAlone ? '' : $name));
        $this->output->flush();
    }

    /**
     * @param ReflectionClass|null $ref
     * @param bool                 $onlyName
     *
     * @return Generator
     */
    protected function getAllCommandMethods(ReflectionClass $ref = null, bool $onlyName = false): ?Generator
    {
        $ref = $ref ?: new ReflectionObject($this);

        $suffix    = $this->actionSuffix;
        $suffixLen = Str::len($suffix);

        foreach ($ref->getMethods() as $m) {
            $mName = $m->getName();

            if ($m->isPublic() && substr($mName, -$suffixLen) === $suffix) {
                // suffix is empty ?
                $cmd = $suffix ? substr($mName, 0, -$suffixLen) : $mName;

                if ($onlyName) {
                    yield $cmd;
                } else {
                    yield $cmd => $m;
                }
            }
        }
    }

    /**
     * @param string $name
     *
     * @return mixed|string
     */
    protected function getRealCommandName(string $name)
    {
        if (!$name) {
            return '';
        }

        $map = $this->getCommandAliases();

        return $map[$name] ?? $name;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isDisabled(string $name): bool
    {
        return isset($this->disabledCommands[$name]);
    }

    /**************************************************************************
     * getter/setter methods
     **************************************************************************/

    /**
     * @return array
     */
    public function getDisabledCommands(): array
    {
        return $this->disabledCommands;
    }

    /**
     * @param string|null $name
     *
     * @return array
     */
    public function getCommandAliases(string $name = ''): array
    {
        if ($name) {
            return self::$commandAliases ? array_keys(self::$commandAliases, $name, true) : [];
        }

        return self::$commandAliases;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction(string $action): self
    {
        if ($action) {
            $this->action = Str::camelCase($action);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultAction(): string
    {
        return $this->defaultAction;
    }

    /**
     * @param string $defaultAction
     */
    public function setDefaultAction(string $defaultAction)
    {
        $this->defaultAction = trim($defaultAction, $this->delimiter);
    }

    /**
     * @return string
     */
    public function getActionSuffix(): string
    {
        return $this->actionSuffix;
    }

    /**
     * @param string $actionSuffix
     */
    public function setActionSuffix(string $actionSuffix)
    {
        $this->actionSuffix = $actionSuffix;
    }

    /**
     * @return string|null
     */
    public function getNotFoundCallback(): ?string
    {
        return $this->notFoundCallback;
    }

    /**
     * @param string $notFoundCallback
     */
    public function setNotFoundCallback(string $notFoundCallback)
    {
        $this->notFoundCallback = $notFoundCallback;
    }

    /**
     * @return bool
     */
    public function isExecutionAlone(): bool
    {
        return $this->executionAlone;
    }

    /**
     * @param bool $executionAlone
     */
    public function setExecutionAlone($executionAlone = true)
    {
        $this->executionAlone = (bool)$executionAlone;
    }

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }
}
