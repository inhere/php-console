<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/4/23 0023
 * Time: 10:22
 */

namespace Inhere\Console\Contract;

/**
 * Class Input
 *
 * @package Inhere\Console\Contract
 */
interface InputInterface
{
    // fixed args and opts for a command/controller-command
    public const ARG_REQUIRED = 1;

    public const ARG_OPTIONAL = 2;

    public const ARG_IS_ARRAY = 4;

    public const OPT_BOOLEAN  = 1; // eq symfony InputOption::VALUE_NONE

    public const OPT_REQUIRED = 2;

    public const OPT_OPTIONAL = 4;

    public const OPT_IS_ARRAY = 8;

    /**
     * 读取输入信息
     *
     * @param string $question 若不为空，则先输出文本消息
     * @param bool   $nl       true 会添加换行符 false 原样输出，不添加换行符
     *
     * @return string
     */
    public function read(string $question = '', bool $nl = false): string;

    /**
     * @return string
     */
    public function getScript(): string;

    /**
     * @return string
     */
    public function getCommand(): string;

    /**
     * @return array
     */
    public function getArgs(): array;

    /**
     * get Argument
     *
     * @param null|int|string $name
     * @param mixed           $default
     *
     * @return mixed
     */
    public function getArg($name, $default = null);

    /**
     * @return array
     */
    public function getOpts(): array;

    /**
     * @param string $name
     * @param null   $default
     *
     * @return bool|mixed|null
     */
    public function getOpt(string $name, $default = null);
}
