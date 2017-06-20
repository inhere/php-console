<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/4/23 0023
 * Time: 10:22
 */

namespace inhere\console\io;

/**
 * Class Input
 * @package inhere\console\io
 */
interface InputInterface
{
    /**
     * These words will be as a Boolean value
     */
    const TRUE_WORDS = '|on|yes|true|';
    const FALSE_WORDS = '|off|no|false|';

    /**
     * 读取输入信息
     * @param  string $question 若不为空，则先输出文本消息
     * @param  bool $nl true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public function read($question = null, $nl = false): string;

    public function getScript(): string;

    public function getCommand(): string;

    /**
     * get Argument
     * @param null|int|string $name
     * @param mixed $default
     * @return mixed
     */
    public function getArg($name, $default = null);

    /**
     * @param string $name
     * @param null $default
     * @return bool|mixed|null
     */
    public function getOpt(string $name, $default = null);
}
