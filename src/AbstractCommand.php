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
     * command name e.g 'test' 'test:one'
     * @var string
     */
    private $name = '';

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

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
    public static function setAllowTags($allowTags)
    {
        self::$allowTags = $allowTags;
    }
}
