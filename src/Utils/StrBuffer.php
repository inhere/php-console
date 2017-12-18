<?php

/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-24
 * Time: 9:17
 */

namespace Inhere\Console\Utils;

/**
 * Class StrBuffer
 * @package Inhere\Console\Utils
 */
class StrBuffer
{
    /**
     * @var string
     */
    private $body;

    public function __construct($content = '')
    {
        $this->body = $content;
    }

    /**
     * @param string $content
     */
    public function write($content)
    {
        $this->body .= $content;
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
        $this->body = $content . $this->body;
    }

    /**
     * clear
     */
    public function clear()
    {
        $this->body = '';
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->body;
    }
}