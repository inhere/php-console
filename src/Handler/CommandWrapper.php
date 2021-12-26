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
 * Class CommandWrapper - wrap an callable as Command
 *
 * @package Inhere\Console\Handler
 */
class CommandWrapper extends Command
{
    /**
     * @var callable(FlagsParser, Output): mixed
     */
    private $callable;

    /**
     * @var array{name: string, desc: string, options: array, arguments: array}
     */
    protected array $config = [];

    /**
     * @param callable(FlagsParser, Output):mixed $handleFn
     *
     * @return $this
     */
    public static function new(callable $handleFn, array $config = []): self
    {
        return (new self())->withConfig($config)->setCallable($handleFn);
    }

    /**
     * @param callable(FlagsParser, Output):mixed $handleFn
     * @param array $config
     *
     * @return static
     */
    public static function wrap(callable $handleFn, array $config = []): self
    {
        return (new self())->withConfig($config)->setCallable($handleFn);
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
     * @param callable(FlagsParser, Output):mixed $fn
     *
     * @return $this
     */
    public function withFunc(callable $fn): self
    {
        return $this->setCallable($fn);
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
     * @param callable(FlagsParser, Output):mixed $callable
     *
     * @return static
     */
    public function setCallable(callable $callable): self
    {
        $this->callable = $callable;
        return $this;
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return $this->config['options'] ?? [];
    }

    /**
     * @return array
     */
    protected function getArguments(): array
    {
        return $this->config['arguments'] ?? [];
    }

    /**
     * @return string
     */
    public function getRealDesc(): string
    {
        return $this->config['desc'] ?? '';
    }

    /**
     * @return string
     */
    public function getRealName(): string
    {
        return $this->config['name'] ?? '';
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
            throw new BadMethodCallException('The command handler property is empty');
        }

        // call custom callable
        return $call($this->flags, $output);
    }
}
