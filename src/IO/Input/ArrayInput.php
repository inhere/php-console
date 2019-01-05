<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/30 0030
 * Time: 23:41
 */

namespace Inhere\Console\IO\Input;

use Inhere\Console\IO\Input;
use Inhere\Console\Util\InputParser;

/**
 * Class ArrayInput
 * @package Inhere\Console\IO\Input
 */
class ArrayInput extends Input
{
    /**
     * Input constructor.
     * @param null|array $args
     * @param bool       $parsing
     */
    public function __construct(array $args = null, bool $parsing = true)
    {
        parent::__construct([], false);

        $this->tokens = $args;
        $this->script = \array_shift($args);
        $this->fullScript = \implode(' ', $args);

        if ($parsing && $args) {
            list($this->args, $this->sOpts, $this->lOpts) = InputParser::fromArray($args);

            // collect command. it is first argument.
            $this->command = isset($this->args[0]) ? \array_shift($this->args) : null;
        }
    }

}
