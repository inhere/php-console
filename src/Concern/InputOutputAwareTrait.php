<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Concern;

use Inhere\Console\IO\Input;
use Inhere\Console\Contract\InputInterface;
use Inhere\Console\IO\Output;
use Inhere\Console\Contract\OutputInterface;
use Toolkit\PFlag\FlagsParser;
use Toolkit\PFlag\SFlags;

/**
 * Class InputOutputAwareTrait
 *
 * @package Inhere\Console\Concern
 */
trait InputOutputAwareTrait
{
    /**
     * @var SFlags|FlagsParser
     */
    protected $flags;

    /**
     * @var Input|InputInterface
     */
    protected $input;

    /**
     * @var Output|OutputInterface
     */
    protected $output;

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
    public function getScriptName(): string
    {
        return $this->input->getScriptName();
    }

    /**
     * @param string $question
     * @param bool   $nl
     *
     * @return string
     */
    public function readln(string $question = '', bool $nl = false): string
    {
        return $this->input->readln($question, $nl);
    }

    /**
     * @param mixed    $message
     * @param bool     $nl
     * @param bool|int $quit
     *
     * @return int
     */
    public function write($message, bool $nl = true, $quit = false): int
    {
        return $this->output->write($message, $nl, $quit);
    }

    /**
     * @param mixed    $message
     * @param bool|int $quit
     *
     * @return int
     */
    public function writeln($message, $quit = false): int
    {
        return $this->output->write($message, true, $quit);
    }

    /**
     * @return Input|InputInterface
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    /**
     * @return Output|OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @param Output|OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @return FlagsParser|SFlags
     */
    public function getFlags(): FlagsParser
    {
        return $this->flags;
    }

    /**
     * @param FlagsParser|SFlags $flags
     */
    public function setFlags(FlagsParser $flags): void
    {
        $this->flags = $flags;
    }
}
