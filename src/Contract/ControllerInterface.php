<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-15
 * Time: 9:23
 */

namespace Inhere\Console\Contract;

/**
 * interface ControllerInterface
 *
 * @package Inhere\Console\Contract
 */
interface ControllerInterface
{
    // eg sampleCommand()
    public const COMMAND_SUFFIX = 'Command';

    // eg sampleConfigure()
    public const CONFIGURE_SUFFIX = 'Configure';

    /**
     * @return int
     */
    public function helpCommand(): int;

    /**
     * show command list of the controller class
     */
    public function showCommandList();

    /**
     * @return string
     */
    public function getAction(): string;

    /**
     * @return string
     */
    public function getDefaultAction(): string;

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter): void;
}
