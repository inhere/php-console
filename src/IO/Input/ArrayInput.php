<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/30 0030
 * Time: 23:41
 */

namespace Inhere\Console\IO\Input;

use Inhere\Console\IO\Input;
use Inhere\Console\Utils\InputParser;

/**
 * Class ArrayInput
 * @package Inhere\Console\IO\Input
 */
class ArrayInput extends Input
{
    /**
     * Input constructor.
     * @param null|array $argv
     * @param bool $parsing
     */
    public function __construct(array $argv = null, $parsing = true)
    {
        parent::__construct([], false);

        $this->tokens = $argv;
        $this->script = \array_shift($argv);
        $this->fullScript = \implode(' ', $argv);

        if ($parsing && $argv) {
            list($this->args, $this->sOpts, $this->lOpts) = InputParser::fromArray($argv);

            // collect command. it is first argument.
            $this->command = isset($this->args[0]) ? \array_shift($this->args) : null;
        }
    }

}
