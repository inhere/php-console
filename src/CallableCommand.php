<?php declare(strict_types=1);

namespace Inhere\Console;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

/**
 * Class CommandWrapper
 *
 * @package Inhere\Console
 */
class CallableCommand extends Command
{
    /**
     * @var callable
     */
    private $callable;

    // public function new(callable $callable): self
    // {
    // }

    // public function __construct(Input $input, Output $output, InputDefinition $definition = null)
    // {
    //     parent::__construct($input, $output, $definition);
    // }

    /**
     * Do execute command
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return int|mixed
     */
    protected function execute($input, $output)
    {
        if (!$call = $this->callable) {
            throw new \BadMethodCallException('The callable property is empty');
        }

        // call custom callable
        return $call($input, $output);
    }

    /**
     * @param callable $callable
     */
    public function setCallable(callable $callable): void
    {
        $this->callable = $callable;
    }
}
