<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:40
 */

namespace Inhere\Console;

use Inhere\Console\Contract\CommandHandlerInterface;
use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputDefinition;
use Inhere\Console\IO\Output;
use Inhere\Console\Concern\InputOutputAwareTrait;
use Inhere\Console\Concern\UserInteractAwareTrait;
use Inhere\Console\Util\FormatUtil;
use Inhere\Console\Util\Helper;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Swoole\Coroutine;
use Swoole\Event;
use Toolkit\Stdlib\Util\PhpDoc;
use function array_diff_key;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function cli_set_process_title;
use function count;
use function error_get_last;
use function explode;
use function function_exists;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function preg_replace;
use function setproctitle;
use function sprintf;
use function strpos;
use function strtr;
use function ucfirst;
use const ARRAY_FILTER_USE_BOTH;
use const PHP_EOL;
use const PHP_OS;

/**
 * Class AbstractHandler
 *
 * @package Inhere\Console
 */
abstract class AbstractHandler implements CommandHandlerInterface
{
    use InputOutputAwareTrait, UserInteractAwareTrait;

    /**
     * command name e.g 'test' 'test:one'
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
     * @var Application
     */
    protected $app;

    /**
     * @var InputDefinition|null
     */
    private $definition;

    /**
     * @var string
     */
    private $processTitle = '';

    /**
     * @var array
     */
    private $commentsVars;

    /**
     * Mark the command/controller is attached in application.
     *
     * @var bool
     */
    private $attached = true;

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
     * @param Input                $input
     * @param Output               $output
     * @param InputDefinition|null $definition
     */
    public function __construct(Input $input, Output $output, InputDefinition $definition = null)
    {
        $this->input  = $input;
        $this->output = $output;

        if ($definition) {
            $this->definition = $definition;
        }

        $this->commentsVars = $this->annotationVars();

        $this->init();
    }

    protected function init(): void
    {
    }

    /**
     * Configure input definition for command, like symfony console.
     */
    protected function configure(): void
    {
    }

