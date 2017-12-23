<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/22 0022
 * Time: 23:13
 */

namespace Inhere\Console\Components\AutoComplete;

/**
 * Class ScriptGenerator
 * - ref linux system '/etc/bash_completion.d/'
 * - generate bash/zsh auto complete script for current console application.
 * @package Inhere\Console\Components\AutoComplete
 * @link http://www.linuxidc.com/Linux/2016-10/136201.htm
 */
class ScriptGenerator
{
    const TYPE_BASH = 1;
    const TYPE_ZSH = 1;

    // simple: only commands. full: contains command description.
    const MODE_SIMPLE = 1;
    const MODE_FULL = 2;

    /** @var int The type */
    private $type = 1;

    /** @var int The mode */
    private $mode = 1;

    /**
     * @return array
     */
    public static function typeList()
    {
        return [self::TYPE_BASH, self::TYPE_ZSH];
    }

    /**
     * @return array
     */
    public static function modeList()
    {
        return [self::MODE_SIMPLE, self::MODE_FULL];
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type)
    {
        if (\in_array($type, self::typeList(), 1)) {
            $this->type = $type;
        }
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode(int $mode)
    {
        if (\in_array($mode, self::modeList(), 1)) {
            $this->mode = $mode;
        }
    }
}
