<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Handler;

use Inhere\Console\Annotate\DocblockRules;
use Inhere\Console\Component\ErrorHandler;
use Inhere\Console\Console;
use Inhere\Console\ConsoleEvent;
use Inhere\Console\Contract\CommandHandlerInterface;
use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\Decorate\AttachApplicationTrait;
use Inhere\Console\Decorate\CommandHelpTrait;
use Inhere\Console\Decorate\InputOutputAwareTrait;
use Inhere\Console\Decorate\SubCommandsWareTrait;
use Inhere\Console\Decorate\UserInteractAwareTrait;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\Helper;
use ReflectionException;
use RuntimeException;
use Throwable;
use Toolkit\PFlag\FlagsParser;
use Toolkit\PFlag\SFlags;
use Toolkit\Stdlib\Helper\PhpHelper;
use Toolkit\Stdlib\Obj\DataObject;
use function cli_set_process_title;
use function error_get_last;
use function function_exists;
use function implode;
use const PHP_OS;

/**
 * Class AbstractHandler
 *
 * @package Inhere\Console
 */
abstract class AbstractHandler implements CommandHandlerInterface
{
    use AttachApplicationTrait, CommandHelpTrait;

    use InputOutputAwareTrait, UserInteractAwareTrait;

    use SubCommandsWareTrait;

    /**
     * The group/command name e.g 'test' 'test:one'
     *
     * @var string
     */
    protected static string $name = '';

    /**
     * The command/controller description message.
     *
     * TIP: please use the property setting current controller/command description
     *
     * @var string
     */
    protected static string $desc = '';

    /**
     * @var bool Whether enable coroutine. It is require swoole extension.
     */
    protected static bool $coroutine = false;

    /**
     * @var bool
     */
    private bool $initialized = false;

    /**
     * @var string
     */
    protected string $processTitle = '';

    /**
     * @var DataObject|null
     */
    protected ?DataObject $params = null;

    /**
     * The user input command name. maybe is an alias name.
     *
     * @var string
     */
    protected string $commandName = '';

    /**
     * @var array Command options
     */
    protected array $commandOptions = [];

    /**
     * Whether enabled
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return true;
    }

    /**
     * Setting current command/group name aliases
     *
     * @return string[]
     */
    public static function aliases(): array
    {
        // return ['alias1', 'alias2'];
        return [];
    }

    /**
     * Command constructor.
     *
     * @param Input|null $input
     * @param Output|null $output
     */
    public function __construct(Input $input = null, Output $output = null)
    {
        // init io stream
        $input && $this->setInput($input);
        $output && $this->setOutput($output);

        // init an flags object
        $this->setFlags(new SFlags());
        $this->init();
    }

    protected function init(): void
    {
        $this->afterInit();

        if ($subCmds = $this->subCommands()) {
            $this->addCommands($subCmds);
            $this->debugf('cmd: %s - load and attach subcommands: %s', $this->getRealName(), implode(',', $this->getSubNames()));
        }
    }

    protected function afterInit(): void
    {
        // do something...
    }

    /**
     * get command options
     *
     * **Alone Command**
     *
     * - set options for command
     *
     * **Group Controller**
     *
     * - set options for the group.
     * you can set common options for all sub-commands
     *
     * @return array
     */
    protected function getOptions(): array
    {
        // ['--skip-invalid' => 'Whether ignore invalid arguments and options, when use input definition',]
        return [];
    }

    /**
     * Configure for the command/controller.
     */
    protected function configure(): void
    {
    }

    /**
     * Config flags for the command/controller.
     *
     * @param FlagsParser $fs
     */
    protected function configFlags(FlagsParser $fs): void
    {
    }

    /**
     * Provides parsable substitution variables for command annotations. Can be used in comments in commands
     * 为命令注解提供可解析的替换变量. 可以在命令的注释中使用
     *
     * you can append by:
     *
     * ```php
     * protected function annotationVars(): array
     * {
     *      return \array_merge(parent::annotationVars(), [
     *          'myVar' => 'value',
     *      ]);
     * }
     * ```
     *
     * @return array
     */
    protected function annotationVars(): array
    {
        $binFile = $this->input->getScriptFile(); // bin/app
        $binName = $this->input->getScriptName();
        $command = $this->input->getCommand();
        $fullCmd = $this->input->buildFullCmd($command);

        // e.g: `more info see {name}:index`
        return [
            'name'        => self::getName(),
            'group'       => self::getName(),
            'workDir'     => $this->input->getPwd(),
            'script'      => $binFile, // bin/app
            'binFile'     => $binFile, // bin/app
            'binName'     => $binName, // app
            'scriptName'  => $binName, // app
            'command'     => $command, // demo OR home:test
            'fullCmd'     => $fullCmd, // bin/app demo OR bin/app home:test
            'fullCommand' => $fullCmd,
            'binWithCmd'  => $binName . ' ' . $command,
        ];
    }

