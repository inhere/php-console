<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:40
 */

namespace inhere\console;

use inhere\console\io\Input;
use inhere\console\io\InputDefinition;
use inhere\console\io\Output;
use inhere\console\traits\InputOutputTrait;
use inhere\console\traits\UserInteractTrait;
use inhere\console\utils\Annotation;

/**
 * Class AbstractCommand
 * @package inhere\console
 */
abstract class AbstractCommand
{
    use InputOutputTrait;
    use UserInteractTrait;

    // name -> {$name}
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
    protected static $allowTags = [
        // tag name => multi line align
        'description' => false,
        'usage' => false,
        'arguments' => true,
        'options' => true,
        'example' => true,
    ];

    /**
     * @var InputDefinition
     */
    private $definition;

    /**
     * @var string
     */
    private $processTitle;

    ////// for strict mode //////

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
    }

    /**
     * configure input definition
     */
    protected function configure()
    {
        return null;
    }

    /**
     * validate input arguments and options
     * @return bool
     */
    public function validate()
    {
        if (!$definition = $this->definition) {
            return true;
        }

        $givenArgs = $this->input->getArgs();

        $missingArgs = array_filter(array_keys($definition->getArguments()), function ($name) use ($definition, $givenArgs) {
            return !array_key_exists($name, $givenArgs) && $definition->argumentIsRequired($name);
        });

        if (count($missingArgs) > 0) {
            throw new \RuntimeException(sprintf('Not enough arguments (missing: "%s").', implode(', ', $missingArgs)));
        }

        return true;
    }

    /**
     * @return InputDefinition
     */
    protected function createDefinition()
    {
        $this->definition = new InputDefinition();

        return $this->definition;
    }

    /**
     * run
     */
    abstract public function run();

    /**
     * beforeRun
     */
    protected function beforeRun()
    {
        if ($this->processTitle) {
            if (function_exists('cli_set_process_title')) {
                if (false === @cli_set_process_title($this->processTitle)) {
                    if ('Darwin' === PHP_OS) {
                        $this->output->writeln('<comment>Running "cli_get_process_title" as an unprivileged user is not supported on MacOS.</comment>');
                    } else {
                        $error = error_get_last();
                        trigger_error($error['message'], E_USER_WARNING);
                    }
                }
            } elseif (function_exists('setproctitle')) {
                setproctitle($this->processTitle);
//            } elseif (isDebug) {
//                $output->writeln('<comment>Install the proctitle PECL to be able to change the process title.</comment>');
            }
        }

        $this->validate();
    }

    /**
     * afterRun
     */
    protected function afterRun()
    {
    }

    /**
     * 为命令注解提供可解析解析变量. 可以在命令的注释中使用
     * @return array
     */
    protected function annotationVars()
    {
        // e.g: `more info see {$name}/index`
        return [
            'script' => $this->input->getScript(),
            'command' => $this->input->getCommand(),
            'name' => self::getName(),
        ];
    }

    /**
     * 为命令注解提供可解析解析变量. 可以在命令的注释中使用
     * @param string $str
     * @return string
     */
    protected function replaceAnnotationVars($str)
    {
        $map = [];

        foreach ($this->annotationVars() as $key => $value) {
            $key = sprintf(self::ANNOTATION_VAR, $key);
            $map[$key] = $value;
        }

        return $map ? strtr($str, $map) : $str;
    }

    /**
     * show help by parse method annotation
     * @param string    $method
     * @param null|string $action
     * @return int
     */
    protected function showHelpByMethodAnnotation($method, $action = null)
    {
        $ref = new \ReflectionClass($this);
        $cName = lcfirst(self::getName() ?: $ref->getShortName());

        if (!$ref->hasMethod($method) || !$ref->getMethod($method)->isPublic()) {
            $name = $action ? "$cName/$action" : $cName;
            $this->write("Command [<info>$name</info>] don't exist or don't allow access in the class.");

            return 0;
        }

        $doc = $ref->getMethod($method)->getDocComment();
        $tags = Annotation::tagList($this->replaceAnnotationVars($doc));

        foreach ($tags as $tag => $msg) {
            if (!is_string($msg)) {
                continue;
            }

            if (isset(self::$allowTags[$tag])) {
                // need multi align
                if (self::$allowTags[$tag]) {
                    $lines = array_map(function ($line) {
                        return trim($line);
                    }, explode("\n", $msg));

                    $msg = implode("\n  ", array_filter($lines, 'trim'));
                }

                $tag = ucfirst($tag);
                $this->write("<comment>$tag:</comment>\n  $msg\n");
            }
        }

        return 0;
    }

    /**
     * handle action/command runtime exception
     *
     * @param  \Throwable $e
     * @throws \Throwable
     */
    protected function handleRuntimeException(\Throwable $e)
    {
        throw $e;
    }

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
    final public static function getDescription(): string
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
    public static function getAllowTags(): array
    {
        return self::$allowTags;
    }

    /**
     * @param array $allowTags
     * @param bool $replace
     */
    public static function setAllowTags(array $allowTags, $replace = false)
    {
        self::$allowTags = $replace ? $allowTags : array_merge(self::$allowTags, $allowTags);
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
}
