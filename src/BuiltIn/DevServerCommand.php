<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/27 0027
 * Time: 21:54
 */

namespace Inhere\Console\BuiltIn;

use Exception;
use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\PhpDevServe;
use function strpos;

/**
 * Class DevServerCommand
 *
 * @package Inhere\Console\BuiltIn
 */
class DevServerCommand extends Command
{
    protected static $name = 'dev:server';

    protected static $description = 'Start a php built-in http server for development';

    public static function aliases(): array
    {
        return ['devServer', 'dev-server', 'dev-serve', 'dev:serve'];
    }

    /**
     * start a php built-in http server for development
     *
     * @usage
     *  {command} [-S HOST:PORT]
     *  {command} [-H HOST] [-p PORT]
     *  {command} [-S HOST:PORT] [file=]web/index.php
     * @options
     *  -s, -S, --addr STRING   The http server address. e.g 127.0.0.1:8552
     *  -t, --doc-root STRING   The document root dir for server(<comment>public</comment>)
     *  -H,--host STRING        The server host address(<comment>127.0.0.1</comment>)
     *  -p,--port INTEGER       The server port number(<comment>8552</comment>)
     *  -b,--php-bin STRING     The php binary file(<comment>php</comment>)
     * @arguments
     *  file=STRING         The entry file for server. e.g web/index.php
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return int|mixed|void
     * @throws Exception
     * @example
     *  {command} -S 127.0.0.1:8552 web/index.php
     */
    public function execute($input, $output)
    {
        $serveAddr = $input->getSameStringOpt('s,S,addr');
        if (!$serveAddr) {
            $serveAddr = $input->getSameStringOpt(['H', 'host']);
        }

        $port = $input->getSameStringOpt(['p', 'port']);
        if ($port && strpos($serveAddr, ':') === false) {
            $serveAddr .= ':' . $port;
        }

        $docRoot = $input->getSameStringOpt('t,doc-root');
        $hceFile = $input->getStringOpt('hce-file');
        $hceEnv  = $input->getStringOpt('hce-env');
        $phpBin  = $input->getStringOpt('php-bin');

        $input->bindArgument('file', 0);
        $entryFile = $input->getStringArg('file');

        $pds = PhpDevServe::new($serveAddr, $docRoot, $entryFile);
        $pds->setPhpBin($phpBin);

        if ($hceEnv && $hceFile) {
            $pds->loadHceFile($hceFile);
            $pds->useHceEnv($hceEnv);
        }

        $pds->listen();
    }
}
