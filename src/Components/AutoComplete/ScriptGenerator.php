<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/22 0022
 * Time: 23:13
 */

namespace Inhere\Console\Components\AutoComplete;

use Inhere\Console\Components\TextTemplate;

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

    /** @var TextTemplate */
    private $renderer;

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
     * @param string $tplFile
     * @param string $dstFile
     * @param array $vars
     * @param bool $override
     * @return bool
     */
    public function gen(string $tplFile, string $dstFile, array $vars = [], $override = false)
    {
        if (!\is_file($tplFile)) {
            throw new \InvalidArgumentException("Template file not exists. FILE: $tplFile");
        }

        if (!$override && is_file($dstFile)) {
            return true;
        }

        $tt = $this->getRenderer();

        return $tt->render(file_get_contents($tplFile), $vars, $dstFile);
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

    /**
     * @return TextTemplate
     */
    public function getRenderer(): TextTemplate
    {
        if (!$this->renderer) {
            $this->renderer = new TextTemplate();
        }

        return $this->renderer;
    }

    /**
     * @param TextTemplate $renderer
     */
    public function setRenderer(TextTemplate $renderer)
    {
        $this->renderer = $renderer;
    }
}
