<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-26
 * Time: 9:33
 */

namespace Inhere\Console\Components\Symbol;

/**
 * Class Emoji
 * @package Inhere\Console\Components\Symbol
 */
final class Emoji
{
    const ID = '🆔';
    const KEY = '🔑';
    const BOX = '📦';
    const GIFT = '🎁';
    const CLOCK = '⏰';
    const FLAG = '🚩';
    const TOOL = '🔧';
    const GUN = '🔫';
    const DING = '📌';
    const STOP = '🚫';

    const DOC = '📄';
    const DIR = '📂';
    const BOOK = '📔';
    const RECYCLE = '♻';

    const EDIT = '✍';
    const SMILE = '😊';
    const LAUGH = '😆';
    const LIKE = '😍';
    const ANGER = '😡';
    const HAPPY = '😀';
    const DOZE = '😴';

    const OK = '👌';
    const YES = '✌';
    const NO = '✋';
    const PRAISE = '👍';
    const TREAD = '👎';
    const STEP = '🐾';

    const UP = '👆';
    const DOWN = '👇';
    const LEFT = '👈';
    const RIGHT = '👉';

    const FIRE = '🔥';
    const SNOW = '❄';
    const WATER = '💧';
    const FLASH = '⚡';

    const EYE = '👀';
    const HEART = '💖';
    const HEARTBREAK = '💔';

    const SUC = '✅';
    const FAIL = '❌';
    const WAN = '❗';
    const QUESTION = '❓';

    const CAR = '🚕';

    const TREE = '🌲';
    const FLOWER = '🌺';

    const PEAR = '🍐';
    const APPLE = '🍎';

    const ELEPHANT = '🐘';
    const WHALE = '🐳';

    const SUN = '🌞';
    const STAR = '⭐';
    const MOON = '🌜';
    const EARTH = '🌏';

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

            // 此处获取类中定义的全部常量 返回的是 [key=>value,...] 的数组
            // key是常量名 value是常量值
            self::$constants = $objClass->getConstants();
        }

        return self::$constants;
    }
}
