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
    /** @var array */
    private $vars = [];
    /** @var string */
    private $openChar = '{$';
    /** @var string */
    private $closeChar = '}';

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

    /**
     * @param string $tplFile
     * @param array $vars
     * @param null|string $saveAs
     * @return string|bool
     */
    public function renderFile($tplFile, array $vars = [], $saveAs = null)
    {
        if (!\is_file($tplFile)) {
            throw new \InvalidArgumentException("Template file not exists. FILE: {$tplFile}");
        }

        return $this->render(file_get_contents($tplFile), $vars, $saveAs);
    }

    /**
     * @param string $template
     * @param array $vars
     * @param null|string $saveAs
     * @return string
     */
    public function render($template, array $vars = [], $saveAs = null)
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
        // replace vars to values.
        $rendered = strtr($template, $pairs);
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
    public function getVar($name, $default = null)
    {
        return isset($this->vars[$name]) ? $this->vars[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function addVar($name, $value)
    {
        if (!isset($this->vars[$name])) {
            $this->vars[$name] = $value;
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setVar($name, $value)
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
    public function getVars()
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
    public function getOpenChar()
    {
        return $this->openChar;
    }

    /**
     * @param string $openChar
     */
    public function setOpenChar($openChar)
    {
        if ($openChar) {
            $this->openChar = $openChar;
        }
    }

    /**
     * @return string
     */
    public function getCloseChar()
    {
        return $this->closeChar;
    }

    /**
     * @param string $closeChar
     */
    public function setCloseChar($closeChar)
    {
        if ($closeChar) {
            $this->closeChar = $closeChar;
        }
    }
}