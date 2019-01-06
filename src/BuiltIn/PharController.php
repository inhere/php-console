<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-22
 * Time: 11:55
 */

namespace Inhere\Console\BuiltIn;

use Inhere\Console\Component\PharCompiler;
use Inhere\Console\Controller;
use Inhere\Console\Util\Helper;
use Inhere\Console\Util\Show;

/**
 * Class PharController
 * @package Inhere\Console\BuiltIn
 */
class PharController extends Controller
{
    protected static $name = 'phar';
    protected static $description = 'Pack a project directory to phar or unpack phar to directory';

    /**
     * @var \Closure
     */
    private $compilerConfiger;

    protected function init()
    {
        parent::init();

        $this->addAnnotationVar('defaultPkgName', \basename($this->input->getPwd()));
    }

    /**
     * pack project to a phar package
     * @usage {fullCommand} [--dir DIR] [--output FILE] [...]
     * @options
     *  -d, --dir STRING        Setting the project directory for packing.
     *                          default is current work-dir.(<cyan>{workDir}</cyan>)
     *  -c, --config STRING     Use the custom config file for build phar(<cyan>./phar.build.inc</cyan>)
     *  -o, --output STRING     Setting the output file name(<cyan>{defaultPkgName}.phar</cyan>)
     *  --fast BOOL             Fast build. only add modified files by <cyan>git status -s</cyan>
     *  --refresh BOOL          Whether build vendor folder files on phar file exists(<cyan>False</cyan>)
     * @param  \Inhere\Console\IO\Input  $in
     * @param  \Inhere\Console\IO\Output $out
     * @return int
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     * @throws \BadMethodCallException
     */
    public function packCommand($in, $out): int
    {
        $time = \microtime(1);
        $workDir = $in->getPwd();

        $dir = $in->getOpt('dir') ?: $workDir;
        $cpr = $this->configCompiler($dir);

        $counter = null;
        $refresh = $in->boolOpt('refresh');
        $pharFile = $workDir . '/' . $in->sameOpt(['o', 'output'], \basename($workDir) . '.phar');

        // use fast build
        if ($this->input->boolOpt('fast')) {
            $cpr->setModifies($cpr->findChangedByGit());
            $this->output->liteNote('Use fast build, will only pack changed or new files(from git status)');
        }

        $out->liteInfo(
            "Now, will begin building phar package.\n from path: <comment>$workDir</comment>\n" .
            " phar file: <info>$pharFile</info>"
        );

        $out->info('Pack file to Phar: ');
        $cpr->onError(function ($error) {
            $this->output->warning($error);
        });

        if ($in->getOpt('debug')) {
            $cpr->onAdd(function ($path) {
                $this->output->write(" <comment>+</comment> $path");
            });
        } else {
            $counter = Show::counterTxt('Handling ...', 'Done.');
            $cpr->onAdd(function () use ($counter) {
                $counter->send(1);
            });
        }

        // packing ...
        $cpr->pack($pharFile, $refresh);

        // end
        if ($counter) {
            $counter->send(-1);
        }

        $out->write([
            PHP_EOL . '<success>Phar build completed!</success>',
            " - Phar file: $pharFile",
            ' - Phar size: ' . round(filesize($pharFile) / 1024 / 1024, 2) . ' Mb',
            ' - Pack Time: ' . round(microtime(1) - $time, 3) . ' s',
            ' - Pack File: ' . $cpr->getCounter(),
            ' - Commit ID: ' . $cpr->getVersion(),
        ]);

        return 0;
    }

    /**
     * @param string $dir
     * @return PharCompiler
     */
    protected function configCompiler(string $dir): PharCompiler
    {
        // create compiler
        $compiler = new PharCompiler($dir);

        // use function config
        if ($configer = $this->compilerConfiger) {
            $configer($compiler);

            return $compiler->in($dir);
        }

        // use config file
        $configFile = $this->input->getSameOpt(['c', 'config']) ?: $dir . '/phar.build.inc';

        if ($configFile && \is_file($configFile)) {
            require $configFile;

            return $compiler->in($dir);
        }

        throw new \RuntimeException("The phar build config file not exists! File: $configFile");
    }

    /**
     * @param \Closure $compilerConfiger
     */
    public function setCompilerConfiger(\Closure $compilerConfiger)
    {
        $this->compilerConfiger = $compilerConfiger;
    }

    /**
     * unpack a phar package to a directory
     * @usage {fullCommand} -f FILE [-d DIR]
     * @options
     *  -f, --file STRING   The packed phar file path
     *  -d, --dir STRING    The output dir on extract phar package.
     *  -y, --yes BOOL      Whether display goon tips message.
     *  --overwrite BOOL    Whether overwrite exists files on extract phar
     * @example {fullCommand} -f myapp.phar -d var/www/app
     * @param  \Inhere\Console\IO\Input  $in
     * @param  \Inhere\Console\IO\Output $out
     * @return int
     */
    public function unpackCommand($in, $out): int
    {
        if (!$path = $in->getSameOpt(['f', 'file'])) {
            return $out->error("Please input the phar file path by option '-f|--file'");
        }

        $basePath = $in->getPwd();
        $file = \realpath($basePath . '/' . $path);

        if (!\file_exists($file)) {
            return $out->error("The phar file not exists. File: $file");
        }

        $dir = $in->getSameOpt(['d', 'dir']) ?: $basePath;
        $overwrite = $in->getBoolOpt('overwrite');

        if (!\is_dir($dir)) {
            Helper::mkdir($dir);
        }

        $out->write("Now, begin extract phar file:\n $file \nto dir:\n $dir");

        PharCompiler::unpack($file, $dir, null, $overwrite);

        $out->success("OK, phar package have been extract to the dir: $dir");

        return 0;
    }
}
