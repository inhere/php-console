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
 * Class Input
 *
 * @package Inhere\Console\Contract
 */
interface InputInterface
{
    /**
     * 读取输入信息
     *
     * @param string $question 若不为空，则先输出文本消息
     * @param bool   $nl       true 会添加换行符 false 原样输出，不添加换行符
     *
     * @return string
     */
    public function readln(string $question = '', bool $nl = false): string;

    /**
     * @return string
     */
    public function getScriptFile(): string;

    /**
     * @return string
     */
    public function getCommand(): string;

    /**
     * Whether the stream is an interactive terminal
     */
    public function isInteractive(): bool;

    /**
     * @return string
     */
    public function toString(): string;
}
