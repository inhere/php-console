<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-02
 * Time: 11:38
 */

namespace Inhere\Console\Component\Formatter;

use Inhere\Console\Component\MessageFormatter;
use Inhere\Console\Console;
use Inhere\Console\Util\FormatUtil;
use Toolkit\Cli\Cli;
use function array_merge;
use function is_array;
use function is_scalar;
use function str_pad;

/**
 * Class Tree
 *
 * @package Inhere\Console\Component\Formatter
 */
class Tree extends MessageFormatter
{
    /** @var int */
    private $counter = 0;

    /** @var bool */
    private $started = false;

    /**
     * Render data like tree
     * ├ ─ ─
     * └ ─
     *
     * @param array $data
     * @param array $opts
     */
    public static function show(array $data, array $opts = []): void
    {
        static $counter = 0;
        static $started = 1;

        if ($started) {
            $started = 0;
            $opts    = array_merge([
                // 'char' => Cli::isSupportColor() ? '─' : '-', // ——
                'char'        => '-',
                'prefix'      => Cli::isSupportColor() ? '├' : '|',
                'leftPadding' => '',
            ], $opts);

            $opts['_level']   = 1;
            $opts['_is_main'] = true;

            Console::startBuffer();
        }

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $counter++;
                $leftString = $opts['leftPadding'] . str_pad($opts['prefix'], $opts['_level'] + 1, $opts['char']);

                Console::write($leftString . ' ' . FormatUtil::typeToString($value));
            } elseif (is_array($value)) {
                $newOpts             = $opts;
                $newOpts['_is_main'] = false;
                $newOpts['_level']++;

                self::show($value, $newOpts);
            }
        }

        if ($opts['_is_main']) {
            Console::write('node count: ' . $counter);
            // var_dump('f');
            Console::flushBuffer();

            // reset.
            $counter = $started = 0;
        }
    }
}
