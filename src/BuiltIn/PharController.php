<?php

/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-27
 * Time: 9:08
 */

namespace Inhere\Console\BuiltIn;

use Inhere\Console\Components\PharBuilder;
use Inhere\Console\Components\PharCompiler;
use Inhere\Console\Controller;

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
     * @usage
     *  {command} phar=[FILE] src-dir=[DIR] [-c --format ...]
     * @arguments
     *  phar       The output phar file path.<red>*</red>
     *  src-dirs   The source directory for pack, multi use ',' split.<red>*</red>
     * @options
     *  -c, --compress      Compress the phar file to 'gz','bz','zip'.
     *      --format        Format php source file content(will remove all annotations).
     *      --file-include  Append file include
     * @example
     *  {command} phar=my.phar src-dir=./ -c --format
     */
    public function packCommand()
    {
        $pcr = new PharCompiler(BASE_PATH);
        $pcr->setOptions(['cliIndex' => 'examples/app', 'webIndex' => null, 'compress' => $this->getSameOpt(['c', 'compress'], false), 'dirExclude' => '#[\\.git|tests]#', 'fileInclude' => ['LICENSE', 'app', 'liteApp'], 'fileMatch' => '#\\.php$#']);
        $pharFile = BASE_PATH . '/test.phar';
        $count = $pcr->pack($pharFile);
        $this->output->json(['count' => $count, 'size' => round(filesize($pharFile) / 1000, 2) . ' kb']);
    }

    /**
     * pack directory(s) to a phar file.
     */
    public function buildCommand()
    {
        $packer = new PharBuilder(BASE_PATH);
        $packer->addDirectory(BASE_PATH);
        $packer->setOptions([
            'cliIndex' => 'examples/app',
            'webIndex' => null,
            // 'compress' => $this->getSameOpt(['c', 'compress'], false),
            'dirExclude' => '#[\\.git|tests]#',
            'fileInclude' => ['LICENSE', 'app', 'liteApp'],
            'fileMatch' => '#\\.php$#',
        ]);
        $packer->build($pharFile = BASE_PATH . '/example.phar');
        $this->output->json(['size' => round(filesize($pharFile) / 1000, 2) . ' kb']);
    }
}