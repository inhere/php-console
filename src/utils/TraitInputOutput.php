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
    public function getScriptName(): string
    {
        return $this->input->getScript();
    }

    /**
     * @param string $msg
     * @return string
     */
    protected function read($msg = ''): string
    {
        return $this->input->read($msg);
    }

    /**
     * @param mixed $message
     * @param bool $nl
     * @param bool|int $quit
     * @return int
     */
    protected function write($message, $nl = true, $quit = false): int
    {
        return $this->output->write($message, $nl, $quit);
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