    /**************************************************************************
     * running a command
     **************************************************************************/

    protected function initForRun(): void
    {
        $this->commentsVars = $this->annotationVars();

        if (!$this->commandName) {
            $this->commandName = $this->getRealName();
        }

        $this->addPathNode($this->commandName);
    }

    /**
     * @param Input $input
     */
    protected function initFlagsParser(Input $input): void
    {
        // if on interactive shell environment(GlobalOption::ISHELL=true)
        if ($this->flags->isLocked()) {
            return;
        }

        $input->setFs($this->flags);
        $this->flags->setDesc($this->getRealDesc());
        $this->flags->setScriptName($this->getRealName());

        $this->beforeInitFlagsParser($this->flags);

        // set options by options()
        $optRules = $this->getOptions();
        $this->flags->addOptsByRules($optRules);

        // for render help
        $this->flags->setBeforePrintHelp(fn(string $text): string => $this->parseCommentsVars($text));
        $this->flags->setHelpRenderer(function (): void {
            $name = $this->getRealName();
            $this->logf(Console::VERB_DEBUG, "cmd: $name - show help by input flags: -h, --help");
            $this->showHelp();
        });

        $this->afterInitFlagsParser($this->flags);
    }

    /**
     * @param FlagsParser $fs
     */
    protected function beforeInitFlagsParser(FlagsParser $fs): void
    {
        // $fs->addOptsByRules(GlobalOption::getAloneOptions());
    }

    /**
     * @param FlagsParser $fs
     */
    protected function afterInitFlagsParser(FlagsParser $fs): void
    {
        // $fs->addOptsByRules(GlobalOption::getAloneOptions());
    }

    /**
     * @param array $args
     *
     * @return mixed
     * @throws Throwable
     */
    public function run(array $args): mixed
    {
        $name = $this->getRealName();

        try {
            $this->initForRun();

            $this->initFlagsParser($this->input);

            $this->log(Console::VERB_DEBUG, "cmd: $name - parse flag options", ['args' => $args]);

            // parse options
            $this->flags->lock();
            if (!$this->flags->parse($args)) {
                return 0; // on error OR help
            }

            $args = $this->flags->getRawArgs();

            return $this->doRun($args);
        } catch (Throwable $e) {
            $this->log(Console::VERB_DEBUG, "cmd: $name - run error: " . $e->getMessage(), ['args' => $args]);

            if ($this->isDetached()) {
                ErrorHandler::new()->handle($e);
            } else {
                throw $e;
            }
        }

        return -1;
    }

    /**
     * run command
     *
     * @param array $args
     *
     * @return mixed
     */
    protected function doRun(array $args): mixed
    {
        // some prepare check
        if (true !== $this->prepare()) {
            return -1;
        }

        // return False to deny goon run.
        if (false === $this->beforeExecute()) {
            return -1;
        }

        // only fire for alone command run.
        if ($this->isAloneCmd()) {
            $this->fire(ConsoleEvent::COMMAND_RUN_BEFORE, $this);
        }

        // if enable swoole coroutine
        if (static::isCoroutine() && Helper::isSupportCoroutine()) {
            $result = $this->coExecute();
        } else { // when not enable coroutine
            $result = $this->execute($this->input, $this->output);
        }

        $this->afterExecute();
        return $result;
    }

    protected function doExecute(): mixed
    {
        return '';
    }

    /**
     * coroutine run by swoole go()
     *
     * @return int
     */
    public function coExecute(): int
    {
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        $cid = \Swoole\Coroutine\run(function (): void {
            $this->execute($this->input, $this->output);
        });

        // if create co fail
        if ($cid < 0) {
            $this->logf(Console::VERB_DEBUG, 'ERROR: The coroutine create failed');
            // exec by normal flow
            $result = (int)$this->execute($this->input, $this->output);
        } else { // success: wait coroutine exec.
            $result = 0;
        }

        return $result;
    }

    /**
     * Before command execute
     *
     * @return boolean It MUST return TRUE to continue execute.
     */
    protected function beforeExecute(): bool
    {
        // trigger event
        $this->fire(ConsoleEvent::COMMAND_EXEC_BEFORE, $this);

        return true;
    }

    /**
     * Do execute command
     *
     * @param Input $input
     * @param Output $output
     *
     * @return mixed|void
     */
    abstract protected function execute(Input $input, Output $output);

    /**
     * After command execute
     */
    protected function afterExecute(): void
    {
        // trigger event
        $this->fire(ConsoleEvent::COMMAND_EXEC_AFTER, $this);
    }

