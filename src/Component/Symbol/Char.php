<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-26
 * Time: 9:33
 */

namespace Inhere\Console\Component\Symbol;

/**
 * Class FontSymbol
 * - 字体符号
 * @package Inhere\Console\Component\Symbol
 */
final class Char
{
    public const OK  = '✔';
    public const NO  = '✘';
    public const PEN = '✎';

    public const HEART = '❤';
    public const SMILE = '☺';

    public const FLOWER = '✿';
    public const MUSIC  = '♬';

    public const UP     = '';
    public const DOWN   = '';
    public const LEFT   = '';
    public const RIGHT  = '';
    public const SEARCH = '';

    public const MALE   = '♂';
    public const FEMALE = '♀';

    public const SUN   = '☀';
    public const STAR  = '★';
    public const SNOW  = '❈';
    public const CLOUD = '☁';

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
