<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\BuiltIn;

use Exception;
use Inhere\Console\Annotate\Attr\CmdOption;
use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\PhpDevServe;

/**
 * Class DevServerCommand
 *
 * @package Inhere\Console\BuiltIn
 */
class DevServerCommand extends Command
{
    protected static string $name = 'dev:server';

    protected static string $desc = 'Start a php built-in http server for development';

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
     *  -s, -S, --addr      The http server address. e.g 127.0.0.1:8552
     *  -t, --doc-root      The document root dir for server(<comment>public</comment>)
     *  -H,--host           The server host address(<comment>127.0.0.1</comment>)
     *  -p,--port           The server port number(<comment>8552</comment>)
     *  -b,--php-bin        The php binary file(<comment>php</comment>)
     *
     * @arguments
     *  file         The entry file for server. e.g web/index.php
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return void
     * @throws Exception
     * @example
     *  {command} -S 127.0.0.1:8552 web/index.php
     */

    #[CmdOption('dev-serve', 'start a php built-in http server for developmentd')]
    public function execute(Input $input, Output $output): void
    {
        $serveAddr = $this->flags->getOpt('addr');
        if (!$serveAddr) {
            $serveAddr = $this->flags->getOpt('host');
        }

        $port = $this->flags->getOpt('port');
        if ($port && !str_contains($serveAddr, ':')) {
            $serveAddr .= ':' . $port;
        }

        $docRoot = $this->flags->getOpt('doc-root');
        $hceFile = $this->flags->getOpt('hce-file');
        $hceEnv  = $this->flags->getOpt('hce-env');
        $phpBin  = $this->flags->getOpt('php-bin');

        $entryFile = $this->flags->getArg('file');

        $pds = PhpDevServe::new($serveAddr, $docRoot, $entryFile);
        $pds->setPhpBin($phpBin);

        if ($hceEnv && $hceFile) {
            $pds->loadHceFile($hceFile);
            $pds->useHceEnv($hceEnv);
        }

        $pds->listen();
    }
}
