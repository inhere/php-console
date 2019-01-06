<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/27 0027
 * Time: 21:54
 */

namespace Inhere\Console\BuiltIn;

use Inhere\Console\Command;
use Toolkit\Sys\Sys;

/**
 * Class DevServerCommand
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
     * @usage
     *  {command} [-S HOST:PORT]
     *  {command} [-H HOST] [-p PORT]
     *  {command} [-S HOST:PORT] [file=]web/index.php
     * @options
     *  -S STRING               The http server address. e.g 127.0.0.1:8552
     *  -t STRING               The document root dir for server(<comment>web</comment>)
     *  -H,--host STRING        The server host address(<comment>127.0.0.1</comment>)
     *  -p,--port INTEGER       The server port number(<comment>8552</comment>)
     *  -b,--php-bin STRING     The php binary file(<comment>php</comment>)
     * @arguments
     *  file=STRING         The entry file for server. e.g web/index.php
     * @example
     *  {command} -S 127.0.0.1:8552 web/index.php
     * @param  \Inhere\Console\IO\Input  $in
     * @param  \Inhere\Console\IO\Output $out
     * @return int|mixed|void
     */
    public function execute($in, $out)
    {
        if (!$server = $this->getOpt('S')) {
            $server = $this->getSameOpt(['H', 'host'], '127.0.0.1');
        }

        if (!\strpos($server, ':')) {
            $port = $this->getSameOpt(['p', 'port'], 8552);
            $server .= ':' . $port;
        }

        $version = \PHP_VERSION;
        $workDir = $this->input->getPwd();
        $docDir = $this->getOpt('t');
        $docRoot = $docDir ? $workDir . '/' . $docDir : $workDir;

        $this->write([
            "PHP $version Development Server started\nServer listening on http://<info>$server</info>",
            "Document root is <comment>$docRoot</comment>",
            'You can use <comment>CTRL + C</comment> to stop run.',
        ]);

        // $command = "php -S {$server} -t web web/index.php";
        $command = "php -S {$server}";

        if ($docDir) {
            $command .= " -t $docDir";
        }

        if ($entryFile = $this->getSameArg(['file', 0])) {
            $command .= " $entryFile";
        }

        $this->write("<cyan>></cyan> <darkGray>$command</darkGray>");

        Sys::execute($command);
    }
}
