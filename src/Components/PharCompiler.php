<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-11
 * Time: 17:57
 */

namespace Inhere\Console\Components;

use Phar;

/**
 * Class PharCompiler
 * @package Inhere\Console\Components
 * @ref Psy\Compiler (package psy/psysh)
 */
class PharCompiler
{
    private $version;

    /**
     * @var array
     */
    private $options = [
        // for create phar Stub. It is relative the srcDir path.
        'cliIndex' => null,
        'webIndex' => null,

        // compress php code
        'compress' => false,

        'dirExclude' => '#[\.git|tests]#',

        'fileInclude' => [],
        'fileMatch' => '#\.php#',
    ];

    /** @var array */
    private $srcDirs;

    /** @var array */
    private $appendFiles;

    /** @var string */
    private $dstDir;

    /**
     * PharCompiler constructor.
     * @param null|string|array $srcDirs
     * @param null $dstDir
     * @param array $options
     */
    public function __construct($srcDirs = null, $dstDir = null, array $options = [])
    {
        $this->srcDirs = $srcDirs ? (array)$srcDirs : [];
        $this->dstDir = $dstDir;

        $this->setOptions($options);
    }

    /**
     * Compiles some dirs into a single phar file.
     * @param string $pharFile The full path to the file to create
     * @param string $version
     * @return int
     * @throws \LogicException
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     */
    public function pack($pharFile = 'your.phar', $version = '0.0.1')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $pharName = basename($pharFile);
        $this->version = $version;

        if (!$this->srcDirs) {
            throw new \LogicException('Please setting the source directory for pack');
        }

        $phar = new Phar($pharFile, 0, $pharName);
//        $phar = new Phar($pharFile, \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::KEY_AS_FILENAME, $pharName);
        $phar->setMetadata(['author' => 'inhere']);
        $phar->setSignatureAlgorithm(Phar::SHA1);

        // begin
        $phar->startBuffering();

        foreach ($this->srcDirs as $srcDir) {
            $this->collectFiles($phar, $srcDir);
        }

        // Stubs
//        $phar->setStub($this->getStub());
//        $stub = Phar::createDefaultStub($this->options['cliIndex'], $this->options['webIndex']);
//        $phar->setStub($stub);

        // 设置入口
        $phar->setStub("<?php
Phar::mapPhar('{$pharName}');
require 'phar://{$pharName}/examples/app';
__HALT_COMPILER();
?>");

        $phar->stopBuffering();
        $count = $phar->count();
        unset($phar);

        return $count;
    }

    /**
     * @param string $file
     * @param string|null $distDir
     * @throws \LogicException
     */
    public function unpack(string $file, string $distDir = null)
    {
        if (!$distDir = $distDir ?: $this->dstDir) {
            throw new \LogicException('Please setting the dist directory for unpack');
        }
    }

    protected function collectFiles(Phar $phar, $srcDir)
    {
        $iterator = Helper::recursiveDirectoryIterator($srcDir, function ($file) {
            /** @var \SplFileInfo $file */
            $name = $file->getFilename();

            // Skip hidden files and directories.
            if ($name[0] === '.') {
                return false;
            }

            if ($file->isDir()) {
                // Only recurse into intended subdirectories.
                return preg_match($this->options['dirExclude'], $name);
            }

            if (\in_array($name, $this->options['fileInclude'], true)) {
                return true;
            }

            // Only consume files of interest.
            return preg_match($this->options['fileMatch'], $name);
        });

        $phar->buildFromIterator($iterator, $srcDir);
//        $phar->buildFromDirectory($srcDir, '/[\.php|app]$/');

//        foreach ($iterator as $file) {
//            $this->addFileToPhar($phar, $file, $srcDir);
//        }
    }

    /**
     * Add a file to the Phar.
     * @param Phar $phar
     * @param \SplFileInfo $file
     * @param string $basePath
     */
    private function addFileToPhar($phar, \SplFileInfo $file, $basePath)
    {
        $isPhp = $file->getExtension() === 'php';
        // $path = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getRealPath());
        $path = substr($file->getRealPath(), \strlen($basePath) + 1);

        if ($isPhp && $this->options['compress']) {
            $content = php_strip_whitespace($file);
        } elseif ('LICENSE' === basename($file)) {
            $content = file_get_contents($file);
            $content = "\n" . $content . "\n";
        } else {
            $content = file_get_contents($file);
        }

        $phar->addFromString($path, $content);
    }

    const STUB_AUTOLOAD = <<<EOS
    Phar::mapPhar('psysh.phar');
    require 'phar://psysh.phar/build-vendor/autoload.php';
EOS;

    /**
     * Get a Phar stub
     * This is basically the psysh bin, with the autoload require statements swapped out.
     * @return string
     */
    protected function getStub()
    {
        $content = file_get_contents(__DIR__ . '/../../bin/psysh');
        $content = preg_replace('{/\* <<<.*?>>> \*/}sm', self::STUB_AUTOLOAD, $content);
        $content = preg_replace('/\\(c\\) .*?with this source code./sm', self::getStubLicense(), $content);

        $content .= '__HALT_COMPILER();';

        return $content;
    }

    private static function getStubLicense()
    {
        $license = file_get_contents(__DIR__ . '/../../LICENSE');
        $license = str_replace('The MIT License (MIT)', '', $license);
        $license = str_replace("\n", "\n * ", trim($license));

        return $license;
    }

    /**
     * @param string $file
     * @return $this
     */
    public function setCliIndex(string $file)
    {
        $this->options['cliIndex'] = $file;

        return $this;
    }

    /**
     * @param string $file
     * @return $this
     */
    public function setWebIndex(string $file)
    {
        $this->options['webIndex'] = $file;

        return $this;
    }

    /**
     * @param string|array $name
     * @return $this
     */
    public function addFileInclude($name)
    {
        $this->options['fileInclude'] = array_merge($this->options['fileInclude'], (array)$name);

        return $this;
    }

    /**
     * @param string $regex
     * @return $this
     */
    public function setDirExclude(string $regex)
    {
        $this->options['dirExclude'] = $regex;

        return $this;
    }

    /**
     * @param string $regex
     * @return $this
     */
    public function setFileMatch(string $regex)
    {
        $this->options['fileMatch'] = $regex;

        return $this;
    }

    /**
     * @param string $srcDir
     * @return $this
     */
    public function addSrcDir(string $srcDir)
    {
        $this->srcDirs[] = $srcDir;

        return $this;
    }

    /**
     * @return array
     */
    public function getAppendFiles(): array
    {
        return $this->appendFiles;
    }

    /**
     * @param array $appendFiles
     * @return PharCompiler
     */
    public function setAppendFiles(array $appendFiles): PharCompiler
    {
        $this->appendFiles = $appendFiles;

        return $this;
    }

    /**
     * @return array
     */
    public function getSrcDirs(): array
    {
        return $this->srcDirs;
    }

    /**
     * @param string|array $srcDirs
     */
    public function setSrcDirs($srcDirs)
    {
        $this->srcDirs = (array)$srcDirs;
    }

    /**
     * @return string
     */
    public function getDstDir(): ?string
    {
        return $this->dstDir;
    }

    /**
     * @param string $dstDir
     */
    public function setDstDir(string $dstDir)
    {
        $this->dstDir = $dstDir;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }
}
