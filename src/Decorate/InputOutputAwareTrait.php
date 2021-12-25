<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Decorate;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Toolkit\PFlag\FlagsParser;

/**
 * Class InputOutputAwareTrait
 *
 * @package Inhere\Console\Decorate
 */
trait InputOutputAwareTrait
{
    /**
     * @var FlagsParser|null
     */
    protected ?FlagsParser $flags;

    /**
     * @var Input|null
     */
    protected ?Input $input;

    /**
     * @var Output|null
     */
    protected ?Output $output;

    /**
     * @return string
     */
    public function getScript(): string
    {
        return $this->input->getScriptFile();
    }

    /**
     * @return string
     */
    public function getWorkdir(): string
    {
        return $this->input->getWorkDir();
    }

    /**
     * @return string
     */
    public function getScriptName(): string
    {
        return $this->input->getScriptName();
    }

    /**
     * @param string $question
     * @param bool $nl
     *
     * @return string
     */
    public function readln(string $question = '', bool $nl = false): string
    {
        return $this->input->readln($question, $nl);
    }

    /**
     * @param mixed $message
     *
     * @return int
     */
    public function write(mixed $message): int
    {
        return $this->output->write($message);
    }

    /**
     * @param mixed $message
     *
     * @return int
     */
    public function writeln(mixed $message): int
    {
        return $this->output->writeln($message);
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
    public function setInput(Input $input): void
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
    public function setOutput(Output $output): void
    {
        $this->output = $output;
    }

    /**
     * @param Input $input
     * @param Output $output
     *
     * @return static
     */
    public function setInputOutput(Input $input, Output $output): static
    {
        $this->input = $input;
        $this->output = $output;
        return $this;
    }

    /**
     * @return FlagsParser
     */
    public function getFlags(): FlagsParser
    {
        return $this->flags;
    }

    /**
     * @param FlagsParser $flags
     */
    public function setFlags(FlagsParser $flags): void
    {
        $this->flags = $flags;
    }
}
