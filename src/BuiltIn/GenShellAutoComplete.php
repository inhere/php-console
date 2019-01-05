<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/7/4
 * Time: 上午10:25
 */

namespace Inhere\Console\BuiltIn;

use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

/**
 * Class GenShellAutoComplete
 * @package Inhere\Console\BuiltIn
 */
class GenShellAutoComplete extends Command
{
    protected static $name = 'gen:ac';
    protected static $description = 'Start a php built-in http server for development';

    public static function aliases(): array
    {
        return ['genac', 'gen-ac'];
    }

    /**
     * do execute
     * @param  Input  $input
     * @param  Output $output
     * @return int|mixed
     */
    protected function execute($input, $output)
    {
        // TODO: Implement execute() method.
    }
}
