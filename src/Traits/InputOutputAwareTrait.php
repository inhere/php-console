<?php

/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:38
 */

namespace Inhere\Console\Traits;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

/**
 * Class InputOutputAwareTrait
 * @package Inhere\Console\Traits
 */
trait InputOutputAwareTrait
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
     * @see Input::getArg()
     * {@inheritdoc}
     */
    public function getArg($name, $default = null)
    {
        return $this->input->getArg($name, $default);
    }

    /**
     * {@inheritdoc}
     * @see Input::getRequiredArg()
     * @throws \InvalidArgumentException
     */
    public function getRequiredArg($name)
    {
        return $this->input->getRequiredArg($name);
    }

    /**
     * {@inheritdoc}
     * @see Input::getSameArg()
     */
    public function getSameArg(array $names, $default = null)
    {
        return $this->input->getSameArg($names, $default);
    }

    /**
     * {@inheritdoc}
     * @see Input::getOpt()
     */
    public function getOpt($name, $default = null)
    {
        return $this->input->getOpt($name, $default);
    }

    /**
     * {@inheritdoc}
     * @see Input::getSameOpt()
     */
    public function getSameOpt(array $names, $default = null)
    {
        return $this->input->getSameOpt($names, $default);
    }

    /**
     * {@inheritdoc}
     * @see Input::getRequiredOpt()
     * @throws \InvalidArgumentException
     */
    public function getRequiredOpt($name)
    {
        return $this->input->getRequiredOpt($name);
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
     * @param mixed $message
     * @param bool $nl
     * @param bool|int $quit
     * @return int
     */
    protected function write($message, $nl = true, $quit = false)
    {
        return $this->output->write($message, $nl, $quit);
    }

    /**
     * @param mixed $message
     * @param bool|int $quit
     * @return int
     */
    protected function writeln($message, $quit = false)
    {
        return $this->output->write($message, true, $quit);
    }

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