<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:40
 */

namespace inhere\console;

use inhere\console\helpers\Annotation;
use inhere\console\io\Input;
use inhere\console\io\Output;
use inhere\console\traits\TraitInputOutput;
use inhere\console\traits\TraitInteract;

/**
 * Class AbstractCommand
 * @package inhere\console
 */
abstract class AbstractCommand
{
    use TraitInputOutput;
    use TraitInteract;

    // command description message
    // please use the const setting current controller/command description
    const DESCRIPTION = '';

    // name -> {$name}
    const ANNOTATION_VAR = '{$%s}';

    /**
     * TODO ...
     * command description message
     * please use the property setting current controller/command description
     * @var string
     */
    public static $description = '';

    /**
     * command name e.g 'test' 'test:one'
     * @var string
     */
    public static $name = '';

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
     * Command constructor.
     * @param Input $input
     * @param Output $output
     */
    public function __construct(Input $input, Output $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param string $arg
     */
    abstract public function run($arg = '');

    /**
     * @param string $action
     */
    protected function beforeRun($action)
    {
    }

    /**
     * @param string $action
     */
    protected function afterRun($action)
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
            'command' => $this->input->getCommand(),
            'name' => static::$name,
        ];
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

        $m = $ref->getMethod($method);
        $tags = Annotation::tagList($m->getDocComment());

        foreach ($tags as $tag => $msg) {
            if (!is_string($msg)) {
                continue;
            }

            if (isset(self::$allowTags[$tag])) {
                // need multi align
                if (self::$allowTags[$tag]) {
                    $msg = implode("\n  ", array_filter(explode("\n", $msg), 'trim'));
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
    public static function getName(): string
    {
        return static::$name;
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
     */
    public static function setAllowTags(array $allowTags)
    {
        self::$allowTags = $allowTags;
    }
}
