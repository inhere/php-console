<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component\Formatter;

use Inhere\Console\Component\MessageFormatter;
use Inhere\Console\Console;
use function implode;

/**
 * Class MultiList
 *
 * @package Inhere\Console\Component\Formatter
 */
class MultiList extends MessageFormatter
{
    /**
     * Format and render multi list
     *
     * ```php
     * $data = [
     *   'list1 title' => [
     *      'name' => 'value text',
     *      'name2' => 'value text 2',
     *   ],
     *   'list2 title' => [
     *      'name' => 'value text',
     *      'name2' => 'value text 2',
     *   ],
     *   ... ...
     * ];
     *
     * MultiList::show($data);
     * ```
     *
     * @param array $data
     * @param array $opts
     *
     * @psalm-param array{beforeWrite: callable, lastNewline: bool} $opts
     */
    public static function show(array $data, array $opts = []): void
    {
        $stringList  = [];
        $ignoreEmpty = $opts['ignoreEmpty'] ?? true;
        $lastNewline = true;

        $opts['returned'] = true;
        if (isset($opts['lastNewline'])) {
            $lastNewline = $opts['lastNewline'];
            unset($opts['lastNewline']);
        }

        $beforeWrite = null;
        if (isset($opts['beforeWrite'])) {
            $beforeWrite = $opts['beforeWrite'];
            unset($opts['beforeWrite']);
        }

        foreach ($data as $title => $list) {
            if ($ignoreEmpty && !$list) {
                continue;
            }

            $stringList[] = SingleList::show($list, (string)$title, $opts);
        }

        $str = implode("\n", $stringList);

        // before write handler
        if ($beforeWrite) {
            $str = $beforeWrite($str);
        }

        Console::write($str, $lastNewline);
    }
}
