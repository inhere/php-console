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
use Inhere\Console\Util\FormatUtil;
use function array_merge;
use function implode;
use function is_array;
use function is_string;
use function trim;
use function ucfirst;
use const PHP_EOL;

/**
 * Class HelpPanel
 * - method version please {@see \Inhere\Console\Util\Show::helpPanel()}
 *
 * @package Inhere\Console\Component\Formatter
 */
class HelpPanel extends MessageFormatter
{
    /**
     * help panel keys
     */
    public const DESC      = 'desc';

    public const USAGE     = 'usage';

    public const COMMANDS  = 'commands';

    public const ARGUMENTS = 'arguments';

    public const OPTIONS   = 'options';

    public const EXAMPLES  = 'examples';

    public const EXTRAS    = 'extras';

    /**
     * Show console help message
     *
     * There are config structure. you can setting some or ignore some. will only render it when value is not empty.
     *
     * ```
     * [
     *  description string         The description text. e.g 'Composer version 1.3.2'
     *  usage       string         The usage message text. e.g 'command [options] [arguments]'
     *  commands    array|string   The command list. e.g:
     *      [
     *          // command => description
     *          'start'    => 'Start the app server',
     *          ... ...
     *      ]
     *  arguments   array|string   The argument list. e.g:
     *      [
     *          // argument => description
     *          'name'      => 'Your name',
     *          'city'      => 'Your city name'
     *          ... ...
     *      ]
     *  options     array|string   The option list. e.g:
     *      [
     *          // option    => description
     *          '-d'         => 'Run the server on daemon.(default: <comment>false</comment>)',
     *          '-h, --help' => 'Display this help message'
     *          ... ...
     *      ]
     *  examples    array|string  The command usage example. e.g 'php server.php {start|reload|restart|stop} [-d]'
     * ]
     * ```
     *
     * @param array $config The config data
     */
    public static function show(array $config): void
    {
        $parts  = [];
        $option = [
            'indentDes' => '  ',
        ];
        $config = array_merge([
            'desc'      => '',
            'usage'     => '',
            'commands'  => [],
            'arguments' => [],
            'options'   => [],
            'examples'  => [],
            // extra
            'extras'    => [],
            '_opts'     => [],
        ], $config);

        // some option for show.
        if (isset($config['_opts'])) {
            $option = array_merge($option, $config['_opts']);
            unset($config['_opts']);
        }

        // description
        if ($config['desc']) {
            $parts[] = "{$option['indentDes']}{$config['desc']}\n";
            unset($config['desc']);
        }

        // now, render usage,commands,arguments,options,examples ...
        foreach ($config as $section => $value) {
            if (!$value) {
                continue;
            }

            // if $value is array, translate array to string
            if (is_array($value)) {
                // is natural key ['text1', 'text2'](like usage,examples)
                if (isset($value[0])) {
                    $value = implode(PHP_EOL . '  ', $value);

                // is key-value [ 'key1' => 'text1', 'key2' => 'text2']
                } else {
                    $value = FormatUtil::spliceKeyValue($value, [
                        'leftChar' => '  ',
                        'sepChar'  => '  ',
                        'keyStyle' => 'info',
                    ]);
                }
            }

            if (is_string($value)) {
                $value   = trim($value);
                $section = ucfirst($section);
                $parts[] = "<comment>$section</comment>:\n  $value\n";
            }
        }

        if ($parts) {
            Console::write(implode("\n", $parts), false);
        }
    }
}
