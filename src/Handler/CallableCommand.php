<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Handler;

use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use BadMethodCallException;

/**
 * Class CallableCommand - wrap an callable as Command
 *
 * @package Inhere\Console\Handler
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

    // /**
    //  * @param callable $cb
    //  *
    //  * @return static
    //  */
    // public static function wrap(callable $cb): self
    // {
    //     return (new self())->setCallable($cb);
    // }

    /**
     * @param callable $callable
     *
     * @return CallableCommand
     */
    public function setCallable(callable $callable): self
    {
        $this->callable = $callable;
        return $this;
    }

    /**
     * Do execute command
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return int|mixed
     */
    protected function execute(Input $input, Output $output): mixed
    {
        if (!$call = $this->callable) {
            throw new BadMethodCallException('The callable property is empty');
        }

        // call custom callable
        return $call($input, $output);
    }
}
