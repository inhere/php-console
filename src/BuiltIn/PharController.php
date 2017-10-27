<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-27
 * Time: 9:08
 */

namespace Inhere\Console\BuiltIn;

use Inhere\Console\Controller;
use Inhere\Console\Utils\PharCompiler;

/**
 * Class PharController
 * @package Inhere\Console\BuiltIn
 */
class PharController extends Controller
{
    protected static $name = 'phar';
    protected static $description = 'provide package code to phar/unpack phar tool.';

    /**
     * pack directory(s) to a phar file.
     * @arguments
     *  src-dir   The source directory for pack<red>*</red>
     *  phar      The output phar file path
     *
     * @options
     *  -c,--compress   Want compress php file.
     *  --file-include  Append file include
     *
     */
    public function packCommand()
    {
        $pcr = new PharCompiler(BASE_PATH);
        $pcr->setOptions([
            'cliIndex' => 'examples/app',
            'webIndex' => null,

            'compress' => $this->getSameOpt(['c', 'compress'], false),

            'dirExclude' => '#[\.git|tests]#',

            'fileInclude' => ['LICENSE', 'app', 'liteApp'],
            'fileMatch' => '#\.php#',
        ]);

        $pharFile = BASE_PATH . '/test.phar';
        $count = $pcr->pack($pharFile);

        $this->output->json([
            'count' => $count,
            'size' => round(filesize($pharFile) / 1000, 2) . ' kb',
        ]);
    }
}