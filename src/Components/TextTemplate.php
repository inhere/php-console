<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/22 0022
 * Time: 21:30
 */

namespace Inhere\Console\Components;

/**
 * Class TextTemplate
 * @package Inhere\Console\Components
 */
class TextTemplate
{
    /**
     * comments: {* comments ... *}
     */
    const MATCH_COMMENT = '#\{\*.*?\*\}#sm';

    /**
     * {#include 'text-0.tpl'}
     */
    const MATCH_INCLUDE = '#\{\#include (.*)\}#';

    /** @var array */
    private $vars = [];

    /** @var string */
    private $openChar = '{$';

    /** @var string */
    private $closeChar = '}';

    /** @var string */
    private $basePath;

    /** @var bool clear first and last space. */
    private $clearFLSpace = true;

    /**
     * TextTemplate constructor.
     * @param array $vars
     */
    public function __construct(array $vars = [])
    {
        if ($vars) {
            $this->setVars($vars);
        }
    }

    public function reset()
    {
        $this->vars = [];
    }

    /**
     * @param string $tplFile
     * @param array $vars
     * @param null|string $saveAs
     * @return string|bool
     */
    public function renderFile(string $tplFile, array $vars = [], $saveAs = null)
    {
        if (!\is_file($tplFile)) {
            throw new \InvalidArgumentException("Template file not exists. FILE: $tplFile");
        }

        if (!$this->basePath) {
            $this->basePath = \dirname($tplFile);
        }

        return $this->render(file_get_contents($tplFile), $vars, $saveAs);
    }

    /**
     * @param string $template
     * @param array $vars
     * @param null|string $saveAs
     * @return string
     */
    public function render(string $template, array $vars = [], $saveAs = null)
    {
        if (!$template || false === strpos($template, $this->openChar)) {
            return $template;
        }

        if ($this->vars) {
            $vars = array_merge($this->vars, $vars);
        }

        $pairs = $map = [];
        $this->expandVars($vars, $map);

        foreach ($map as $name => $value) {
            $key = $this->openChar . $name . $this->closeChar;
            $pairs[$key] = $value;
        }

        // has include tag
        if ($this->basePath && false !== strpos($template, '{#include')) {
            $template = (string)preg_replace_callback(self::MATCH_INCLUDE, function ($m) use ($pairs) {
                return $this->renderInclude($m, $pairs);
            }, $template);
        }

        // remove comments
        $template = $this->removeComments($template);

        // replace vars to values.
        $rendered = strtr($template, $pairs);

        if ($this->clearFLSpace) {
            $rendered = trim($rendered);
        }

        if (!$saveAs) {
            return $rendered;
        }

        $dstDir = \dirname($saveAs);

        if (!is_dir($dstDir) && !mkdir($dstDir, 0775, true) && !is_dir($dstDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dstDir));
        }

        return (bool)file_put_contents($saveAs, $rendered);
    }

    /**
     * @param array $matches
     * @param array $data
     * @return string
     */
    protected function renderInclude(array $matches, array $data)
    {
        if (!isset($matches[1]) || !($include = trim($matches[1], ' \'"'))) {
            return '!!CANNOT INCLUDE EMPTY FILE!!';
        }

        $include = $this->basePath . '/' . $include;

        if (!\is_file($include)) {
            return '!!THE INCLUDE FILE NOT FOUND!!';
        }

        $rendered = strtr(file_get_contents($include), $data);

        if ($this->clearFLSpace) {
            $rendered = trim($rendered);
        }

        return $rendered;
    }

    /**
     * @param string $input
     * @return string
     */
    protected function removeComments(string $input)
    {
        return preg_replace(
            self::MATCH_COMMENT,
            '',
            $input = str_replace("\r\n", "\n", $input)
        );
    }

    /**
     * Multidimensional array expansion to one dimension array
     * @param array $vars
     * @param null|string $prefix
     * @param array $map
     */
    protected function expandVars(array $vars, array &$map = [], $prefix = null)
    {
        foreach ($vars as $name => $value) {
            $key = $prefix !== null ? $prefix . '.' . $name : $name;

            if (is_scalar($value)) {
                $map[$key] = $value;
            } elseif (\is_array($value)) {
                $this->expandVars($value, $map, (string)$key);
            }
        }
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public function getVar(string $name, $default = null)
    {
        return $this->vars[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function addVar(string $name, $value)
    {
        if (!isset($this->vars[$name])) {
            $this->vars[$name] = $value;
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setVar(string $name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * @param array $vars
     */
    public function addVars(array $vars)
    {
        $this->vars = array_merge($this->vars, $vars);
    }

    /**
     * @return array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * @param array $vars
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }

    /**
     * @return string
     */
    public function getOpenChar(): string
    {
        return $this->openChar;
    }

    /**
     * @param string $openChar
     */
    public function setOpenChar(string $openChar)
    {
        if ($openChar) {
            $this->openChar = $openChar;
        }
    }

    /**
     * @return string
     */
    public function getCloseChar(): string
    {
        return $this->closeChar;
    }

    /**
     * @param string $closeChar
     */
    public function setCloseChar(string $closeChar)
    {
        if ($closeChar) {
            $this->closeChar = $closeChar;
        }
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param bool $clearFLSpace
     * @return TextTemplate
     */
    public function setClearFLSpace(bool $clearFLSpace): TextTemplate
    {
        $this->clearFLSpace = $clearFLSpace;

        return $this;
    }
}
