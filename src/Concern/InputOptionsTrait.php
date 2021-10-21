<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

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
     * @return array
     */
    public function getOpts(): array
    {
        return array_merge($this->sOpts, $this->lOpts);
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
     * clear s-opts
     */
    public function clearSOpts(): void
    {
        $this->sOpts = [];
    }

    /************************** long-opts **********************/

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
     * clear lang opts
     */
    public function clearLOpts(): void
    {
        $this->lOpts = [];
    }
}
