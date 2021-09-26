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
use Toolkit\PFlag\FlagsParser;
use Toolkit\PFlag\SFlags;
use function array_shift;
use function basename;
use function getcwd;
use function implode;
use function is_int;
use function is_string;
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
     * Global flags parser
     *
     * @var FlagsParser|SFlags
     */
    protected $gfs;

    /**
     * Command flags parser
     *
     * @var FlagsParser|SFlags
     */
    protected $fs;

    /**
     * @var string
     */
    protected $pwd = '';

    /**
     * The bin script file
     * e.g `./bin/app` OR `bin/cli.php`
     *
     * @var string
     */
    protected $scriptFile = '';

    /**
     * The bin script name
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
     * Raw input argv data.
     * - first element is script file
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
     * @param array $rawFlags
     */
    protected function collectInfo(array $rawFlags): void
    {
        $this->getPwd();
        if (!$rawFlags) {
            return;
        }

        $this->tokens = $rawFlags;

        // first is bin file
        if (isset($rawFlags[0]) && is_string($rawFlags[0])) {
            $this->scriptFile = array_shift($rawFlags);

            // bin name
            $this->scriptName = basename($this->scriptFile);
        }

        $this->flags = $rawFlags; // no script

        // full script
        $this->fullScript = implode(' ', $rawFlags);
    }

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

    public function popFirstArg()
    {
        return array_shift($this->args);
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

    /**
     * @return bool
     */
    public function isInteractive(): bool
    {
        return false;
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
    public function getScriptFile(): string
    {
        return $this->scriptFile;
    }

    /**
     * @return string
     */
    public function getScriptPath(): string
    {
        return $this->scriptFile;
    }

    /**
     * @param string $scriptFile
     */
    public function setScriptFile(string $scriptFile): void
    {
        if ($scriptFile) {
            $this->scriptFile = $scriptFile;
            // update scriptName
            $this->scriptName = basename($scriptFile);
        }
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
     * @param array $flags
     */
    public function setFlags(array $flags): void
    {
        $this->flags = $flags;
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
        $this->collectInfo($tokens);
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

    /**
     * @return FlagsParser
     */
    public function getGfs(): FlagsParser
    {
        return $this->gfs;
    }

    /**
     * @param FlagsParser $gfs
     */
    public function setGfs(FlagsParser $gfs): void
    {
        $this->gfs = $gfs;
    }

    /**
     * @return FlagsParser
     */
    public function getFs(): FlagsParser
    {
        return $this->fs;
    }

    /**
     * @param FlagsParser $fs
     */
    public function setFs(FlagsParser $fs): void
    {
        $this->fs = $fs;
    }
}
