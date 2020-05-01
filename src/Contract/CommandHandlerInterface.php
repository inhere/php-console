<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-18
 * Time: 18:57
 */

namespace Inhere\Console\Contract;

use Inhere\Console\AbstractApplication;
use Inhere\Console\IO\InputDefinition;

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
     * @param string $command
     *
     * @return int|mixed return int is exit code. other is command exec result.
     */
    public function run(string $command = '');

    /**
     * @return InputDefinition|null
     */
    public function getDefinition(): ?InputDefinition;

    /**
     * @return AbstractApplication|ApplicationInterface
     */
    public function getApp(): AbstractApplication;

    /**
     * @return string
     */
    public static function getName(): string;

    /**
     * @return string
     */
    public static function getDescription(): string;
}