    /**
     * @return InputDefinition
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    protected function createDefinition(): InputDefinition
    {
        if (!$this->definition) {
            $this->definition = new InputDefinition();
            $this->definition->setDescription(self::getDescription());
        }

        return $this->definition;
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
        $binFile = $this->input->getScript(); // bin/app
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
     * run command
     *
     * @param string $command
     *
     * @return int|mixed
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function run(string $command = '')
    {
        // load input definition configure
        $this->configure();

        if ($this->input->sameOpt(['h', 'help'])) {
            $this->showHelp();
            return 0;
        }

        // some prepare check
        if (true !== $this->prepare()) {
            return -1;
        }

        // return False to deny go on
        if (false === $this->beforeExecute()) {
            return -1;
        }

        // if enable swoole coroutine
        if (static::isCoroutine() && Helper::isSupportCoroutine()) {
            $result = $this->coroutineRun();
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
    public function coroutineRun(): bool
    {
        // $ch = new Coroutine\Channel(1);
        $ok = Coroutine::create(function () {
            $this->execute($this->input, $this->output);
            // $ch->push($result);
        });

        // create co fail:
        if ((int)$ok === 0) {
            // if open debug, output a tips
            if ($this->isDebug()) {
                $this->output->warning('The coroutine create failed!');
            }

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

        return $this->validateInput();
    }

    /**
     * validate input arguments and options
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateInput(): bool
    {
        if (!$def = $this->definition) {
            return true;
        }

        $in  = $this->input;
        $out = $this->output;

        $givenArgs = $errArgs = [];
        foreach ($in->getArgs() as $key => $value) {
            if (is_int($key)) {
                $givenArgs[$key] = $value;
            } else {
                $errArgs[] = $key;
            }
        }

        if (count($errArgs) > 0) {
            $out->liteError(sprintf('Unknown arguments (error: "%s").', implode(', ', $errArgs)));
            return false;
        }

        $defArgs     = $def->getArguments();
        $missingArgs = array_filter(array_keys($defArgs), static function ($name, $key) use ($def, $givenArgs) {
            return !array_key_exists($key, $givenArgs) && $def->argumentIsRequired($name);
        }, ARRAY_FILTER_USE_BOTH);

        if (count($missingArgs) > 0) {
            $out->liteError(sprintf('Not enough arguments (missing: "%s").', implode(', ', $missingArgs)));
            return false;
        }

        $index = 0;
        $args  = [];

        foreach ($defArgs as $name => $conf) {
            $args[$name] = $givenArgs[$index] ?? $conf['default'];
            $index++;
        }

        $in->setArgs($args);
        $this->checkNotExistsOptions($def);

        // check options
        $opts = $missingOpts = [];

        $defOpts = $def->getOptions();
        foreach ($defOpts as $name => $conf) {
            if (!$in->hasLOpt($name)) {
                // support multi short: 'a|b|c'
                $shortNames = $conf['shortcut'] ? explode('|', $conf['shortcut']) : [];
                if ($srt = $in->findOneShortOpts($shortNames)) {
                    $opts[$name] = $in->sOpt($srt);
                } elseif ($conf['default'] !== null) {
                    $opts[$name] = $conf['default'];
                } elseif ($conf['required']) {
                    $missingOpts[] = "--{$name}" . ($srt ? "|-{$srt}" : '');
                }
            }
        }

        if (count($missingOpts) > 0) {
            $out->liteError(sprintf('Not enough options parameters (missing: "%s").', implode(', ', $missingOpts)));
            return false;
        }

        if ($opts) {
            $in->setLOpts($opts);
        }

        return true;
    }

    private function checkNotExistsOptions(InputDefinition $def): void
    {
        $givenOpts  = $this->input->getOptions();
        $allDefOpts = $def->getAllOptionNames();

        // check unknown options
        if ($unknown = array_diff_key($givenOpts, $allDefOpts)) {
            $names = array_keys($unknown);

            // $first = array_shift($names);
            $first = '';
            foreach ($names as $name) {
                if (!Application::isGlobalOption($name)) {
                    $first = $name;
                    break;
                }
            }

            if (!$first) {
                return;
            }

            $errMsg = sprintf('Input option is not exists (unknown: "%s").', (isset($first[1]) ? '--' : '-') . $first);
            throw new InvalidArgumentException($errMsg);
        }

    }

    /**************************************************************************
     * helper methods
     **************************************************************************/

    /**
     * @param string       $name
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
     * @param string       $name
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
    protected function parseCommentsVars(string $str): string
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
     * @return bool
     */
    public function isAlone(): bool
    {
        return $this instanceof CommandInterface;
    }

    /**********************************************************
     * display help information
     **********************************************************/

    /**
     * Display help information
     *
     * @return bool
     */
    protected function showHelp(): bool
    {
        if (!$definition = $this->getDefinition()) {
            return false;
        }

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
        if ($this->attached) {
            $help['usage:'] = sprintf('%s %s %s', $binName, $this->getCommandName(), $help['usage:']);
        } else {
            $help['usage:'] = $binName . ' ' . $help['usage:'];
        }

        // align global options
        $help['global options:'] = FormatUtil::alignOptions(Application::getGlobalOptions());

        if (empty($help[0]) && $this->isAlone()) {
            $help[0] = self::getDescription();
        }

        if (empty($help[0])) {
            $help[0] = 'No description message for the command';
        }

        // output description
        $this->write(ucfirst($help[0]) . PHP_EOL);
        unset($help[0]);

        $this->output->mList($help, ['sepChar' => '  ']);
        return true;
    }

