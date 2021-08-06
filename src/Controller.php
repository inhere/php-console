<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace Inhere\Console;

use Generator;
use Inhere\Console\Concern\ControllerHelpTrait;
use Inhere\Console\Contract\ControllerInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputDefinition;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\FormatUtil;
use Inhere\Console\Util\Helper;
use ReflectionClass;
use ReflectionObject;
use RuntimeException;
use Toolkit\Stdlib\Str;
use function array_flip;
use function array_keys;
use function implode;
use function is_array;
use function is_string;
use function method_exists;
use function sprintf;
use function substr;
use function trim;
use function ucfirst;

/**
 * Class Controller
 *
 * @package Inhere\Console
 */
abstract class Controller extends AbstractHandler implements ControllerInterface
{
    use ControllerHelpTrait;

    /**
     * The sub-command aliases mapping
     *
     * eg: [
     *  alias => command,
     *  alias1 => command1,
     *  alias2 => command1,
     * ]
     *
     * @var array
     */
    private static $commandAliases = [];

    /**
     * @var array global options for the group command
     */
    protected static $globalOptions = [
        '--show-disabled' => 'Whether display disabled commands',
    ];

    /**
     * Action name, no suffix.
     * eg: updateCommand() -> action: 'update'
     *
     * @var string
     */
    private $action;

    /**
     * eg: '/' ':'
     *
     * @var string
     */
    private $delimiter = ':';

    /**
     * @var string
     */
    private $defaultAction = '';

    /**
     * @var string
     */
    private $actionSuffix = self::COMMAND_SUFFIX;

    /**
     * @var array Common options for all sub-commands in the group
     */
    private $groupOptions = [];

    /**
     * @var array From disabledCommands()
     */
    private $disabledCommands = [];

    /**
     * TODO ...
     *
     * @var array
     */
    private $attachedCommands = [];

    /**
     * Metadata for sub-commands. such as: desc, alias
     * Notice: you must add metadata on `init()`
     *
     * [
     *  'command real name' => [
     *      'desc'  => 'sub command description',
     *      'alias' => [],
     *   ],
     * ],
     *
     * @var array
     */
    protected $commandMetas = [];

    /**
     * Define command alias mapping. please rewrite it on sub-class.
     *
     * @return array
     */
    protected static function commandAliases(): array
    {
        // Usage:
        // - method 1:
        // alias => command
        // [
        //  'i'   => 'install',
        //  'ins' => 'install',
        // ]
        //
        // - method 2:
        // command => alias[]
        // [
        //  'install'  => ['i', 'ins'],
        // ]
        return [];
    }

