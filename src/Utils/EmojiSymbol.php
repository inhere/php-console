<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-26
 * Time: 9:33
 */

namespace Inhere\Console\Utils;

/**
 * Class EmojiSymbol
 * @package Inhere\Console\Utils
 */
class EmojiSymbol
{
    const KEY = '🔑';
    const BOX = '📦';
    const GIFT = '🎁';
    const CLOCK = '⏰';
    const FLAG = '🚩';

    const DOC = '📄';
    const DIR = '📂';
    const BOOK = '📔';

    const TOOL = '🔧';
    const GUN = '🔫';

    const UP = '👍';
    const DOWN = '👎';

    const FIRE = '🔥';
    const SNOW = '❄';
    const WATER = '💧';
    const FLASH = '⚡';

    const HEART = '💖';

    const SUC = '✅';
    const FAIL = '❌';
    const WAN = '❗';

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

    /**
     * @return array
     */
    public static function getConstants(): array
    {
        $objClass = new \ReflectionClass(__CLASS__);

        // 此处获取类中定义的全部常量 返回的是 [key=>value,...] 的数组
        // key是常量名 value是常量值
        return $objClass->getConstants();
    }
}