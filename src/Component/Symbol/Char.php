<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-26
 * Time: 9:33
 */

namespace Inhere\Console\Component\Symbol;

use ReflectionClass;

/**
 * Class FontSymbol
 * - 字体符号
 *
 * @package Inhere\Console\Component\Symbol
 */
final class Char
{
    public const OK  = '✔';
    public const OK1 = '✓';

    public const NO = '✘︎';

    public const NO1 = '︎✕';

    public const NO2 = '︎✖︎';

    public const NO3 = '︎✗';

    public const PEN = '✎';

    // ☑︎☐☒

    public const FLAG = '⚑';
    public const FLAG1 = '⚐';

    public const HEART = '❤';

    // ☺︎☹︎☻
    public const SMILE  = '☺';
    public const SMILE1 = '☹︎';
    public const SMILE2 = '☻︎';

    public const FLOWER = '✿';

    public const MUSIC = '♬';

    public const UP = '';

    public const DOWN = '';

    public const LEFT = '';

    public const RIGHT = '';

    public const SEARCH = '';

    public const MALE = '♂';

    public const FEMALE = '♀';

    public const SUN = '☀';

    // ✪☆✯★✩
    public const STAR = '★';

    public const STAR1 = '✪';

    public const STAR2 = '✩';

    public const SNOW = '❈';

    public const CLOUD = '☁';

    public const POINT = '●';

    public const POINT1 = '•';

    public const POINT2 = '○';

    public const POINT3 = '◉';

    public const POINT4 = '◎';

    public const POINT5 = '⦿';

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
     */
    public static function getConstants(): array
    {
        if (!self::$constants) {
            $objClass = new ReflectionClass(__CLASS__);

            self::$constants = $objClass->getConstants();
        }

        return self::$constants;
    }
}
