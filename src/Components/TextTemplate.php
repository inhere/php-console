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
    /** @var array  */
    private $vars = [];

    /** @var string  */
    private $openChar = '{$';

    /** @var string  */
    private $closeChar = '}';

    /**
     * @param string $template
     * @param array $vars
     * @return string
     */
    public function render(string $template, array $vars = [])
    {
        if (!$template || false === strpos($template, $this->openChar)) {
            return $template;
        }

        if ($this->vars) {
            $vars = array_merge($this->vars, $vars);
        }

        $pairs = [];

        foreach ($vars as $name => $value) {
            $key = $this->openChar . $name . $this->closeChar;
            $pairs[$key] = $value;
        }

        return strtr($template, $pairs);
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
}
