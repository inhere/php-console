<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Contract;

use Inhere\Console\AbstractApplication;
use Inhere\Console\Application;

/**
 * Interface CommandHandlerInterface
 *
 * @package Inhere\Console\Contract
 */
interface CommandHandlerInterface
{
    public const OK  = 0;

    public const ERR = 2;

    // name -> {name}
    public const ANNOTATION_VAR = '{%s}'; // '{$%s}';

    // {$%s} name -> {name}
    public const HELP_VAR_LEFT  = '{';

    public const HELP_VAR_RIGHT = '}';

    /**
     * Run command
     *
     * @param array $args
     *
     * @return mixed return int is exit code. other is command exec result.
     */
    public function run(array $args): mixed;

    /**
     * @return Application
     */
    public function getApp(): Application;

    /**
     * The input group name.
     *
     * @return string
     */
    public function getGroupName(): string;

    /**
     * The real group or command name. Alias of the getName()
     *
     * @return string
     */
    public function getRealName(): string;

    /**
     * @return string
     */
    public function getRealDesc(): string;

    /**
     * The real group name.
     *
     * @return string
     */
    public function getRealGName(): string;

    /**
     * The real command name.
     *
     * @return string
     */
    public function getRealCName(): string;

    /**
     * The input command/subcommand name.
     *
     * @return string
     */
    public function getCommandName(): string;

    /**
     * @param bool $useReal
     *
     * @return string
     */
    public function getCommandId(bool $useReal = true): string;

    /**
     * @return string
     */
    public static function getName(): string;

    /**
     * @return string
     */
    public static function getDesc(): string;
}
