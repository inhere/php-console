<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-26
 * Time: 9:33
 */

namespace Inhere\Console\Components\Symbol;

/**
 * Class FontSymbol
 * - 字体符号
 * @package Inhere\Console\Components\Symbol
 */
final class Char
{
    const OK = '✔';
    const NO = '✘';
    const PEN = '✎';

    const HEART = '❤';
    const SMILE = '☺';

    const FLOWER = '✿';
    const MUSIC = '♬';

    const UP = '';
    const DOWN = '';
    const LEFT = '';
    const RIGHT = '';
    const SEARCH = '';

    const MALE = '♂';
    const FEMALE = '♀';

    const SUN = '☀';
    const STAR = '★';
    const SNOW = '❈';
    const CLOUD = '☁';

    /**
     * @var array
     * [
     *  key => value,
     *  ...
     * ]
     */
    private static $constants;

    /**
     * @return array
     * @throws \ReflectionException
     */
    public static function getConstants(): array
    {
        if (!self::$constants) {
            $objClass = new \ReflectionClass(__CLASS__);

            self::$constants = $objClass->getConstants();
        }

        return self::$constants;
    }
}
