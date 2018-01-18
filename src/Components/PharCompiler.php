<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/18 0018
 * Time: 21:32
 */

namespace Inhere\Console\Components;

use Inhere\Console\Utils\Helper;

/**
 * Class PharCompiler
 * @package Inhere\Console\Components
 */
final class PharCompiler
{
    /** @var array */
    private static $supportedSignatureTypes = [
        \Phar::SHA512 => 1,
        \Phar::SHA256 => 1,
        \Phar::SHA1 => 1
    ];

    /** @var resource */
    private $key;

    /** @var  */
    private $signatureType;

    /**
     * @var int compress Mode @see \Phar::NONE, \Phar::GZ, \Phar::BZ2
     */
    private $compressMode = 0;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string Phar file path. e.g '/some/path/app.phar'
     */
    private $pharFile;

    /**
     * @var string Phar file name. eg 'app.phar'
     */
    private $pharName;

    /**
     * @var string|null
     */
    private $cliIndex;

    /**
     * @var string|null
     */
    private $webIndex;

    /**
     * @var array
     */
    private $files = [];

    /**
     * @var array want to exclude directory name list
     */
    private $excludes = [];

    /**
     * @var array
     */
    private $directories = [];

    public function __construct()
    {

    }

    /**
     * @param string $pharFile
     * @param string $extractTo
     * @param string|array|null $files Only fetch the listed files
     * @param bool $overwrite
     * @return bool
     */
    public static function unpack(string $pharFile, string $extractTo, $files = null, $overwrite = false): bool
    {
        $phar = new \Phar($pharFile);

        return $phar->extractTo($extractTo, $files, $overwrite);
    }

    /**
     * @param string $pharFile
     * @param bool $refresh
     * @return string
     */
    public function pack(string $pharFile, $refresh = true): string
    {
        if (ini_get('phar.readonly')) {
            throw new \RuntimeException("The 'phar.readonly' is 'On', build phar must setting it 'Off'");
        }

        if (!$this->directories) {
            throw new \RuntimeException("Please setting the 'directories' want building directories");
        }

        if ($refresh && file_exists($pharFile)) {
            unlink($pharFile);
        }

        $this->pharName = $pharName = basename($pharFile);

        $phar = new \Phar($pharFile, 0, $pharName);
        $phar->startBuffering();

        if ($this->key !== null) {
            $privateKey = '';
            openssl_pkey_export($this->key, $privateKey);
            $phar->setSignatureAlgorithm(\Phar::OPENSSL, $privateKey);
            $keyDetails = openssl_pkey_get_details($this->key);
            file_put_contents($pharFile . '.pubkey', $keyDetails['key']);
        } else {
            $phar->setSignatureAlgorithm($this->selectSignatureType());
        }

        $filter = $this->createIteratorFilter();
        $basePath = $this->basePath;

        foreach ($this->directories as $directory) {
            $iterator = Helper::recursiveDirectoryIterator($directory, $filter);

            foreach ($iterator as $file) {
                $this->addFileToPhar($phar, $file);
            }
        }

        foreach ($this->files as $filename) {
            if ('/' === $filename[0] || is_file($filename = $basePath . '/' . $filename)) {
                $this->addFileToPhar($phar, new \SplFileInfo($filename));
            }
        }


        $phar->setStub($this->createStub($pharName));

        if ($this->compressMode) {
            $phar->compressFiles($this->compressMode);
        }

        $phar->stopBuffering();
        unset($phar);

        return $pharFile;
    }

    /**
     * Add a file to the Phar.
     * @param \Phar $phar
     * @param \SplFileInfo $file
     * @param bool $strip
     */
    private function addFileToPhar(\Phar $phar, \SplFileInfo $file, $strip = true)
    {
        $isPhp = $file->getExtension() === 'php';
        $basePath = $this->basePath;

        // $path = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getRealPath());
        $path = substr($file->getRealPath(), \strlen($basePath) + 1);

        if ($isPhp && $strip) {
            $content = php_strip_whitespace($file);
        } elseif ('LICENSE' === basename($file)) {
            $content = file_get_contents($file);
            $content = "\n" . $content . "\n";
        } else {
            $content = file_get_contents($file);
        }

        $phar->addFromString($path, $content);
    }

    private function packIndexFile()
    {

    }

    /**
     * @param string $pharName
     * @return string
     */
    private function createStub(string $pharName): string
    {
        // Stubs
        // return \Phar::createDefaultStub($this->cliIndex, $this->webIndex);

        // 设置入口
        return <<<EOF
<?php

Phar::mapPhar('{$pharName}');

require 'phar://{$pharName}/{$this->cliIndex}';

__HALT_COMPILER(); 
EOF;

    }

    /**
     * @return \Closure
     */
    private function createIteratorFilter(): \Closure
    {
        return function (\SplFileInfo $file) {
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
        };
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = realpath($basePath);
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param array $files
     * @return PharCompiler
     */
    public function setFiles(array $files): self
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @param array $files
     * @return PharCompiler
     */
    public function addFiles(array $files): self
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @return array
     */
    public function getExcludes(): array
    {
        return $this->excludes;
    }

    /**
     * @param array $excludes
     * @return $this
     */
    public function setExcludes(array $excludes): self
    {
        $this->excludes = $excludes;

        return $this;
    }

    /**
     * @param string $dir
     * @return $this
     */
    public function addExclude(string $dir): self
    {
        $this->excludes[] = $dir;

        return $this;
    }

    /**
     * @param array $excludes
     * @return $this
     */
    public function addExcludes(array $excludes): self
    {
        $this->excludes = $excludes;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCliIndex()
    {
        return $this->cliIndex;
    }

    /**
     * @param string $cliIndex
     * @return $this
     */
    public function setCliIndex(string $cliIndex): self
    {
        $this->cliIndex = $cliIndex;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getWebIndex()
    {
        return $this->webIndex;
    }

    /**
     * @param null|string $webIndex
     * @return PharCompiler
     */
    public function setWebIndex(string $webIndex): self
    {
        $this->webIndex = $webIndex;
        return $this;
    }

    /**
     * @return int
     */
    private function selectSignatureType(): int
    {
        if (isset(self::$supportedSignatureTypes[$this->signatureType])) {
            return $this->signatureType;
        }

        return \Phar::SHA1;
    }

    /**
     * @return string
     */
    public function getPharName(): string
    {
        return $this->pharName;
    }

    /**
     * @return string
     */
    public function getPharFile(): string
    {
        return $this->pharFile;
    }

}