    protected function init(): void
    {
        self::loadCommandAliases();

        $list = $this->disabledCommands();

        // save to property
        $this->disabledCommands = $list ? array_flip($list) : [];
        $this->groupOptions     = $this->groupOptions();

        if (!$this->actionSuffix) {
            $this->actionSuffix = self::COMMAND_SUFFIX;
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
        // ['--skip-invalid' => 'Whether ignore invalid arguments and options, when use input definition',]
        return [];
    }

    /**
     * define disabled command list.
     *
     * @return array
     */
    protected function disabledCommands(): array
    {
        // ['command1', 'command2'];
        return [];
    }

    /**
     * Will call it on action(sub-command) not found on the group.
     *
     * @param string $action
     *
     * @return bool if return True, will stop goon render group help.
     */
    protected function onNotFound(string $action): bool
    {
        // you can add custom logic on sub-command not found.
        return false;
    }

    /**
     * @param string $command
     *
     * @return string
     */
    protected function findCommandName(string $command): string
    {
        if (!$command = trim($command, $this->delimiter)) {
            $command = $this->defaultAction;

            // try use next arg as sub-command name.
            if (!$command) {
                $command = $this->input->findCommandName();

                // update the command id.
                if ($command) {
                    $group = $this->input->getCommand();
                    $this->input->setCommandId("$group:$command");
                }
            }
        }

        return $command;
    }

    protected function beforeRun(): void
    {
    }

    /**
     * @param string $command command in the group
     *
     * @return int|mixed
     */
    public function run(string $command = '')
    {
        $command = $this->findCommandName($command);

        // if not input sub-command, render group help.
        if (!$command) {
            $this->debugf('not input subcommand, display help for the group: %s', self::getName());
            return $this->showHelp();
        }

        // update subcommand
        $this->input->setSubCommand($command);

        // update some comment vars
        $fullCmd = $this->input->getFullCommand();
        $this->setCommentsVar('fullCmd', $fullCmd);
        $this->setCommentsVar('fullCommand', $fullCmd);
        $this->setCommentsVar('binWithCmd', $this->input->getBinWithCommand());

        // get real sub-command name
        $command = $this->resolveAlias($command);

        // convert 'boo-foo' to 'booFoo'
        $this->action = $action = Str::camelCase($command);
        $this->debugf("will run the '%s' group action: %s, sub-command: %s", static::getName(), $this->action, $command);

        $this->beforeRun();

        // check method not exist
        $method = $this->getMethodName($action);

        // if command method not exists.
        if (!method_exists($this, $method)) {
            return $this->handleNotFound(static::getName(), $action);
        }

        // do running
        return parent::run($command);
    }

    /**
     * Load command configure
     */
    protected function configure(): void
    {
        // eg. use `indexConfigure()` for `indexCommand()`
        $method = $this->action . self::CONFIGURE_SUFFIX;

        if (method_exists($this, $method)) {
            $this->$method($this->input);
        }
    }

    /**
     * @return InputDefinition
     */
    protected function createDefinition(): InputDefinition
    {
        if (!$this->definition) {
            $this->definition = new InputDefinition();

            // if have been set desc for the sub-command
            $cmdDesc = $this->commandMetas[$this->action]['desc'] ?? '';
            if ($cmdDesc) {
                $this->definition->setDescription($cmdDesc);
            }
        }

        return $this->definition;
    }

    /**
     * Before controller method execute
     *
     * @return boolean It MUST return TRUE to continue execute. if return False, will stop run.
     */
    protected function beforeAction(): bool
    {
        return true;
    }

    /**
     * After controller method execute
     */
    protected function afterAction(): void
    {
    }

    /**
     * Run command action in the group
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return mixed
     */
    final public function execute($input, $output)
    {
        $action = $this->action;
        $group  = static::getName();

        if ($this->isDisabled($action)) {
            $this->debugf('command %s is disabled on the group %s', $action, $group);
            $output->error(sprintf("Sorry, The command '%s' is invalid in the group '%s'!", $action, $group));
            return -1;
        }

        $method = $this->getMethodName($action);

        // the action method exists and only allow access public method.
        // if (method_exists($this, $method)) {
        // before run action
        if (!$this->beforeAction()) {
            $this->debugf('beforeAction() returns FALSE, interrupt processing continues');
            return 0;
        }

        if (method_exists($this, $beforeFunc = 'before' . ucfirst($action))) {
            $beforeOk = $this->$beforeFunc($input, $output);
            if ($beforeOk === false) {
                $this->debugf('%s() returns FALSE, interrupt processing continues', $beforeFunc);
                return 0;
            }
        }

        // run action
        $result = $this->$method($input, $output);

        // after run action
        if (method_exists($this, $after = 'after' . ucfirst($action))) {
            $this->$after($input, $output);
        }

        $this->afterAction();
        return $result;
    }

    /**
     * @param string $group
     * @param string $action
     *
     * @return int
     */
    protected function handleNotFound(string $group, string $action): int
    {
        // if user custom handle not found logic.
        if ($this->onNotFound($action)) {
            $this->debugf('user custom handle the "%s" action "%s" not found', $group, $action);
            return 0;
        }

        $this->debugf('action "%s" not found on the group controller "%s"', $action, $group);

        // if you defined the method '$this->notFoundCallback' , will call it
        // if (($notFoundCallback = $this->notFoundCallback) && method_exists($this, $notFoundCallback)) {
        //     $result = $this->{$notFoundCallback}($action);
        // } else {
        $this->output->liteError("Sorry, The command '$action' not exist of the group '$group'!");

        // find similar command names
        $similar = Helper::findSimilar($action, $this->getAllCommandMethods(null, true));

        if ($similar) {
            $this->output->writef("\nMaybe what you mean is:\n    <info>%s</info>", implode(', ', $similar));
        } else {
            $this->showCommandList();
        }

        return -1;
    }

    /**
     * @param string $action
     *
     * @return string
     */
    protected function getMethodName(string $action): string
    {
        return $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;
    }

    /**
     * @return bool
     */
    protected function showHelp(): bool
    {
        // render help by Definition
        if ($definition = $this->getDefinition()) {
            if ($action = $this->action) {
                $aliases = $this->getCommandAliases($action);
            } else {
                $aliases = $this->getAliases();
            }

            $this->showHelpByDefinition($definition, $aliases);
            return true;
        }

        return $this->helpCommand() === 0;
    }

    /**
     * @param array $help
     */
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
     * @return string
     * @description please use resolveAlias()
     */
    public function getRealCommandName(string $name): string
    {
        return $this->resolveAlias($name);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function resolveAlias(string $name): string
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

    /**
     * load sub-commands aliases from sub-class::commandAliases()
     */
    public static function loadCommandAliases(): void
    {
        $cmdAliases = static::commandAliases();
        if (!$cmdAliases) {
            return;
        }

        $fmtAliases = [];
        foreach ($cmdAliases as $name => $item) {
            // $name is command, $item is alias list
            // eg: ['command1' => ['alias1', 'alias2']]
            if (is_array($item)) {
                foreach ($item as $alias) {
                    $fmtAliases[$alias] = $name;
                }
            } elseif (is_string($item)) { // $item is command, $name is alias name
                $fmtAliases[$name] = $item;
            }
        }

        self::$commandAliases = $fmtAliases;
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
    public function setDefaultAction(string $defaultAction): void
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
    public function setActionSuffix(string $actionSuffix): void
    {
        $this->actionSuffix = $actionSuffix;
    }

    /**
     * @return bool
     * @deprecated
     */
    public function isExecutionAlone(): bool
    {
        throw new RuntimeException('please call isAttached() instead');
    }

    /**
     * @deprecated
     */
    public function setExecutionAlone(): void
    {
        throw new RuntimeException('please call setAttached() instead');
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

    /**
     * @return array
     */
    public function getCommandMetas(): array
    {
        return $this->commandMetas;
    }

    /**
     * @param string $command
     * @param array  $meta eg: ['desc' => '', 'alias' => []]
     */
    public function setCommandMeta(string $command, array $meta): void
    {
        if ($command) {
            $this->commandMetas[$command] = $meta;
        }
    }

    /**
     * @param string $key
     * @param null   $default
     * @param string $command if not set, will use $this->action
     *
     * @return mixed|null
     */
    public function getCommandMeta(string $key, $default = null, string $command = '')
    {
        $action = $command ?: $this->action;

        return $this->commandMetas[$action][$key] ?? $default;
    }
}
