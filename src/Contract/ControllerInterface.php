<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
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
    public function getGroupName(): string;

    /**
     * @return string
     */
    public function getDefaultAction(): string;

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter): void;
}
