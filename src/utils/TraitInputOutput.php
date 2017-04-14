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
     * @return string
     */
    public function getScriptName()
    {
        return $this->input->getScript();
    }

    /**
     * @param string $msg
     * @return string
     */
    protected function read($msg = '')
    {
        return $this->input->read($msg);
    }

    /**
     * @param $message
     * @param bool $nl
     * @param bool $quit
     */
    protected function write($message, $nl = true, $quit = false)
    {
        $this->output->write($message, $nl, $quit);
    }

    /**
     * @return Input
     */
    public function getInput(): Input
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
    public function getOutput(): Output
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
