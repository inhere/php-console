<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console;

use Generator;
use Inhere\Console\Contract\ControllerInterface;
use Inhere\Console\Decorate\ControllerHelpTrait;
use Inhere\Console\Exception\ConsoleException;
use Inhere\Console\Handler\AbstractHandler;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\Helper;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionObject;
use Throwable;
use Toolkit\PFlag\FlagsParser;
use Toolkit\PFlag\FlagUtil;
use Toolkit\PFlag\SFlags;
use Toolkit\Stdlib\Obj\ObjectHelper;
use Toolkit\Stdlib\Str;
use function array_flip;
use function array_shift;
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
     * @var array global options for the group command
     */
    protected static array $globalOptions = [
        '--show-disabled' => 'Whether display disabled commands',
    ];

    /**
     * Action name - real subcommand name, no suffix 'Command'.
     *
     * eg: updateCommand() -> action: 'update'
     *
     * @var string
     */
    private string $action = '';

    /**
     * The input group name.
     *
     * @var string
     */
    private string $groupName = '';

    /**
     * The delimiter. eg: '/' ':'
     *
     * @var string
     */
    private string $delimiter = ':';

    /**
     * @var string
     */
    private string $defaultAction = '';

    /**
     * The action method name on the controller.
     *
     * @var string
     */
    private string $actionMethod = '';

    /**
     * @var string
     */
    private string $actionSuffix = self::COMMAND_SUFFIX;

    /**
     * Flags for all action commands
     *
     * ```php
     * [
     *  action => AbstractFlags,
     *  action2 => AbstractFlags2,
     * ]
     * ```
     *
     * @var FlagsParser[]
     * @psalm-var array<string, FlagsParser>
     */
    private array $subFss = [];

    /**
     * @var array From disabledCommands()
     */
    private array $disabledCommands = [];

    /**
     * TODO ...
     *
     * @var array
     */
    private array $attachedCommands = [];

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
    protected array $commandMetas = [];

    /**
     * Define command alias mapping. please rewrite it on sub-class.
     *
     * - key is command name, value is aliases.
     *
     * @return array{string: list<string>}
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
        parent::init();

        // up: load sub-commands alias
        $this->loadCommandAliases();

        $list = $this->disabledCommands();

        $this->groupName = $this->getRealGName();
        // save to property
        $this->disabledCommands = $list ? array_flip($list) : [];

        if (!$this->actionSuffix) {
            $this->actionSuffix = self::COMMAND_SUFFIX;
        }
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
     * Will call it on subcommand not found on the group.
     *
     * @param string $command
     * @param array  $args
     *
     * @return bool if return True, will stop goon render group help.
     */
    protected function onNotFound(string $command, array $args): bool
    {
        // TIP: you can add custom logic on sub-command not found.
        return false;
    }

    /**
     * @param FlagsParser $fs
     */
    protected function afterInitFlagsParser(FlagsParser $fs): void
    {
        $fs->addOptsByRules(GlobalOption::getGroupOptions());
    }

    /**
     * Run an action with args
     *
     * Usage:
     *
     * ```php
     *  $args = $this->flags->getRawArgs();
     *  // add option
     *  $args[] = '--push';
     *  $this->runActionWithArgs('subcmd', $args);
     * ```
     *
     * @param string $cmd
     * @param array $args
     *
     * @return bool|int|mixed
     * @throws Throwable
     */
    public function runActionWithArgs(string $cmd, array $args): mixed
    {
        $args[0] = $cmd;
        return $this->doRun($args);
    }

    protected function beforeRun(): void
    {
    }

    /**
     * @param array $args
     *
     * @return mixed
     * @throws Throwable
     */
    public function doRun(array $args): mixed
    {
        $name = self::getName();
        if (!$args) {
            $command = $this->defaultAction;

            // and not default command
            if (!$command) {
                $this->debugf('cmd: %s - run args is empty, display help for the group', $name);
                return $this->showHelp();
            }
        } else {
            $first = $args[0];
            if (!FlagUtil::isValidName($first)) {
                $this->debugf('cmd: %s - not input subcommand, display help for the group', $name);
                return $this->showHelp();
            }

            $command = $first;
            array_shift($args);
        }

        // update subcommand
        $this->commandName = $command;

        // update some comment vars
        $fullCmd = $this->input->buildFullCmd($name, $command);
        $this->setCommentsVar('fullCmd', $fullCmd);
        $this->setCommentsVar('fullCommand', $fullCmd);
        $this->setCommentsVar('binWithCmd', $this->input->buildCmdPath($name, $command));

        // get real sub-command name
        $command = $this->resolveAlias($command);

        // update the command id.
        $this->input->setCommandId("$name:$command");

        // convert 'boo-foo' to 'booFoo'
        $this->action = $action = Str::camelCase($command);
        $this->debugf("cmd: %s - will run the subcommand: %s(action: %s)", $name, $command, $action);
        $method = $this->getMethodName($action);

        // fire event
        $this->fire(ConsoleEvent::COMMAND_RUN_BEFORE, $this);
        $this->beforeRun();

        // check method not exist
        if (!method_exists($this, $method)) {
            if ($this->isSub($command)) {
                return $this->dispatchSub($command, $args);
            }

            // if command not exists.
            return $this->handleNotFound($name, $command, $args);
        }

        // init flags for subcommand
        $fs = $this->newActionFlags();

        $this->actionMethod = $method;
        $this->input->setFs($fs);
        $this->debugf('load flags by configure method, subcommand: %s', $command);
        $this->configure();

        // not config flags. load rules from method doc-comments
        if ($fs->isEmpty()) {
            $this->loadRulesByDocblock($method, $fs);
        }

        $this->log(Console::VERB_DEBUG, "run subcommand '$name.$command' - parse options", ['args' => $args]);
        // parse subcommand flags.
        if (!$fs->parse($args)) {
            return 0;
        }

        // do running
        return parent::doRun($args);
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
     * @throws ReflectionException
     */
    final public function execute(Input $input, Output $output): mixed
    {
        $action = $this->action;
        $group  = static::getName();

        if ($this->isDisabled($action)) {
            $this->debugf('command %s is disabled on the group %s', $action, $group);
            $output->error(sprintf("Sorry, The command '%s' is invalid in the group '%s'!", $action, $group));
            return -1;
        }

        $method = $this->getMethodName($action);

        // trigger event
        $this->fire(ConsoleEvent::SUBCOMMAND_RUN_BEFORE, $this);

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

        // current action flags
        $flags = $this->actionFlags($action);

        $rftMethod = new ReflectionMethod($this, $method);
        $callArgs  = ObjectHelper::buildReflectCallArgs($rftMethod, [
            Input::class       => $this->input,
            Output::class      => $this->output,
            FlagsParser::class => $flags,
            // 'args' => $args,
        ]);

        // call action method
        $result = $rftMethod->invokeArgs($this, $callArgs);
        // call action method
        // $result = $this->$method($input, $output, $flags);

        // after run action
        if (method_exists($this, $after = 'after' . ucfirst($action))) {
            $this->$after($input, $output);
        }

        $this->afterAction();
        return $result;
    }

    /**
     * @param string $group
     * @param string $command
     * @param array  $args
     *
     * @return int
     */
    protected function handleNotFound(string $group, string $command, array $args): int
    {
        // if user custom handle not found logic.
        if ($this->onNotFound($command, $args)) {
            $this->debugf('group: %s - user custom handle the subcommand "%s" not found', $group, $command);
            return 0;
        }

        $this->debugf('group: %s - command "%s" is not found on the group', $group, $command);

        // if you defined the method '$this->notFoundCallback' , will call it
        // if (($notFoundCallback = $this->notFoundCallback) && method_exists($this, $notFoundCallback)) {
        //     $result = $this->{$notFoundCallback}($action);
        // } else {
        $this->output->liteError("Sorry, The command '$command' not exist of the group '$group'!");

        // find similar command names
        $similar = Helper::findSimilar($command, $this->getAllCommandMethods(null, true));

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
     * @return FlagsParser
     */
    protected function newActionFlags(string $action = ''): FlagsParser
    {
        $action = $action ?: $this->action;

        if (!$fs = $this->getActionFlags($action)) {
            $fs = new SFlags(['name' => $action]);
            $fs->setStopOnFistArg(false);
            $fs->setBeforePrintHelp(function (string $text) {
                return $this->parseCommentsVars($text);
            });
            $fs->setHelpRenderer(function (): void {
                $this->logf(Console::VERB_DEBUG, 'show subcommand help by input flags: -h, --help');
                $this->showHelp();
            });

            // old mode: options and arguments at method annotations
            // if ($this->compatible) {
            //     $fs->setSkipOnUndefined(true);
            // }

            // save
            $this->subFss[$action] = $fs;
        }

        return $fs;
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
        return $this->helpCommand() === 0;
    }

    /**
     * @param array $help
     */
    protected function beforeRenderCommandHelp(array &$help): void
    {
        $help['Group Options:'] = FlagUtil::alignOptions($this->flags->getOptsHelpLines());
    }

    /**
     * @param ReflectionClass|null $ref
     * @param bool                 $onlyName
     *
     * @return ?Generator
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
     * @return bool
     */
    public function isDisabled(string $name): bool
    {
        return isset($this->disabledCommands[$name]);
    }

    /**
     * load sub-commands aliases from sub-class::commandAliases()
     */
    public function loadCommandAliases(): void
    {
        $cmdAliases = static::commandAliases();
        if (!$cmdAliases) {
            return;
        }

        foreach ($cmdAliases as $name => $item) {
            // $name is command, $item is alias list
            // eg: ['command1' => ['alias1', 'alias2']]
            if (is_array($item)) {
                $this->setAlias($name, $item, true);
            } elseif (is_string($item)) { // $item is command, $name is alias name
                $this->setAlias($item, $name, true);
            }
        }
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
     * @return string
     */
    public function getGroupName(): string
    {
        return $this->groupName;
    }

    /**
     * @return string
     */
    public function getRealGName(): string
    {
        return self::getName();
    }

    /**
     * @param string $groupName
     */
    public function setGroupName(string $groupName): void
    {
        $this->groupName = $groupName;
    }

    /**
     * @return string
     */
    public function getRealCName(): string
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getSubName(): string
    {
        return $this->commandName;
    }

    /**
     * @param bool $useReal
     *
     * @return string
     */
    public function getCommandId(bool $useReal = true): string
    {
        if ($useReal) {
            return self::getName() . $this->delimiter . $this->action;
        }

        return $this->groupName . $this->delimiter . $this->commandName;
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
     * @param string $action
     */
    public function setDefaultAction(string $action): void
    {
        $this->defaultAction = trim($action, $this->delimiter);
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
    public function getCommandMeta(string $key, $default = null, string $command = ''): mixed
    {
        $action = $command ?: $this->action;

        return $this->commandMetas[$action][$key] ?? $default;
    }

    /**
     * @param string $action
     *
     * @return FlagsParser|null
     */
    public function getActionFlags(string $action): ?FlagsParser
    {
        $action = $action ?: $this->action;

        return $this->subFss[$action] ?? null;
    }

    /**
     * @return FlagsParser
     */
    public function curActionFlags(): FlagsParser
    {
        return $this->actionFlags($this->action);
    }

    /**
     * @param string $action
     *
     * @return FlagsParser
     */
    public function actionFlags(string $action): FlagsParser
    {
        $action = $action ?: $this->action;

        if (!isset($this->subFss[$action])) {
            throw new ConsoleException("not found flags parser for the action: $action");
        }

        return $this->subFss[$action];
    }
}
