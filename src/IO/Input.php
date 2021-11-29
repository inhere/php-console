<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO;

use Inhere\Console\IO\Input\StreamInput;
use Toolkit\Cli\Cli;

/**
 * Class Input - The std input.
 *
 * @package Inhere\Console\IO
 */
class Input extends StreamInput
{
    /**
     * The real command ID(group:command)
     * e.g `http:start`
     *
     * @var string
     */
    protected string $commandId = '';

    /**
     * Input constructor.
     *
     * @param null|array $args
     */
    public function __construct(array $args = null)
    {
        if (null === $args) {
            $args = $_SERVER['argv'];
        }

        parent::__construct(Cli::getInputStream());
        $this->collectInfo($args);
    }

    /**
     * @return resource
     */
    public function getInputStream()
    {
        return $this->stream;
    }

    public function resetInputStream(): void
    {
        $this->stream = Cli::getInputStream();
    }

    /***********************************************************************************
     * getter/setter
     ***********************************************************************************/

    /**
     * @return string
     */
    public function getBinWithCommand(): string
    {
        return $this->scriptName . ' ' . $this->getCommandPath();
    }

    /**
     * @return string
     */
    public function getFullCommand(): string
    {
        return $this->scriptFile . ' ' . $this->getCommandPath();
    }

    /**
     * Get command ID e.g `http:start`
     *
     * @return  string
     */
    public function getCommandId(): string
    {
        return $this->commandId;
    }

    /**
     * Set command ID e.g `http:start`
     *
     * @param string $commandId e.g `http:start`
     *
     * @return void
     */
    public function setCommandId(string $commandId): void
    {
        $this->commandId = $commandId;
    }
}
