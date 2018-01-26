<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-26
 * Time: 9:33
 */

namespace Inhere\Console\Utils;

/**
 * Class FontSymbol
 * - 字体符号
 * @package Inhere\Console\Utils
 */
class FontSymbol
{
    const OK = '✔';
    const NO = '✘';
    const PEN = '✎';
    const HEART = '❤';

    const UP = '';
    const DOWN = '';
    const LEFT = '';
    const RIGHT = '';

    const MALE = '♂';
    const FEMALE = '♀';

    /**
     * @return array
     */
    public static function getConstants(): array
    {
        $objClass = new \ReflectionClass(__CLASS__);

        return $objClass->getConstants();
    }
}