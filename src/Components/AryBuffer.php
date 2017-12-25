<?php

/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-24
 * Time: 9:17
 */

namespace Inhere\Console\Components;

/**
 * Class AryBuffer
 * @package Inhere\Console\Components
 */
final class AryBuffer
{
    /**
     * @var string[]
     */
    private $body = [];
    /** @var string */
    private $delimiter = '';
    // '/' ':'

    /**
     * constructor.
     * @param string $content
     */
    public function __construct($content = '')
    {
        if ($content) {
            $this->body[] = $content;
        }
    }

    /**
     * @param string $content
     */
    public function write($content)
    {
        $this->body[] = $content;
    }

    /**
     * @param string $content
     */
    public function append($content)
    {
        $this->write($content);
    }

    /**
     * @param string $content
     */
    public function prepend($content)
    {
        array_unshift($this->body, $content);
    }

    /**
     * clear
     */
    public function clear()
    {
        $this->body = [];
    }

    /**
     * @return string[]
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string[] $body
     */
    public function setBody(array $body)
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return implode($this->delimiter, $this->body);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }
}