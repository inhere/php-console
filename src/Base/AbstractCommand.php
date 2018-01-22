<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:40
 */

namespace Inhere\Console\Base;

use Inhere\Console\Application;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputDefinition;
use Inhere\Console\IO\Output;
use Inhere\Console\Traits\InputOutputAwareTrait;
use Inhere\Console\Traits\UserInteractAwareTrait;
use Inhere\Console\Utils\Annotation;
use Inhere\Console\Utils\FormatUtil;

/**
 * Class AbstractCommand
 * @package Inhere\Console
 */
abstract class AbstractCommand implements BaseCommandInterface
{
    use InputOutputAwareTrait, UserInteractAwareTrait;

    const OK = 0;
    // name -> {name}
    const ANNOTATION_VAR = '{%s}'; // '{$%s}';

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
     * Allow display message tags in the command annotation
     * @var array
     */
    protected static $annotationTags = [
        // tag name => multi line align
        'description' => false,
        'usage' => false,
        'arguments' => true,
        'options' => true,
        'example' => true,
    ];

    /** @var Application */
    protected $app;

    /** @var InputDefinition|null */
    private $definition;

    /** @var string */
    private $processTitle;

    /** @var array */
    private $annotationVars;

    /**
     * Command constructor.
     * @param Input $input
     * @param Output $output
     * @param InputDefinition|null $definition
     */
    public function __construct(Input $input, Output $output, InputDefinition $definition = null)
    {
        $this->input = $input;
        $this->output = $output;

        if ($definition) {
            $this->definition = $definition;
        }

        $this->init();

        $this->annotationVars = $this->annotationVars();
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
     */
    protected function createDefinition()
    {
        if (!$this->definition) {
            $this->definition = new InputDefinition();
        }

        return $this->definition;
    }

    /**
     * 为命令注解提供可解析解析变量. 可以在命令的注释中使用
     * @return array
     */
    public function annotationVars()
    {
        // e.g: `more info see {name}:index`
        return [
            'name' => self::getName(),
            'group' => self::getName(),
            'workDir' => $this->input->getPwd(),
            'script' => $this->input->getScript(), // bin/app
            'command' => $this->input->getCommand(), // demo OR home:test
            'fullCommand' => $this->input->getScript() . ' ' . $this->input->getCommand(),
        ];
    }

    /**************************************************************************
     * running a command
     **************************************************************************/

    /**
     * run command
     * @param string $command
     * @return int
     */
    public function run($command = '')
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

        if (true !== $this->beforeExecute()) {
            return -1;
        }

        $status = (int)$this->execute($this->input, $this->output);
        $this->afterExecute();

        return $status;
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
     * do execute
     * @param  Input $input
     * @param  Output $output
     * @return int
     */
    abstract protected function execute($input, $output);

    /**
     * after command execute
     */
    protected function afterExecute()
    {
    }

    /**
     * display help information
     * @return bool
     */
    protected function showHelp(): bool
    {
        if (!$def = $this->getDefinition()) {
            return false;
        }

        // 创建了 InputDefinition , 则使用它的信息(此时不会再解析和使用命令的注释)
        $info = $def->getSynopsis();
        $info['usage'] = sprintf('%s %s %s',
            $this->input->getScript(),
            $this->input->getCommand(),
            $info['usage']
        );

        $this->output->mList($info, ['sepChar' => '  ']);

        return true;

    }

    /**
     * prepare run
     * @throws \RuntimeException
     */
    protected function prepare()
    {
        if ($this->processTitle) {
            if (\function_exists('cli_set_process_title')) {
                if (false === @cli_set_process_title($this->processTitle)) {
                    $error = error_get_last();

                    if ($error && 'Darwin' !== PHP_OS) {
                        throw new \RuntimeException($error['message']);
                    }
                }
            } elseif (\function_exists('setproctitle')) {
                setproctitle($this->processTitle);
            }
        }

        return $this->validateInput();
    }

    /**
     * validate input arguments and options
     * @return bool
     */
    public function validateInput(): bool
    {
        if (!$def = $this->definition) {
            return true;
        }

        $in = $this->input;
        $givenArgs = $errArgs = [];

        foreach ($in->getArgs() as $key => $value) {
            if (\is_int($key)) {
                $givenArgs[$key] = $value;
            } else {
                $errArgs[] = $key;
            }
        }

        if (\count($errArgs) > 0) {
            $this->output->liteError(sprintf('Unknown arguments (error: "%s").', implode(', ', $errArgs)));

            return false;
        }

        $defArgs = $def->getArguments();
        $missingArgs = array_filter(array_keys($defArgs), function ($name, $key) use ($def, $givenArgs) {
            return !array_key_exists($key, $givenArgs) && $def->argumentIsRequired($name);
        }, ARRAY_FILTER_USE_BOTH);

        if (\count($missingArgs) > 0) {
            $this->output->liteError(sprintf('Not enough arguments (missing: "%s").', implode(', ', $missingArgs)));

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
        if ($unknown = array_diff_key($givenOpts, $defOpts)) {
            $names = array_keys($unknown);
            $first = array_shift($names);

            throw new \InvalidArgumentException(sprintf(
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
            $this->output->liteError(
                sprintf('Not enough options parameters (missing: "%s").', implode(', ', $missingOpts))
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
     * @param string $name
     * @param string $value
     */
    protected function setAnnotationVar(string $name, $value)
    {
        $this->annotationVars[$name] = (string)$value;
    }

    /**
     * 替换注解中的变量为对应的值
     * @param string $str
     * @return string
     */
    protected function parseAnnotationVars($str)
    {
        // not use vars
        if (false === strpos($str, '{')) {
            return $str;
        }

        static $map;

        if ($map === null) {
            foreach ($this->annotationVars as $key => $value) {
                $key = sprintf(self::ANNOTATION_VAR, $key);
                $map[$key] = $value;
            }
        }

        return $map ? strtr($str, $map) : $str;
    }

    /**
     * show help by parse method annotations
     * @param string $method
     * @param null|string $action
     * @param array $aliases
     * @return int
     */
    protected function showHelpByMethodAnnotations($method, $action = null, array $aliases = [])
    {
        $ref = new \ReflectionClass($this);
        $name = $this->input->getCommand();
        $isAlone = $ref->isSubclassOf(CommandInterface::class);

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
        $tags = Annotation::getTags($this->parseAnnotationVars($doc));
        $help = [];

        if ($aliases) {
            $help['Alias Name:'] = implode(',', $aliases);
        }

        foreach (array_keys(self::$annotationTags) as $tag) {
            if (empty($tags[$tag]) || !\is_string($tags[$tag])) {
                continue;
            }

            // $msg = trim($tags[$tag]);
            $msg = $tags[$tag];
            $tag = ucfirst($tag);

            // for alone command
            if (!$msg && $tag === 'description' && $isAlone) {
                $msg = self::getDescription();
            } else {
                $msg = preg_replace('#(\n)#', '$1 ', $msg);
            }

            $help[$tag . ':'] = $msg;
        }

        if (isset($help['Description:'])) {
            $description = $help['Description:'] ?: 'No description message for the command';
            $this->write(ucfirst($description) . PHP_EOL);
            unset($help['Description:']);
        }

        $help['Global Options:'] = FormatUtil::alignmentOptions(Application::getInternalOptions());

        $this->output->mList($help, [
            'sepChar' => '  ',
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
    public static function setName(string $name)
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
    final public static function getDescription()
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
     * @return array
     */
    public static function getAnnotationTags(): array
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
     * @param bool $replace
     */
    public static function setAnnotationTags(array $annotationTags, $replace = false)
    {
        self::$annotationTags = $replace ? $annotationTags : array_merge(self::$annotationTags, $annotationTags);
    }

    /**
     * @return InputDefinition
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
