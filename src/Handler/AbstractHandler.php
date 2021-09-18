<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:40
 */

namespace Inhere\Console\Handler;

use Inhere\Console\Annotate\DocblockRules;
use Inhere\Console\Component\ErrorHandler;
use Inhere\Console\Concern\AttachApplicationTrait;
use Inhere\Console\Concern\CommandHelpTrait;
use Inhere\Console\Concern\InputOutputAwareTrait;
use Inhere\Console\Concern\SubCommandsWareTrait;
use Inhere\Console\Concern\UserInteractAwareTrait;
use Inhere\Console\Console;
use Inhere\Console\ConsoleEvent;
use Inhere\Console\Contract\CommandHandlerInterface;
use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputDefinition;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\Helper;
use InvalidArgumentException;
use ReflectionException;
use RuntimeException;
use Throwable;
use Toolkit\PFlag\FlagsParser;
use Toolkit\PFlag\SFlags;
use Toolkit\Stdlib\Helper\PhpHelper;
use Toolkit\Stdlib\Obj\ConfigObject;
use function cli_set_process_title;
use function error_get_last;
use function function_exists;
use const PHP_OS;

/**
 * Class AbstractHandler
 *
 * @package Inhere\Console
 */
abstract class AbstractHandler implements CommandHandlerInterface
{
    use AttachApplicationTrait;
    use CommandHelpTrait;
    use InputOutputAwareTrait;
    use UserInteractAwareTrait;
    use SubCommandsWareTrait;

    /**
     * group/command name e.g 'test' 'test:one'
     *
     * @var string
     */
    protected static $name = '';

    /**
     * command/controller description message
     * please use the property setting current controller/command description
     *
     * @var string
     */
    protected static $description = '';

    /**
     * @var bool Whether enable coroutine. It is require swoole extension.
     */
    protected static $coroutine = false;

    /**
     * Allow display message tags in the command annotation
     *
     * @var array
     */
    protected static $annotationTags = [
        // tag name => multi line align
        'description' => false,
        'usage'       => false,
        'arguments'   => true,
        'options'     => true,
        'example'     => true,
        'help'        => true,
    ];

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * Compatible mode run command.
     *
     * @var bool
     */
    protected $compatible = true;

    /**
     * @var InputDefinition|null
     */
    protected $definition;

    /**
     * @var string
     */
    protected $processTitle = '';

    /**
     * @var ConfigObject
     */
    protected $params;

    /**
     * @var array Command options
     */
    protected $commandOptions = [];

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
     * @param Input $input
     * @param Output $output
     */
    // TODO public function __construct(Input $input = null, Output $output = null, InputDefinition $definition = null)
    public function __construct(Input $input, Output $output)
    {
        $this->input  = $input;
        $this->output = $output;

        // init an flags object
        $this->flags = new SFlags();

        $this->init();
    }

    protected function init(): void
    {
        $this->commentsVars = $this->annotationVars();

        $this->afterInit();
        $this->debugf('attach inner subcommands to "%s"', self::getName());
        $this->addCommands($this->commands());
    }

    protected function afterInit(): void
    {
        // do something...
    }

    /**
     * command options
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
    protected function options(): array
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
        $fullCmd = $this->input->getFullCommand();
        $binFile = $this->input->getScriptFile(); // bin/app
        $binName = $this->input->getScriptName();
        $command = $this->input->getCommand();

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

    /**
     * @param Input $input
     */
    protected function initFlagsParser(Input $input): void
    {
        $input->setFs($this->flags);
        $this->flags->setDesc(self::getDesc());
        $this->flags->setScriptName(self::getName());

        $this->beforeInitFlagsParser($this->flags);

        // set options by options()
        $optRules = $this->options();
        $this->flags->addOptsByRules($optRules);

        // for render help
        $this->flags->setBeforePrintHelp(function (string $text) {
            return $this->parseCommentsVars($text);
        });
        $this->flags->setHelpRenderer(function () {
            $this->logf(Console::VERB_DEBUG, 'show help message by input flags: -h, --help');
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
     * @return bool|int|mixed
     * @throws Throwable
     */
    public function run(array $args)
    {
        $name = self::getName();

        try {
            $this->initFlagsParser($this->input);

            $this->log(Console::VERB_DEBUG, "begin run '$name' - parse options", ['args' => $args]);

            // parse options
            $this->flags->lock();
            if (!$this->flags->parse($args)) {
                return 0; // on error, help
            }

            $args = $this->flags->getRawArgs();

            return $this->doRun($args);
        } catch (Throwable $e) {
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
     * @return int|mixed
     */
    protected function doRun(array $args)
    {
        if (isset($args[0])) {
            $first = $args[0];
            $rName = $this->resolveAlias($first);

            if ($this->isSubCommand($rName)) {
                // TODO
            }
        }

        // some prepare check
        // - validate input arguments
        if (true !== $this->prepare()) {
            return -1;
        }

        // $this->dispatchCommand($name);

        // return False to deny goon run.
        if (false === $this->beforeExecute()) {
            return -1;
        }

        // only fire for alone command run.
        if ($this->isAlone()) {
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

    /**
     * coroutine run by swoole go()
     *
     * @return int
     */
    public function coExecute(): int
    {
        $cid = \Swoole\Coroutine\run(function () {
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
     * @return int|mixed
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
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function prepare(): bool
    {
        if ($this->processTitle && 'Darwin' !== PHP_OS) {
            if (function_exists('cli_set_process_title')) {
                cli_set_process_title($this->processTitle);
            } elseif (function_exists('setproctitle')) {
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
    public function isCommand(): bool
    {
        return $this instanceof CommandInterface;
    }

    /**
     * @return ConfigObject
     */
    public function getParams(): ConfigObject
    {
        if (!$this->params) {
            $this->initParams([]);
        }

        return $this->params;
    }

    /**
     * @param array $params
     *
     * @return ConfigObject
     */
    public function initParams(array $params): ConfigObject
    {
        $this->params = ConfigObject::new($params);
        return $this->params;
    }

    /**
     * @param string $method
     * @param FlagsParser $fs
     *
     * @throws ReflectionException
     */
    public function loadRulesByDocblock(string $method, FlagsParser $fs): void
    {
        $this->debugf('not config flags, load flag rules by docblock, method: %s', $method);
        $rftMth = PhpHelper::reflectMethod($this, $method);

        // parse doc for get flag rules
        $dr = DocblockRules::newByDocblock($rftMth->getDocComment());
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
    public function getRealName(): string
    {
        return self::getName();
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        $aliases = [];
        if ($this->app) {
            $aliases = $this->app->getAliases(self::getName());
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
        return static::$description;
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return static::$description;
    }

    /**
     * @param string $desc
     */
    public static function setDesc(string $desc): void
    {
        self::setDescription($desc);
    }

    /**
     * @param string $description
     */
    public static function setDescription(string $description): void
    {
        if ($description) {
            static::$description = $description;
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
    public static function setCoroutine($coroutine): void
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
