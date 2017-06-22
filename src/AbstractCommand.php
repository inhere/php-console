<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:40
 */

namespace inhere\console;

use inhere\console\io\Input;
use inhere\console\io\Output;
use inhere\console\utils\TraitInputOutput;
use inhere\console\utils\TraitInteract;

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
     * allow display message tags in the command
     * @var array
     */
    protected static $allowTags = ['description', 'usage', 'example'];

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

    abstract public function run($arg = '');

    protected function beforeRun($action)
    {
    }

    protected function afterRun($action)
    {
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
