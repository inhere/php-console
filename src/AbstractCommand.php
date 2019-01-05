<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:40
 */

namespace Inhere\Console;

use Inhere\Console\Face\BaseCommandInterface;
use Inhere\Console\Face\CommandInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputDefinition;
use Inhere\Console\IO\Output;
use Inhere\Console\Traits\InputOutputAwareTrait;
use Inhere\Console\Traits\UserInteractAwareTrait;
use Inhere\Console\Util\FormatUtil;
use Inhere\Console\Util\Helper;
use Swoole\Coroutine;
use Toolkit\PhpUtil\PhpDoc;

/**
 * Class AbstractCommand
 * @package Inhere\Console
 */
abstract class AbstractCommand implements BaseCommandInterface
{
    use InputOutputAwareTrait, UserInteractAwareTrait;

    /**
     * command name e.g 'test' 'test:one'
     * @var string
     */
    protected static $name = '';

    /**
     * command/controller description message
     * please use the property setting current controller/command description
     * @var string
     */
    protected static $description = '';

    /**
     * @var bool Whether enable coroutine. It is require swoole extension.
     */
    protected static $coroutine = false;

    /**
     * Allow display message tags in the command annotation
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

    /** @var Application */
    protected $app;

    /** @var array common options for all sub-commands */
    private $commonOptions;

    /** @var InputDefinition|null */
    private $definition;

    /** @var string */
    private $processTitle = '';

    /** @var array */
    private $annotationVars;

    /**
     * Whether enabled
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return true;
    }

    /**
     * Setting current command/group name aliases
     * @return string[]
     */
    public static function aliases(): array
    {
        // return ['alias1', 'alias2'];
        return [];
    }

    /**
     * Command constructor.
     * @param Input                $input
     * @param Output               $output
     * @param InputDefinition|null $definition
     */
    public function __construct(Input $input, Output $output, InputDefinition $definition = null)
    {
        $this->input = $input;
        $this->output = $output;

        if ($definition) {
            $this->definition = $definition;
        }

        $this->commonOptions = $this->commonOptions();
        $this->annotationVars = $this->annotationVars();

        $this->init();
    }

    protected function init()
    {
    }

    /**
     * Configure input definition for command
     * @return InputDefinition|null
     */
    protected function configure()
    {
        return null;
    }

    /**
     * @return InputDefinition
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    protected function createDefinition(): InputDefinition
    {
        if (!$this->definition) {
            $this->definition = new InputDefinition();
        }

        return $this->definition;
    }

    /**
     * @return array
     */
    protected function commonOptions(): array
    {
        return [
            '--skip-invalid' => 'Whether ignore invalid arguments and options, when use input definition',
        ];
    }

    /**
     * 为命令注解提供可解析解析变量. 可以在命令的注释中使用
     * @return array
     */
    protected function annotationVars(): array
    {
        // e.g: `more info see {name}:index`
        return [
            'name'        => self::getName(),
            'group'       => self::getName(),
            'workDir'     => $this->input->getPwd(),
            'script'      => $this->input->getScript(), // bin/app
            'binName'     => $this->input->getScript(), // bin/app
            'command'     => $this->input->getCommand(), // demo OR home:test
            'fullCommand' => $this->input->getFullCommand(),
        ];
    }

    /**************************************************************************
     * running a command
     **************************************************************************/

