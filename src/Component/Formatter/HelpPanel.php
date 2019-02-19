<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/22 0022
 * Time: 21:45
 */

namespace Inhere\Console\Component\Formatter;

use Inhere\Console\Util\FormatUtil;
use Inhere\Console\Util\Show;

/**
 * Class HelpPanel
 * - method version please {@see \Inhere\Console\Util\Show::helpPanel()}
 * @package Inhere\Console\Component\Formatter
 */
class HelpPanel extends Formatter
{
    /**
     * help panel keys
     */
    public const DESC      = 'description';
    public const USAGE     = 'usage';
    public const COMMANDS  = 'commands';
    public const ARGUMENTS = 'arguments';
    public const OPTIONS   = 'options';
    public const EXAMPLES  = 'examples';
    public const EXTRAS    = 'extras';

    /**
     * Show console help message
     * @param  array $config The config data
     * There are config structure. you can setting some or ignore some. will only render it when value is not empty.
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
     */
    public static function show(array $config): void
    {
        $parts  = [];
        $option = [
            'indentDes' => '  ',
        ];
        $config = \array_merge([
            'description' => '',
            'usage'       => '',

            'commands'  => [],
            'arguments' => [],
            'options'   => [],

            'examples' => [],

            // extra
            'extras'   => [],

            '_opts' => [],
        ], $config);

        // some option for show.
        if (isset($config['_opts'])) {
            $option = \array_merge($option, $config['_opts']);
            unset($config['_opts']);
        }

        // description
        if ($config['description']) {
            $parts[] = "{$option['indentDes']}{$config['description']}\n";
            unset($config['description']);
        }

        // now, render usage,commands,arguments,options,examples ...
        foreach ($config as $section => $value) {
            if (!$value) {
                continue;
            }

            // if $value is array, translate array to string
            if (\is_array($value)) {
                // is natural key ['text1', 'text2'](like usage,examples)
                if (isset($value[0])) {
                    $value = \implode(\PHP_EOL . '  ', $value);

                    // is key-value [ 'key1' => 'text1', 'key2' => 'text2']
                } else {
                    $value = FormatUtil::spliceKeyValue($value, [
                        'leftChar' => '  ',
                        'sepChar'  => '  ',
                        'keyStyle' => 'info',
                    ]);
                }
            }

            if (\is_string($value)) {
                $value   = \trim($value);
                $section = \ucfirst($section);
                $parts[] = "<comment>$section</comment>:\n  {$value}\n";
            }
        }

        if ($parts) {
            Show::write(\implode("\n", $parts), false);
        }
    }
}
