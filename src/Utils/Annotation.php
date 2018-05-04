<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-06-30
 * Time: 17:29
 */

namespace Inhere\Console\Utils;

/**
 * Class Annotation
 * @package Inhere\Console\Utils
 */
final class Annotation
{
    /**
     * 以下三个方法来自 yii2 console/Controller.php 做了一些调整
     */

    /**
     * Parses the comment block into tags.
     * @param string $comment The comment block text
     * @param array $ignored
     * @return array The parsed tags
     */
    public static function getTags(string $comment, array $ignored = ['param', 'return']): array
    {
        $comment = str_replace("\r\n", "\n", trim($comment, "/ \n"));
        $comment = "@description \n" . str_replace("\r", '',
                trim(preg_replace('/^\s*\**( |\t)?/m', '', $comment))
            );

        $tags = [];
        $parts = preg_split('/^\s*@/m', $comment, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($parts as $part) {
            if (preg_match('/^(\w+)(.*)/ms', trim($part), $matches)) {
                $name = $matches[1];
                if (\in_array($name, $ignored, true)) {
                    continue;
                }

                if (!isset($tags[$name])) {
                    $tags[$name] = trim($matches[2]);
                } elseif (\is_array($tags[$name])) {
                    $tags[$name][] = trim($matches[2]);
                } else {
                    $tags[$name] = [$tags[$name], trim($matches[2])];
                }
            }
        }

        return $tags;
    }

    /**
     * Returns the first line of docBlock.
     *
     * @param string $comment
     * @return string
     */
    public static function firstLine(string $comment): string
    {
        $docLines = preg_split('~\R~u', $comment);

        if (isset($docLines[1])) {
            return trim($docLines[1], "/\t *");
        }

        return '';
    }

    /**
     * Returns full description from the doc-block.
     * If have multi line text, will return multi line.
     *
     * @param string $comment
     * @return string
     */
    public static function description(string $comment): string
    {
        $comment = str_replace("\r", '', trim(preg_replace('/^\s*\**( |\t)?/m', '', trim($comment, '/'))));

        if (preg_match('/^\s*@\w+/m', $comment, $matches, PREG_OFFSET_CAPTURE)) {
            $comment = trim(substr($comment, 0, $matches[0][1]));
        }

        return $comment;
    }

    /**
     * @param string $optsStr
     */
    public static function formatOptions(string $optsStr)
    {

    }

    /**
     * this is a command's description message
     * the second line text
     * @format
     * @usage usage message
     * @arguments(format=true)
     *  arg1  argument description 1
     *        the second line
     *  a2,arg2  argument description 2
     *        the second line
     * @arguments(
     *  arg1="argument description 1
     *        the second line",
     *  "a2,arg2"="argument description 2
     *        the second line"
     * )
     * @options
     *  -s, --long LONG option description 1
     *  --opt    OPT   option description 2
     * @example example text one
     *  the second line example
     * @param string $argsStr
     */
    public static function formatArguments(string $argsStr)
    {

    }
}