    /**
     * run command
     * @param string $command
     * @return int|mixed
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
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
     * @return bool
     */
    public function coroutineRun()
    {
        $ch = new Coroutine\Channel(1);
        $ok = Coroutine::create(function () use ($ch) {
            $result = $this->execute($this->input, $this->output);
            $ch->push($result);
            // $this->getApp()->stop($status);
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
            // Event::wait();
            $result = $ch->pop(10);
        }

        return $result;
    }

    /**
     * before command execute
     * @return boolean It MUST return TRUE to continue execute.
     */
    protected function beforeExecute(): bool
    {
        return true;
    }

    /**
     * do execute command
     * @param  Input  $input
     * @param  Output $output
     * @return int|mixed
     */
    abstract protected function execute($input, $output);

    /**
     * after command execute
     */
    protected function afterExecute()
    {
    }

    /**
     * prepare run
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function prepare(): bool
    {
        if ($this->processTitle && 'Darwin' !== \PHP_OS) {
            if (\function_exists('cli_set_process_title')) {
                \cli_set_process_title($this->processTitle);
            } elseif (\function_exists('setproctitle')) {
                \setproctitle($this->processTitle);
            }

            if ($error = \error_get_last()) {
                throw new \RuntimeException($error['message']);
            }
        }

        return $this->validateInput();
    }

    /**
     * validate input arguments and options
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateInput(): bool
    {
        if (!$def = $this->definition) {
            return true;
        }

        $in = $this->input;
        $out = $this->output;
        $givenArgs = $errArgs = [];

        foreach ($in->getArgs() as $key => $value) {
            if (\is_int($key)) {
                $givenArgs[$key] = $value;
            } else {
                $errArgs[] = $key;
            }
        }

        if (\count($errArgs) > 0) {
            $out->liteError(\sprintf('Unknown arguments (error: "%s").', \implode(', ', $errArgs)));
            return false;
        }

        $defArgs = $def->getArguments();
        $missingArgs = \array_filter(\array_keys($defArgs), function ($name, $key) use ($def, $givenArgs) {
            return !\array_key_exists($key, $givenArgs) && $def->argumentIsRequired($name);
        }, \ARRAY_FILTER_USE_BOTH);

        if (\count($missingArgs) > 0) {
            $out->liteError(\sprintf('Not enough arguments (missing: "%s").', \implode(', ', $missingArgs)));
            return false;
        }

        $index = 0;
        $args = [];

        foreach ($defArgs as $name => $conf) {
            $args[$name] = $givenArgs[$index] ?? $conf['default'];
            $index++;
        }

        $in->setArgs($args);

        // check options
        $opts = $missingOpts = [];
        $givenOpts = $in->getOptions();
        $defOpts = $def->getOptions();

        // check unknown options
        if ($unknown = \array_diff_key($givenOpts, $defOpts)) {
            $names = \array_keys($unknown);
            $first = \array_shift($names);

            throw new \InvalidArgumentException(\sprintf(
                'Input option is not exists (unknown: "%s").',
                (isset($first[1]) ? '--' : '-') . $first
            ));
        }

        foreach ($defOpts as $name => $conf) {
            if (!$in->hasLOpt($name)) {
                if (($srt = $conf['shortcut']) && $in->hasSOpt($srt)) {
                    $opts[$name] = $in->sOpt($srt);
                } elseif ($conf['required']) {
                    $missingOpts[] = "--{$name}" . ($srt ? "|-{$srt}" : '');
                }
            }
        }

        if (\count($missingOpts) > 0) {
            $out->liteError(
                \sprintf('Not enough options parameters (missing: "%s").',
                    \implode(', ', $missingOpts))
            );

            return false;
        }

        if ($opts) {
            $in->setLOpts($opts);
        }

        return true;
    }

    /**************************************************************************
     * helper methods
     **************************************************************************/

    /**
     * @param string $name
     * @param string $value
     */
    protected function addAnnotationVar(string $name, $value)
    {
        if (!isset($this->annotationVars[$name])) {
            $this->annotationVars[$name] = (string)$value;
        }
    }

    /**
     * @param array $map
     */
    protected function addAnnotationVars(array $map)
    {
        foreach ($map as $name => $value) {
            $this->addAnnotationVar($name, $value);
        }
    }

    /**
     * @param string       $name
     * @param string|array $value
     */
    protected function setAnnotationVar(string $name, $value)
    {
        $this->annotationVars[$name] = \is_array($value) ? \implode(',', $value) : (string)$value;
    }

    /**
     * 替换注解中的变量为对应的值
     * @param string $str
     * @return string
     */
    protected function parseAnnotationVars(string $str): string
    {
        // not use vars
        if (false === \strpos($str, '{')) {
            return $str;
        }

        static $map;

        if ($map === null) {
            foreach ($this->annotationVars as $key => $value) {
                $key = \sprintf(self::ANNOTATION_VAR, $key);
                $map[$key] = $value;
            }
        }

        return $map ? \strtr($str, $map) : $str;
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
     * display help information
     * @return bool
     */
    protected function showHelp(): bool
    {
        if (!$definition = $this->getDefinition()) {
            return false;
        }

        // 创建了 InputDefinition , 则使用它的信息(此时不会再解析和使用命令的注释)
        $help = $definition->getSynopsis();
        $help['usage:'] = \sprintf('%s %s %s', $this->getScriptName(), $this->getCommandName(), $help['usage:']);
        $help['global options:'] = FormatUtil::alignOptions(Application::getInternalOptions());

        if (empty($help[0]) && $this->isAlone()) {
            $help[0] = self::getDescription();
        }

        if (empty($help[0])) {
            $help[0] = 'No description message for the command';
        }

        // output description
        $this->write(\ucfirst($help[0]) . \PHP_EOL);
        unset($help[0]);

        $this->output->mList($help, ['sepChar' => '  ']);
        return true;
    }

    /**
     * show help by parse method annotations
     * @param string      $method
     * @param null|string $action
     * @param array       $aliases
     * @return int
     * @throws \ReflectionException
     */
    protected function showHelpByMethodAnnotations(string $method, string $action = null, array $aliases = []): int
    {
        $ref = new \ReflectionClass($this);
        $name = $this->input->getCommand();

        if (!$ref->hasMethod($method)) {
            $this->write("The command [<info>$name</info>] don't exist in the group: " . static::getName());
            return 0;
        }

        // is a console controller command
        if ($action && !$ref->getMethod($method)->isPublic()) {
            $this->write("The command [<info>$name</info>] don't allow access in the class.");
            return 0;
        }

        $doc = $ref->getMethod($method)->getDocComment();
        $tags = PhpDoc::getTags($this->parseAnnotationVars($doc));
        $isAlone = $ref->isSubclassOf(CommandInterface::class);
        $help = [];

        if ($aliases) {
            $realName = $action ?: self::getName();
            $help['Command:'] = \sprintf('%s(alias: <info>%s</info>)', $realName, \implode(',', $aliases));
        }

        foreach (\array_keys(self::$annotationTags) as $tag) {
            if (empty($tags[$tag]) || !\is_string($tags[$tag])) {
                // for alone command
                if ($tag === 'description' && $isAlone) {
                    $help['Description:'] = self::getDescription();
                    continue;
                }

                if ($tag === 'usage') {
                    $help['Usage:'] = $this->annotationVars['fullCommand'] . ' [--options ...] [arguments ...]';
                }

                continue;
            }

            // $msg = trim($tags[$tag]);
            $msg = $tags[$tag];
            $tag = \ucfirst($tag);

            // for alone command
            if (!$msg && $tag === 'description' && $isAlone) {
                $msg = self::getDescription();
            } else {
                $msg = \preg_replace('#(\n)#', '$1 ', $msg);
            }

            $help[$tag . ':'] = $msg;
        }

        if (isset($help['Description:'])) {
            $description = $help['Description:'] ?: 'No description message for the command';
            $this->write(\ucfirst($description) . \PHP_EOL);
            unset($help['Description:']);
        }

        $help['Global Options:'] = FormatUtil::alignOptions(
            \array_merge(Application::getInternalOptions(),
            $this->commonOptions)
        );
        $this->output->mList($help, [
            'sepChar'     => '  ',
            'lastNewline' => 0,
        ]);

        return 0;
    }

    /**************************************************************************
     * getter/setter methods
     **************************************************************************/

    /**
     * @param string $name
     */
    final public static function setName(string $name)
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
    public static function setDescription(string $description)
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
    public static function setCoroutine($coroutine)
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
    public static function addAnnotationTag(string $name)
    {
        if (!isset(self::$annotationTags[$name])) {
            self::$annotationTags[$name] = true;
        }
    }

    /**
     * @param array $annotationTags
     * @param bool  $replace
     */
    public static function setAnnotationTags(array $annotationTags, $replace = false)
    {
        self::$annotationTags = $replace ? $annotationTags : array_merge(self::$annotationTags, $annotationTags);
    }

    /**
     * @return InputDefinition|null
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param InputDefinition $definition
     */
    public function setDefinition(InputDefinition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return array
     */
    public function getCommonOptions(): array
    {
        return $this->commonOptions;
    }

    /**
     * @return array
     */
    public function getAnnotationVars(): array
    {
        return $this->annotationVars;
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
    public function setApp(AbstractApplication $app)
    {
        $this->app = $app;
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
    public function setProcessTitle(string $processTitle)
    {
        $this->processTitle = $processTitle;
    }
}
