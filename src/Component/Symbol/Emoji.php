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
 * Class Emoji
 *
 * @package Inhere\Console\Component\Symbol
 */
final class Emoji
{
    public const ID    = '🆔';

    public const KEY   = '🔑';

    public const BOX   = '📦';

    public const GIFT  = '🎁';

    public const CLOCK = '⏰';

    public const FLAG  = '🚩';

    public const TOOL  = '🔧';

    public const GUN   = '🔫';

    public const DING  = '📌';

    public const STOP  = '🚫';

    public const DOC     = '📄';

    public const DIR     = '📂';

    public const BOOK    = '📔';

    public const RECYCLE = '♻';

    public const EDIT  = '✍';

    public const SMILE = '😊';

    public const LAUGH = '😆';

    public const LIKE  = '😍';

    public const ANGER = '😡';

    public const HAPPY = '😀';

    public const DOZE  = '😴';

    public const OK     = '👌';

    public const YES    = '✌';

    public const NO     = '✋';

    public const PRAISE = '👍';

    public const TREAD  = '👎';

    public const STEP   = '🐾';

    public const UP    = '👆';

    public const DOWN  = '👇';

    public const LEFT  = '👈';

    public const RIGHT = '👉';

    public const FIRE  = '🔥';

    public const SNOW  = '❄';

    public const WATER = '💧';

    public const FLASH = '⚡';

    public const EYE        = '👀';

    public const HEART      = '💖';

    public const HEARTBREAK = '💔';

    public const SUC      = '✅';

    public const FAIL     = '❌';

    public const WAN      = '❗';

    public const QUESTION = '❓';

    public const CAR = '🚕';

    public const TREE   = '🌲';

    public const FLOWER = '🌺';

    public const PEAR  = '🍐';

    public const APPLE = '🍎';

    public const ELEPHANT = '🐘';

    public const WHALE    = '🐳';

    public const SUN   = '🌞';

    public const STAR  = '⭐';

    public const MOON  = '🌜';

    public const EARTH = '🌏';

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

            // 此处获取类中定义的全部常量 返回的是 [key=>value,...] 的数组
            // key是常量名 value是常量值
            self::$constants = $objClass->getConstants();
        }

        return self::$constants;
    }
}
