<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-02-03
 * Time: 10:24
 */

namespace Inhere\Console\IO;

use InvalidArgumentException;
use function array_merge;
use function getcwd;
use function is_bool;
use function is_int;
use function trim;

/**
 * Class AbstractInput
 *
 * @package Inhere\Console\IO
 */
abstract class AbstractInput implements InputInterface
{
    /**
     * @var string
     */
    protected $pwd;

    /**
     * the script name
     * e.g `./bin/app` OR `bin/cli.php`
     *
     * @var string
     */
    protected $script;

    /**
     * the command name(Is first argument)
     * e.g `start` OR `start`
     *
     * @var string
     */
    protected $command = '';

    /**
     * eg `./examples/app home:useArg status=2 name=john arg0 -s=test --page=23`
     *
     * @var string
     */
    protected $fullScript;

    /**
     * raw argv data.
     *
     * @var array
     */
    protected $tokens;

    /**
     * Input args data
     *
     * @var array
     */
    protected $args = [];

    /**
     * Input short-opts data
     *
     * @var array
     */
    protected $sOpts = [];

    /**
     * Input long-opts data
     *
     * @var array
     */
    protected $lOpts = [];

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
     * find command name. it is first argument.
     */
    protected function findCommand(): void
    {
        if (!isset($this->args[0])) {
            return;
        }

        $newArgs = [];

        foreach ($this->args as $key => $value) {
            if ($key === 0) {
                $this->command = trim($value);
            } elseif (is_int($key)) {
                $newArgs[] = $value;
            } else {
                $newArgs[$key] = $value;
            }
        }

        $this->args = $newArgs;
    }

