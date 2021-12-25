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
use Toolkit\PFlag\FlagsParser;

/**
 * Class CallableCommand - wrap an callable as Command
 *
 * @package Inhere\Console\Handler
 */
class CallableCommand extends Command
{
    /**
     * @var callable(FlagsParser, Output): void
     */
    private $callable;

    /**
     * @var array{options: array, arguments: array}
     */
    protected array $config = [];

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
     * @param callable $fn
     *
     * @return $this
     */
    public function withFunc(callable $fn): self
    {
        return $this->setCallable($fn);
    }

    /**
     * @param callable $fn
     *
     * @return $this
     */
    public function withCustom(callable $fn): self
    {
        $fn($this);
        return $this;
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function withConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function withOptions(array $options): self
    {
        $this->config['options'] = $options;
        return $this;
    }

    /**
     * @param array $arguments
     *
     * @return $this
     */
    public function withArguments(array $arguments): self
    {
        $this->config['arguments'] = $arguments;
        return $this;
    }

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
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Do execute command
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return mixed
     */
    protected function execute(Input $input, Output $output): mixed
    {
        if (!$call = $this->callable) {
            throw new BadMethodCallException('The callable property is empty');
        }

        // call custom callable
        return $call($this->flags, $output);
    }
}
