<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:40
 */

namespace Inhere\Console;

use Inhere\Console\Annotate\DocblockRules;
use Inhere\Console\Component\ErrorHandler;
use Inhere\Console\Concern\AttachApplicationTrait;
use Inhere\Console\Concern\CommandHelpTrait;
use Inhere\Console\Concern\InputOutputAwareTrait;
use Inhere\Console\Concern\SubCommandsWareTrait;
use Inhere\Console\Concern\UserInteractAwareTrait;
use Inhere\Console\Contract\CommandHandlerInterface;
use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputDefinition;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\FormatUtil;
use Inhere\Console\Util\Helper;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Swoole\Coroutine;
use Swoole\Event;
use Throwable;
use Toolkit\PFlag\FlagsParser;
use Toolkit\PFlag\SFlags;
use Toolkit\Stdlib\Obj\ConfigObject;
use Toolkit\Stdlib\Util\PhpDoc;
use function array_keys;
use function array_merge;
use function cli_set_process_title;
use function error_get_last;
use function function_exists;
use function implode;
use function is_array;
use function is_string;
use function preg_replace;
use function sprintf;
use function ucfirst;
use const PHP_EOL;
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
     * @param Input  $input
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

    protected function initForRun(Input $input): void
    {
        $input->setFs($this->flags);
        $this->flags->setScriptName(self::getName());
        $this->flags->setDesc(self::getDescription());

        // load built in options
        // $builtInOpts = GlobalOption::getAloneOptions();
        $builtInOpts = $this->getBuiltInOptions();
        $this->flags->addOptsByRules($builtInOpts);

        // set options by options()
        $optRules = $this->options();
        $this->flags->addOptsByRules($optRules);
        $this->flags->setBeforePrintHelp(function (string $text) {
            return $this->parseCommentsVars($text);
        });
        $this->flags->setHelpRenderer(function () {
            $this->logf(Console::VERB_DEBUG, 'show help message by input flags: -h, --help');
            $this->showHelp();
        });
    }

    protected function getBuiltInOptions(): array
    {
        return GlobalOption::getAloneOptions();
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
            $this->initForRun($this->input);

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

            }
        }

        // $this->debugf('begin run command. load configure for command');
        // // load input definition configure
        // $this->configure();

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
     * @return bool
     */
    public function coExecute(): bool
    {
        // $ch = new Coroutine\Channel(1);
        $ok = Coroutine::create(function () {
            $this->execute($this->input, $this->output);
            // $ch->push($result);
        });

        // if create co fail
        if ((int)$ok === 0) {
            // if open debug, output a tips
            $this->logf(Console::VERB_DEBUG, 'ERROR: The coroutine create failed');

            // exec by normal flow
            $result = $this->execute($this->input, $this->output);
        } else { // success: wait coroutine exec.
            Event::wait();
            $result = 0;
            // $result = $ch->pop(10);
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
     * @param Input  $input
     * @param Output $output
     *
     * @return int|mixed
     */
    abstract protected function execute($input, $output);

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
     * @throws \ReflectionException
     */
    public function loadRulesByDocblock(string $method, FlagsParser $fs): void
    {
        $this->debugf('not config flags, load flag rules by docblock, method: %s', $method);
        $rftMth = new ReflectionMethod($this, $method);

        // parse doc for get flag rules
        $dr = DocblockRules::newByDocblock($rftMth->getDocComment());
        $dr->parse();

        $fs->addArgsByRules($dr->getArgRules());
        $fs->addOptsByRules($dr->getOptRules());
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

    /**
     * @param InputDefinition $definition
     * @param array           $aliases
     */
    protected function showHelpByDefinition(InputDefinition $definition, array $aliases = []): void
    {
        $this->log(Console::VERB_DEBUG, 'display help information by input definition');

        // if has InputDefinition object. (The comment of the command will not be parsed and used at this time.)
        $help = $definition->getSynopsis();
        // parse example
        $example = $help['example:'];
        if (!empty($example)) {
            if (is_string($example)) {
                $help['example:'] = $this->parseCommentsVars($example);
            } elseif (is_array($example)) {
                foreach ($example as &$item) {
                    $item = $this->parseCommentsVars($item);
                }
                unset($item);
            } else {
                $help['example:'] = '';
            }
        }

        $binName = $this->getScriptName();

        // build usage
        if ($this->isAttached()) {
            $help['usage:'] = sprintf('%s %s %s', $binName, $this->getCommandName(), $help['usage:']);
        } else {
            $help['usage:'] = $binName . ' ' . $help['usage:'];
        }

        // align global options
        $help['global options:'] = FormatUtil::alignOptions(GlobalOption::getOptions());

        $isAlone = $this->isAlone();
        if ($isAlone && empty($help[0])) {
            $help[0] = self::getDescription();
        }

        if (empty($help[0])) {
            $help[0] = 'No description message for the command';
        }

        // output description
        $this->write(ucfirst($help[0]) . PHP_EOL);

        if ($aliases) {
            $this->output->writef('<comment>Alias:</comment> %s', implode(',', $aliases));
        }

        unset($help[0]);
        $this->output->mList($help, ['sepChar' => '  ']);
    }

    /**
     * Display command/action help by parse method annotations
     *
     * @param string $method
     * @param string $action action of an group
     * @param array  $aliases
     *
     * @return int
     */
    protected function showHelpByAnnotations(string $method, string $action = '', array $aliases = []): int
    {
        $ref  = new ReflectionClass($this);
        $name = $this->input->getCommand();

        if (!$ref->hasMethod($method)) {
            $subCmd = $this->input->getSubCommand();
            $this->write("The command '<info>$subCmd</info>' dont exist in the group: " . static::getName());
            return 0;
        }

        // subcommand: is a console controller subcommand
        if ($action && !$ref->getMethod($method)->isPublic()) {
            $this->write("The command [<info>$name</info>] don't allow access in the class.");
            return 0;
        }

        $allowedTags = array_keys(self::$annotationTags);
        $this->logf(Console::VERB_DEBUG, "render help for the command: %s", $this->input->getCommandId());

        $help = [];
        $doc  = $ref->getMethod($method)->getDocComment();
        $tags = PhpDoc::getTags($this->parseCommentsVars((string)$doc), [
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
        $isCommand = $ref->isSubclassOf(CommandInterface::class);
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
            $help['Global Options:'] = FormatUtil::alignOptions($app->getFlags()->getOptSimpleDefines());
        }

        $this->output->mList($help, [
            'sepChar'     => '  ',
            'lastNewline' => 0,
        ]);

        return 0;
    }

    protected function beforeRenderCommandHelp(array &$help): void
    {
    }

    /**************************************************************************
     * getter/setter methods
     **************************************************************************/

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
     * @return array
     */
    final public static function getAnnotationTags(): array
    {
        return self::$annotationTags;
    }

    /**
     * @param string $name
     */
    public static function addAnnotationTag(string $name): void
    {
        if (!isset(self::$annotationTags[$name])) {
            self::$annotationTags[$name] = true;
        }
    }

    /**
     * @param array $annotationTags
     * @param bool  $replace
     */
    public static function setAnnotationTags(array $annotationTags, $replace = false): void
    {
        self::$annotationTags = $replace ? $annotationTags : array_merge(self::$annotationTags, $annotationTags);
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