    /**
     * Display command/action help by parse method annotations
     *
     * @param string $method
     * @param string $action
     * @param array  $aliases
     *
     * @return int
     * @throws ReflectionException
     */
    protected function showHelpByMethodAnnotations(string $method, string $action = '', array $aliases = []): int
    {
        $ref  = new ReflectionClass($this);
        $name = $this->input->getCommand();

        $this->log(Console::VERB_CRAZY, "display help info for the method=$method", [
            'class'  =>  static::class,
            'action' => $action,
        ]);

        if (!$ref->hasMethod($method)) {
            $this->write("The command [<info>$name</info>] don't exist in the group: " . static::getName());
            return 0;
        }

        // is a console controller command
        if ($action && !$ref->getMethod($method)->isPublic()) {
            $this->write("The command [<info>$name</info>] don't allow access in the class.");
            return 0;
        }

        $help = [];
        $doc  = $ref->getMethod($method)->getDocComment();
        $tags = PhpDoc::getTags($this->parseCommentsVars($doc));

        if ($aliases) {
            $realName = $action ?: static::getName();
            // command name
            $help['Command:'] = sprintf('%s(alias: <info>%s</info>)', $realName, implode(',', $aliases));
        }

        // is an command object
        $isCommand = $ref->isSubclassOf(CommandInterface::class);
        foreach (array_keys(self::$annotationTags) as $tag) {
            if (empty($tags[$tag]) || !is_string($tags[$tag])) {
                // for alone command
                if ($tag === 'description' && $isCommand) {
                    $help['Description:'] = static::getDescription();
                    continue;
                }

                if ($tag === 'usage') {
                    $help['Usage:'] = $this->commentsVars['binWithCmd'] . ' [--options ...] [arguments ...]';
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

        $help['Group Options:']  = null;
        $help['Global Options:'] = FormatUtil::alignOptions(Application::getGlobalOptions());

        $this->beforeRenderCommandHelp($help);

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
    public static function getDescription(): string
    {
        return static::$description;
    }

    /**
     * @param string $description
     */
    public static function setDescription(string $description): void
    {
        static::$description = $description;
    }

    /**
     * @return bool
     */
    public static function isCoroutine(): bool
    {
        return static::$coroutine;
    }

    /**
     * @param bool $coroutine
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
     * @return bool
     */
    public function isInteractive(): bool
    {
        if ($this->app) {
            return $this->app->isInteractive();
        }

        $value = $this->input->getBoolOpt(GlobalOption::NO_INTERACTIVE);

        return $value === false;
    }

    /**
     * Get current debug level value
     *
     * @return int
     */
    public function getVerbLevel(): int
    {
        if ($this->app) {
            return $this->app->getVerbLevel();
        }

        return (int)$this->input->getLongOpt('debug', Console::VERB_ERROR);
    }

    /**
     * @param int    $level
     * @param string $format
     * @param mixed  ...$args
     */
    public function logf(int $level, string $format, ...$args): void
    {
        if ($this->getVerbLevel() < $level) {
            return;
        }

        Console::logf($level, $format, ...$args);
    }

    /**
     * @param int    $level
     * @param string $message
     * @param array  $extra
     */
    public function log(int $level, string $message, array $extra = []): void
    {
        if ($this->getVerbLevel() < $level) {
            return;
        }

        Console::log($message, $extra, $level);
    }

    /**
     * @return InputDefinition|null
     */
    public function getDefinition(): ?InputDefinition
    {
        return $this->definition;
    }

    /**
     * @param InputDefinition $definition
     */
    public function setDefinition(InputDefinition $definition): void
    {
        $this->definition = $definition;
    }

    /**
     * @return array
     */
    public function getCommentsVars(): array
    {
        return $this->commentsVars;
    }

    /**
     * @return AbstractApplication
     */
    public function getApp(): AbstractApplication
    {
        return $this->app;
    }

    /**
     * @param AbstractApplication $app
     */
    public function setApp(AbstractApplication $app): void
    {
        $this->app = $app;
    }

    /**
     * @return bool
     */
    public function isAttached(): bool
    {
        return $this->attached;
    }

    /**
     * @return bool
     */
    public function isDetached(): bool
    {
        return $this->attached === false;
    }

    /**
     * @param bool $attached
     */
    public function setAttached(bool $attached): void
    {
        $this->attached = $attached;
    }

    /**
     * Detached running
     */
    public function setDetached(): void
    {
        $this->attached = false;
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
