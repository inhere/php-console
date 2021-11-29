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
     * @var FlagsParser|null
     */
    protected ?FlagsParser $flags;

    /**
     * @var InputInterface|null
     */
    protected ?InputInterface $input;

    /**
     * @var OutputInterface|null
     */
    protected ?OutputInterface $output;

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
     * @param bool   $nl
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
