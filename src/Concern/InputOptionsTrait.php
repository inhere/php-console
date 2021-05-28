<?php declare(strict_types=1);

namespace Inhere\Console\Concern;

use Inhere\Console\Exception\PromptException;
use function array_map;
use function array_merge;
use function explode;
use function is_array;
use function is_bool;
use function is_string;

/**
 * Trait InputOptionsTrait
 *
 * @package Inhere\Console\Concern
 */
trait InputOptionsTrait
{
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
     * Alias of the getOpt()
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
     * Get a required option value
     *
     * @param string $name
     *
     * @param string $errMsg
     *
     * @return mixed
     */
    public function getRequiredOpt(string $name, string $errMsg = '')
    {
        if (null !== ($val = $this->getOpt($name))) {
            return $val;
        }

        $errMsg = $errMsg ?: "The option '{$name}' is required";
        throw new PromptException($errMsg);
    }

    /**
     * Get an string option(long/short) value
     *
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function getStringOpt(string $name, string $default = ''): string
    {
        return (string)$this->getOpt($name, $default);
    }

    /**
     * Get an string option(long/short) value
     *
     * @param string|string[] $names eg 'n,name' OR ['n', 'name']
     * @param string $default
     *
     * @return string
     */
    public function getSameStringOpt($names, string $default = ''): string
    {
        return (string)$this->getSameOpt($names, $default);
    }

    /**
     * Get an int option(long/short) value
     *
     * @param string $name
     * @param int    $default
     *
     * @return int
     */
    public function getIntOpt(string $name, int $default = 0): int
    {
        return (int)$this->getOpt($name, $default);
    }

    /**
     * Get an int option(long/short) value
     *
     * @param string|string[] $names eg 'l,length' OR ['l', 'length']
     * @param int $default
     *
     * @return int
     */
    public function getSameIntOpt($names, int $default = 0): int
    {
        return (int)$this->getSameOpt($names, $default);
    }

    /**
     * Get (long/short)option value(bool)
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
     * Get (long/short)option value(bool)
     * eg: -h --help
     *
     * @param string|string[] $names eg 'n,name' OR ['n', 'name']
     * @param bool     $default
     *
     * @return bool
     */
    public function getSameBoolOpt($names, bool $default = false): bool
    {
        return (bool)$this->getSameOpt($names, $default);
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
     * $input->sameOpt('h,help');
     * $input->sameOpt(['h','help']);
     * ```
     *
     * @param string|string[] $names eg 'n,name' OR ['n', 'name']
     * @param mixed $default
     *
     * @return bool|mixed|null
     */
    public function getSameOpt($names, $default = null)
    {
        if (is_string($names)) {
            $names = array_map('trim', explode(',', $names));
        } elseif (!is_array($names)) {
            $names = (array)$names;
        }

        foreach ($names as $name) {
            if ($this->hasOpt($name)) {
                return $this->getOpt($name);
            }
        }

        return $default;
    }

    /**
     * Alias of the getSameOpt()
     *
     * @param string|array $names
     * @param mixed  $default
     *
     * @return bool|mixed|null
     */
    public function sameOpt($names, $default = null)
    {
        return $this->getSameOpt($names, $default);
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
     * Check short-opt exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasSOpt(string $name): bool
    {
        return isset($this->sOpts[$name]);
    }

    /**
     * Check multi short-opt exists
     *
     * @param string[] $names
     *
     * @return string
     */
    public function findOneShortOpts(array $names): string
    {
        foreach ($names as $name) {
            if (isset($this->sOpts[$name])) {
                return $name;
            }
        }

        return '';
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
}
