<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/24 0024
 * Time: 16:48
 */

namespace Inhere\Console\BuiltIn;

use Inhere\Console\Controller;

/**
 * Class GenController
 * @package Inhere\Console\BuiltIn
 */
class GenController extends Controller
{
    protected static $name = 'gen';
    protected static $description = 'generate code template file tool.';

    /**
     * generate a alone console command class
     */
    public function aloneCommand()
    {

    }

    /**
     * generate a group commands(console controller) class
     */
    public function groupCommand()
    {

    }
}