    /**
     * prepare run
     */
    protected function prepare(): bool
    {
        if ($this->processTitle && 'Darwin' !== PHP_OS) {
            if (function_exists('cli_set_process_title')) {
                cli_set_process_title($this->processTitle);
            } elseif (function_exists('setproctitle')) {
                /** @noinspection PhpUndefinedFunctionInspection */
                setproctitle($this->processTitle);
            }

            if ($error = error_get_last()) {
                throw new RuntimeException($error['message']);
            }
        }

        // return $this->validateInput();
        return true;
    }

    /**************************************************************************
     * wrap trigger events
     **************************************************************************/

    /**
     * @param string $event
     * @param mixed  ...$args
     *
     * @return bool
     */
    public function fire(string $event, ...$args): bool
    {
        $this->debugf("fire event: $event");

        // if has application instance
        if ($this->attached) {
            $stop = $this->app->fire($event, ...$args);
            if ($stop === false) {
                return false;
            }
        }
        // TODO pop fire event to parent and app

        return $this->parentFire($event, ...$args);
    }

    /**************************************************************************
     * helper methods
     **************************************************************************/

    /**
     * @return bool
     */
    public function isAlone(): bool
    {
        return $this instanceof CommandInterface;
    }

    /**
     * @return bool
     */
    public function isAloneCmd(): bool
    {
        return $this instanceof CommandInterface;
    }

    /**
     * @return DataObject
     */
    public function getParams(): DataObject
    {
        if (!$this->params) {
            $this->initParams([]);
        }

        return $this->params;
    }

    /**
     * @param array $params
     */
    public function initParams(array $params): void
    {
        $this->params = DataObject::new($params);
    }

    /**
     * @param string $method
     * @param FlagsParser $fs
     *
     * @throws ReflectionException
     */
    public function loadRulesByDocblock(string $method, FlagsParser $fs): void
    {
        $this->debugf('cmd: %s - not config flags, load flag rules by method %s() docblock', $this->getRealCName(), $method);
        $rftMth = PhpHelper::reflectMethod($this, $method);

        // parse doc for get flag rules
        $dr = DocblockRules::newByDocblock((string)$rftMth->getDocComment());
        $dr->parse();

        $fs->addArgsByRules($dr->getArgRules());
        $fs->addOptsByRules($dr->getOptRules());

        // more info
        $fs->setDesc($dr->getTagValue('desc'), true);
        $fs->setMoreHelp($dr->getTagValue('help'));
        $fs->setExample($dr->getTagValue('example'));
    }

    /**********************************************************
     * display help information
     **********************************************************/

    /**
     * Display help information
     *
     * @return bool
     */
    abstract protected function showHelp(): bool;

    /**************************************************************************
     * getter/setter methods
     **************************************************************************/

    /**
     * @return string
     */
    public function getGroupName(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getRealGName(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getRealCName(): string
    {
        return '';
    }

    /**
     * @param string $commandName
     */
    public function setCommandName(string $commandName): void
    {
        $this->commandName = $commandName;
    }

    /**
     * @return string
     */
    public function getCommandName(): string
    {
        return $this->commandName;
    }

    /**
     * @return string
     */
    public function getRealName(): string
    {
        return self::getName();
    }

    /**
     * @return string
     */
    public function getRealDesc(): string
    {
        return self::getDesc();
    }

    /**
     * @param bool $useReal
     *
     * @return string top:sub
     */
    public function getCommandId(bool $useReal = true): string
    {
        return $this->getPath(':');
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        if ($this->app) {
            $aliases = $this->app->getAliases(self::getName());
        } else {
            $aliases = static::aliases();
        }

        return $aliases;
    }

    /**
     * @param string $name
     */
    final public static function setName(string $name): void
    {
        static::$name = $name;
    }

    /**
     * @return string
     */
    final public static function getName(): string
    {
        return static::$name;
    }

    /**
     * @return string
     */
    public static function getDesc(): string
    {
        return static::$desc;
    }

    /**
     * @param string $desc
     */
    public static function setDesc(string $desc): void
    {
        if ($desc) {
            static::$desc = $desc;
        }
    }

    /**
     * @return bool
     */
    public static function isCoroutine(): bool
    {
        return static::$coroutine;
    }

    /**
     * @param bool|mixed $coroutine
     */
    public static function setCoroutine(mixed $coroutine): void
    {
        static::$coroutine = (bool)$coroutine;
    }

    /**
     * @return string
     */
    public function getProcessTitle(): string
    {
        return $this->processTitle;
    }

    /**
     * @param string $processTitle
     */
    public function setProcessTitle(string $processTitle): void
    {
        $this->processTitle = $processTitle;
    }
}
