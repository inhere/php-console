<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:38
 */

namespace inhere\console\utils;

use inhere\console\io\Input;
use inhere\console\io\Output;

/**
 * Class TraitInputOutput
 * @package inhere\console\utils
 */
trait TraitInputOutput
{
    /**
     * @var Input
     */
    protected $input;

    /**
     * @var Output
     */
    protected $output;


    /**
     * @return Input
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param Input $input
     */
    public function setInput(Input $input)
    {
        $this->input = $input;
    }

    /**
     * @return Output
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param Output $output
     */
    public function setOutput(Output $output)
    {
        $this->output = $output;
    }

}