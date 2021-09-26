<?php declare(strict_types=1);

namespace Inhere\Console\Concern;

use Inhere\Console\Exception\PromptException;
use function array_merge;

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
     * @param null $default
     *
     * @return bool|mixed|null
     */
    public function getOpt(string $name, $default = null)
    {
        // It's long-opt
        if (isset($name[1])) {
            return $this->getLongOpt($name, $default);
        }

        return $this->getShortOpt($name, $default);
    }

    /**
     * Alias of the getOpt()
     *
     * @param string $name
     * @param mixed $default
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

        $errMsg = $errMsg ?: "The option '$name' is required";
        throw new PromptException($errMsg);
    }

    /**
     * check option exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasOpt(string $name): bool
    {
        return isset($this->sOpts[$name]) || isset($this->lOpts[$name]);
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
     * Alias of the sOpt()
     *
     * @param string $name
     * @param null $default
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
     * @return array
     */
    public function getShortOpts(): array
    {
        return $this->sOpts;
    }

    /**
     * @param string $name
     * @param mixed $value
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
     * @param bool $replace
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
     * @param null $default
     *
     * @return mixed|null
     */
    public function lOpt(string $name, $default = null)
    {
        return $this->lOpts[$name] ?? $default;
    }

    /**
     * Get long-opt value
     *
     * @param string $name
     * @param null $default
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
     * @return array
     */
    public function getLongOpts(): array
    {
        return $this->lOpts;
    }

    /**
     * @param string $name
     * @param mixed $value
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
     * @param bool $replace
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