    /***********************************************************************************
     * arguments (eg: arg0 name=john city=chengdu)
     ***********************************************************************************/

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->getArgs();
    }

    /**
     * @param array $args
     * @param bool  $replace
     */
    public function setArgs(array $args, $replace = false): void
    {
        $this->args = $replace ? $args : array_merge($this->args, $args);
    }

    /**
     * @param string|int $name
     *
     * @return bool
     */
    public function hasArg($name): bool
    {
        return isset($this->args[$name]);
    }

    /**
     * get Argument
     *
     * @param null|int|string $name
     * @param mixed           $default
     *
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        return $this->get($name, $default);
    }

    /**
     * get Argument
     *
     * @param null|int|string $name
     * @param mixed           $default
     *
     * @return mixed
     */
    public function getArg($name, $default = null)
    {
        return $this->get($name, $default);
    }

    /**
     * get Argument
     *
     * @param null|int|string $name
     * @param mixed           $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return $this->args[$name] ?? $default;
    }

    /**
     * get a required argument
     *
     * @param int|string $name argument index
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getRequiredArg($name)
    {
        if ('' !== $this->get($name, '')) {
            return $this->args[$name];
        }

        throw new InvalidArgumentException("The argument '{$name}' is required");
    }

    /**
     * get first argument
     *
     * @param string $default
     *
     * @return string
     */
    public function getFirstArg(string $default = ''): string
    {
        return $this->get(0, $default);
    }

    /**
     * get second argument
     *
     * @param string $default
     *
     * @return string
     */
    public function getSecondArg(string $default = ''): string
    {
        return $this->get(1, $default);
    }

    /**
     * @param string|int $key
     * @param int        $default
     *
     * @return int
     */
    public function getInt($key, $default = 0): int
    {
        $value = $this->get($key);

        return $value === null ? (int)$default : (int)$value;
    }

    /**
     * get same args value
     * eg: des description
     *
     * ```php
     * $input->sameArg(['des', 'description']);
     * ```
     *
     * @param array $names
     * @param mixed $default
     *
     * @return bool|mixed|null
     */
    public function getSameArg(array $names, $default = null)
    {
        return $this->sameArg($names, $default);
    }

    /**
     * @param array $names
     * @param mixed $default
     *
     * @return mixed
     */
    public function sameArg(array $names, $default = null)
    {
        foreach ($names as $name) {
            if ($this->hasArg($name)) {
                return $this->get($name);
            }
        }

        return $default;
    }

    /**
     * clear args
     */
    public function clearArgs(): void
    {
        $this->args = [];
    }

    /***********************************************************************************
     * long/short options (eg: -d --help)
     ***********************************************************************************/

    /**
     * get (long/short)opt value
     * eg: -e dev --name sam
     *
     * @param string $name
     * @param null   $default
     *
     * @return bool|mixed|null
     */
    public function getOpt(string $name, $default = null)
    {
        // It's long-opt
        if (isset($name[1])) {
            return $this->lOpt($name, $default);
        }

        return $this->sOpt($name, $default);
    }

    /**
     * alias of the getOpt()
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption(string $name, $default = null)
    {
        return $this->getOpt($name, $default);
    }

    /**
     * get a required argument
     *
     * @param string $name
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getRequiredOpt(string $name)
    {
        if (null === ($val = $this->getOpt($name))) {
            throw new InvalidArgumentException("The option '{$name}' is required");
        }

        return $val;
    }

    /**
     * get (long/short)opt value(bool)
     * eg: -h --help
     *
     * @param string $name
     * @param bool   $default
     *
     * @return bool
     */
    public function getBoolOpt(string $name, bool $default = false): bool
    {
        return (bool)$this->getOpt($name, $default);
    }

    /**
     * Alias of the getBoolOpt()
     *
     * @param string $name
     * @param bool   $default
     *
     * @return bool
     */
    public function boolOpt(string $name, bool $default = false): bool
    {
        return (bool)$this->getOpt($name, $default);
    }

    /**
     * check option exists
     *
     * @param $name
     *
     * @return bool
     */
    public function hasOpt(string $name): bool
    {
        return isset($this->sOpts[$name]) || isset($this->lOpts[$name]);
    }

    /**
     * Get same opts value
     * eg: -h --help
     *
     * ```php
     * $input->sameOpt(['h','help']);
     * ```
     *
     * @param array $names
     * @param mixed $default
     *
     * @return bool|mixed|null
     */
    public function getSameOpt(array $names, $default = null)
    {
        return $this->sameOpt($names, $default);
    }

    /**
     * Alias of the getSameOpt()
     *
     * @param array $names
     * @param null  $default
     *
     * @return bool|mixed|null
     */
    public function sameOpt(array $names, $default = null)
    {
        foreach ($names as $name) {
            if ($this->hasOpt($name)) {
                return $this->getOpt($name);
            }
        }

        return $default;
    }

    /**
     * @return array
     */
    public function getOpts(): array
    {
        return array_merge($this->sOpts, $this->lOpts);
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->getOpts();
    }

    /**
     * clear (l/s)opts
     */
    public function clearOpts(): void
    {
        $this->sOpts = $this->lOpts = [];
    }

    /************************** short-opts **********************/

    /**
     * Get short-opt value
     *
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function sOpt(string $name, $default = null)
    {
        return $this->sOpts[$name] ?? $default;
    }

    /**
     * Alias of the sOpt()
     *
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function shortOpt(string $name, $default = null)
    {
        return $this->sOpts[$name] ?? $default;
    }

    /**
     * Alias of the sOpt()
     *
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function getShortOpt(string $name, $default = null)
    {
        return $this->sOpts[$name] ?? $default;
    }

    /**
     * check short-opt exists
     *
     * @param $name
     *
     * @return bool
     */
    public function hasSOpt(string $name): bool
    {
        return isset($this->sOpts[$name]);
    }

    /**
     * get short-opt value(bool)
     *
     * @param string $name
     * @param bool   $default
     *
     * @return bool
     */
    public function sBoolOpt(string $name, $default = false): bool
    {
        $val = $this->sOpt($name);

        return is_bool($val) ? $val : (bool)$default;
    }

    /**
     * @return array
     */
    public function getShortOpts(): array
    {
        return $this->sOpts;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setSOpt(string $name, $value): void
    {
        $this->sOpts[$name] = $value;
    }

    /**
     * @return array
     */
    public function getSOpts(): array
    {
        return $this->sOpts;
    }

    /**
     * @param array $sOpts
     * @param bool  $replace
     */
    public function setSOpts(array $sOpts, bool $replace = false): void
    {
        $this->sOpts = $replace ? $sOpts : array_merge($this->sOpts, $sOpts);
    }

    /**
     * clear s-opts
     */
    public function clearSOpts(): void
    {
        $this->sOpts = [];
    }

    /************************** long-opts **********************/

    /**
     * Alias of the getLongOpt()
     *
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function lOpt(string $name, $default = null)
    {
        return $this->lOpts[$name] ?? $default;
    }

    /**
     * Alias of the getLongOpt()
     *
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function longOpt(string $name, $default = null)
    {
        return $this->lOpts[$name] ?? $default;
    }

    /**
     * Get long-opt value
     *
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function getLongOpt(string $name, $default = null)
    {
        return $this->lOpts[$name] ?? $default;
    }

    /**
     * check long-opt exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasLOpt(string $name): bool
    {
        return isset($this->lOpts[$name]);
    }

    /**
     * get long-opt value(bool)
     *
     * @param string $name
     * @param bool   $default
     *
     * @return bool
     */
    public function lBoolOpt(string $name, $default = false): bool
    {
        $val = $this->lOpt($name);

        return is_bool($val) ? $val : (bool)$default;
    }

    /**
     * @return array
     */
    public function getLongOpts(): array
    {
        return $this->lOpts;
    }

    /**
     * @param string $name
     * @param        $value
     */
    public function setLOpt(string $name, $value): void
    {
        $this->lOpts[$name] = $value;
    }

    /**
     * @return array
     */
    public function getLOpts(): array
    {
        return $this->lOpts;
    }

    /**
     * @param array $lOpts
     * @param bool  $replace
     */
    public function setLOpts(array $lOpts, bool $replace = false): void
    {
        $this->lOpts = $replace ? $lOpts : array_merge($this->lOpts, $lOpts);
    }

    /**
     * clear lang opts
     */
    public function clearLOpts(): void
    {
        $this->lOpts = [];
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
}
