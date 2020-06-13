<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-22
 * Time: 11:55
 */

namespace Inhere\Console\BuiltIn;

use Closure;
use Exception;
use Inhere\Console\Component\PharCompiler;
use Inhere\Console\Controller;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\Helper;
use Inhere\Console\Util\Show;
use RuntimeException;
use Toolkit\Stdlib\Str;
use function basename;
use function file_exists;
use function is_dir;
use function is_file;
use function microtime;
use function realpath;

/**
 * Class PharController
 *
 * @package Inhere\Console\BuiltIn
 */
class PharController extends Controller
{
    protected static $name = 'phar';

    protected static $description = 'Pack a project directory to phar or unpack phar to directory';

    /**
     * @var Closure
     */
    private $compilerConfiger;

    /**
     * @var string
     */
    private $defPkgName;

    protected static function commandAliases(): array
    {
        return [
            'pack' => ['build']
        ];
    }

    /**
     * @param Input $input
     */
    protected function packConfigure(Input $input): void
    {
        $this->defPkgName = trim(basename($input->getPwd()), '.') . PharCompiler::FILE_EXT;

        $this->addCommentsVar('defaultPkgName', $this->defPkgName);
    }

    /**
     * pack project to a phar package
     * @usage {fullCommand} [--dir DIR] [--output FILE] [...]
     *
     * @options
     *  -d, --dir STRING        Setting the project directory for packing.
     *                          default is current work-dir(default: <cyan>{workDir}</cyan>)
     *  -c, --config STRING     Use the custom config file for build phar(default: <cyan>./phar.build.inc</cyan>)
     *  -o, --output STRING     Setting the output file name(<cyan>{defaultPkgName}</cyan>)
     *  --fast                  Fast build. only add modified files by <cyan>git status -s</cyan>
     *  --refresh               Whether build vendor folder files on phar file exists(<cyan>False</cyan>)
     *  --files  STRING         Only pack the list files to the exist phar, multi use ',' split
     *  --no-progress           Disable output progress on the runtime
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return int
     * @throws Exception
     * @example
     *  {fullCommand}                               Pack current dir to a phar file.
     *  {fullCommand} --dir vendor/swoft/devtool    Pack the specified dir to a phar file.
     *
     * custom output phar file name
     *   php -d phar.readonly=0 {binFile} phar:pack -o=mycli.phar
     *
     * only update the input files:
     *   php -d phar.readonly=0 {binFile} phar:pack -o=mycli.phar --debug --files app/Command/ServeCommand.php
     */
    public function packCommand($input, $output): int
    {
        $startAt = microtime(true);
        $workDir = $input->getPwd();

        $dir = $input->getOpt('dir') ?: $workDir;
        $cpr = $this->configCompiler($dir);

        $refresh  = $input->boolOpt('refresh');
        $outFile  = $input->getSameStringOpt(['o', 'output'], $this->defPkgName);
        $pharFile = $workDir . '/' . $outFile;

        Show::aList([
            'work dir'  => $workDir,
            'project'   => $dir,
            'phar file' => $pharFile,
        ], 'Building Information');

        // use fast build
        if ($this->input->boolOpt('fast')) {
            $cpr->setModifies($cpr->findChangedByGit());
            $this->output->liteNote('Use fast build, will only pack changed or new files(from git status)');
        }

        // Manual append some files
        if ($files = $input->getStringOpt('files')) {
            $cpr->setModifies(Str::explode($files));
            $output->liteInfo("will only pack input files to the exists phar: $outFile");
        }

        $cpr->onError(function ($error) {
            $this->writeln("<warning>$error</warning>");
        });
        $cpr->on(PharCompiler::ON_MESSAGE, function ($msg) {
            $this->output->colored('> ' . $msg);
        });

        $output->colored('Collect Pack files', 'comment');
        $this->outputProgress($cpr, $input);

        // packing ...
        $cpr->pack($pharFile, $refresh);

        $info = [
            PHP_EOL . '<success>Phar Build Completed!</success>',
            ' - Pack File: ' . $cpr->getCounter(),
            ' - Pack Time: ' . round(microtime(true) - $startAt, 3) . ' s',
            ' - Phar Size: ' . round(filesize($pharFile) / 1024 / 1024, 2) . ' Mb',
            " - Phar File: $pharFile",
            ' - Commit ID: ' . $cpr->getLastCommit(),
        ];
        $output->writeln($info);

        return 0;
    }

    /**
     * @param string $dir
     *
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

        if ($configFile && is_file($configFile)) {
            /** @noinspection PhpIncludeInspection */
            require $configFile;
            return $compiler->in($dir);
        }

        throw new RuntimeException("The phar build config file not exists! File: $configFile");
    }

    /**
     * @param PharCompiler $cpr
     * @param Input        $input
     *
     * @return void
     */
    private function outputProgress(PharCompiler $cpr,Input $input): void
    {
        if ($input->getBoolOpt('no-progress')) {
            return;
        }

        if ($input->getOpt('debug')) {
            // $output->info('Pack file to Phar ... ...');
            $cpr->onAdd(function (string $path) {
                $this->writeln(" <info>+</info> $path");
            });

            $cpr->on('skip', function (string $path, bool $isFile) {
                $mark = $isFile ? '[F]' : '[D]';
                $this->writeln(" <red>-</red> $path <info>$mark</info>");
            });
        } else {
            $counter = Show::counterTxt('Collecting ...', 'Done.');
            $cpr->onAdd(static function () use ($counter) {
                $counter->send(1);
            });
            $cpr->on(PharCompiler::ON_COLLECTED, function () use ($counter) {
                $counter->send(-1);
                $this->writeln('');
            });
        }
    }

    /**
     * @param Closure $compilerConfiger
     */
    public function setCompilerConfiger(Closure $compilerConfiger): void
    {
        $this->compilerConfiger = $compilerConfiger;
    }

    /**
     * unpack a phar package to a directory
     * @usage {fullCommand} -f FILE [-d DIR]
     *
     * @options
     *  -f, --file STRING   The packed phar file path
     *  -d, --dir STRING    The output dir on extract phar package.
     *  -y, --yes BOOL      Whether display goon tips message.
     *  --overwrite BOOL    Whether overwrite exists files on extract phar
     *
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @example {fullCommand} -f myapp.phar -d var/www/app
     */
    public function unpackCommand($in, $out): int
    {
        if (!$path = $in->getSameOpt(['f', 'file'])) {
            return $out->error("Please input the phar file path by option '-f|--file'");
        }

        $basePath = $in->getPwd();
        $file     = realpath($basePath . '/' . $path);

        if (!file_exists($file)) {
            return $out->error("The phar file not exists. File: $file");
        }

        $dir       = $in->getSameOpt(['d', 'dir']) ?: $basePath;
        $overwrite = $in->getBoolOpt('overwrite');

        if (!is_dir($dir)) {
            Helper::mkdir($dir);
        }

        $out->write("Now, begin extract phar file:\n $file \nto dir:\n $dir");

        PharCompiler::unpack($file, $dir, null, $overwrite);

        $out->success("OK, phar package have been extract to the dir: $dir");

        return 0;
    }
}
