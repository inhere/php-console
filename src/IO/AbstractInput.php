<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-02-03
 * Time: 10:24
 */

namespace Inhere\Console\IO;

use Inhere\Console\Concern\InputArgumentsTrait;
use Inhere\Console\Concern\InputOptionsTrait;
use Inhere\Console\Contract\InputInterface;
use function getcwd;
use function is_int;
use function trim;

/**
 * Class AbstractInput
 *
 * @package Inhere\Console\IO
 */
abstract class AbstractInput implements InputInterface
{
    use InputArgumentsTrait, InputOptionsTrait;

    /**
     * @var string
     */
    protected $pwd = '';

    /**
     * The script path
     * e.g `./bin/app` OR `bin/cli.php`
     *
     * @var string
     */
    protected $script = '';

    /**
     * The script name
     * e.g `app` OR `cli.php`
     *
     * @var string
     */
    protected $scriptName = '';

    /**
     * the command name(Is first argument)
     * e.g `git` OR `start`
     *
     * @var string
     */
    protected $command = '';

    /**
     * the command name(Is first argument)
     * e.g `subcmd` in the `./app group subcmd`
     *
     * @var string
     */
    protected $subCommand = '';

    /**
     * eg `./examples/app home:useArg status=2 name=john arg0 -s=test --page=23`
     *
     * @var string
     */
    protected $fullScript;

    /**
     * Raw argv data.
     *
     * @var array
     */
    protected $tokens;

    /**
     * Same the $tokens but no $script
     *
     * @var array
     */
    protected $flags = [];

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    abstract public function toString(): string;

    /**
     * find command name, it is first argument.
     * TIP: will reset args data after founded.
     */
    public function findCommandName(): string
    {
        if (!isset($this->args[0])) {
            return '';
        }

        $command = '';
        $newArgs = [];
        foreach ($this->args as $key => $value) {
            if ($key === 0) {
                $command = trim($value);
            } elseif (is_int($key)) {
                $newArgs[] = $value;
            } else {
                $newArgs[$key] = $value;
            }
        }

        if ($command) {
            $this->args = $newArgs;
        }

        return $command;
    }

    /**
     * @return string
     */
    public function getCommandPath(): string
    {
        $path = $this->command;
        if ($this->subCommand) {
            $path .= ' ' . $this->subCommand;
        }

        return $path;
    }

    /***********************************************************************************
     * getter/setter
     ***********************************************************************************/

    /**
     * @return string
     */
    public function getPwd(): string
    {
        if (!$this->pwd) {
            $this->pwd = (string)getcwd();
        }

        return $this->pwd;
    }

    /**
     * @return string
     */
    public function getWorkDir(): string
    {
        return $this->getPwd();
    }

    /**
     * @param string $pwd
     */
    public function setPwd(string $pwd): void
    {
        $this->pwd = $pwd;
    }

    /**
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * @param string $script
     */
    public function setScript(string $script): void
    {
        $this->script = $script;
    }

    /**
     * @return string
     */
    public function getScriptName(): string
    {
        return $this->scriptName;
    }

    /**
     * @return string
     */
    public function getBinName(): string
    {
        return $this->scriptName;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getFullScript(): string
    {
        return $this->fullScript;
    }

    /**
     * @param string $fullScript
     */
    public function setFullScript(string $fullScript): void
    {
        $this->fullScript = $fullScript;
    }

    /**
     * @return array
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * @return array
     */
    public function getRawFlags(): array
    {
        return $this->tokens;
    }

    /**
     * @return array
     */
    public function getRawArgs(): array
    {
        return $this->tokens;
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * @param array $tokens
     */
    public function setTokens(array $tokens): void
    {
        $this->tokens = $tokens;
    }

    /**
     * @return string
     */
    public function getSubCommand(): string
    {
        return $this->subCommand;
    }

    /**
     * @param string $subCommand
     */
    public function setSubCommand(string $subCommand): void
    {
        $this->subCommand = $subCommand;
    }
}
