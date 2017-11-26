<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/11/25 0025
 * Time: 11:39
 */

namespace Inhere\Console\Components;

use Inhere\Console\Utils\Helper;

/**
 * Class PharBuilder
 * @package Inhere\Console\Components
 */
class PharBuilder
{
    private $scanner;

    /** @var int @see \Phar::GZ, \Phar::BZ2 */
    private $compressMode;

    /** @var resource */
    private $key;

    /** @var string */
    private $basedir;

    /** @var string */
    private $aliasName;

    /** @var callable */
    private $iteratorFilter;

    private $signatureType;

    private $directories = [];

    private $supportedSignatureTypes = [
        'SHA-512' => \Phar::SHA512,
        'SHA-256' => \Phar::SHA256,
        'SHA-1' => \Phar::SHA1
    ];

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

    public function __construct(DirectoryScanner $scanner, $basedir)
    {
        $this->scanner = $scanner;
        $this->basedir = $basedir;
    }

    /**
     * @param int $mode
     */
    public function setCompressMode($mode)
    {
        $this->compressMode = (int)$mode;
    }

    /**
     * @param $type
     * @throws \InvalidArgumentException
     */
    public function setSignatureType($type)
    {
        if (!array_key_exists($type, $this->supportedSignatureTypes)) {
            throw new \InvalidArgumentException(
                sprintf('Signature type "%s" not known or not supported by this PHP installation.', $type)
            );
        }

        $this->signatureType = $type;
    }

    public function setSignatureKey($key)
    {
        $this->key = $key;
    }

    public function addDirectory($directory)
    {
        $this->directories[] = $directory;
    }

    public function setAliasName($name)
    {
        $this->aliasName = $name;
    }

    /**
     * @param $filename
     * @param $stub
     * @throws \LogicException
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     */
    public function build($filename, $stub = null)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }

        $aliasName = $this->aliasName ?: basename($filename);
        $phar = new \Phar($filename, 0, $aliasName);
        $phar->startBuffering();
        $phar->setStub($stub ?: $this->createStub($aliasName));

        if ($this->key !== null) {
            $privateKey = '';
            openssl_pkey_export($this->key, $privateKey);
            $phar->setSignatureAlgorithm(\Phar::OPENSSL, $privateKey);
            $keyDetails = openssl_pkey_get_details($this->key);
            file_put_contents($filename . '.pubkey', $keyDetails['key']);
        } else {
            $phar->setSignatureAlgorithm($this->selectSignatureType());
        }

        $filter = $this->getIteratorFilter();
        $basedir = $this->basedir ?: $this->directories[0];

        foreach ($this->directories as $directory) {
            $iterator = Helper::recursiveDirectoryIterator($directory, $filter);
            $phar->buildFromIterator($iterator, $basedir);
        }

        if ($this->compressMode !== \Phar::NONE) {
            $phar->compressFiles($this->compressMode);
        }

        $phar->stopBuffering();
    }

    private function createStub($pharName)
    {
        // Stubs
//        $phar->setStub($this->getStub());
        $stub = \Phar::createDefaultStub($this->options['cliIndex'], $this->options['webIndex']);

        // 设置入口
        return"<?php
Phar::mapPhar('{$pharName}');
require 'phar://{$pharName}/examples/app';
__HALT_COMPILER();
?>";
    }

    /**
     * @return int|mixed
     */
    private function selectSignatureType()
    {
        if ($this->signatureType) {
            return $this->supportedSignatureTypes[$this->signatureType];
        }

        $supported = \Phar::getSupportedSignatures();

        foreach ($this->supportedSignatureTypes as $candidate => $type) {
            if (\in_array($candidate, $supported, true)) {
                return $type;
            }
        }

        // Is there any PHP Version out there that does not support at least SHA-1?
        // But hey, fallback to md5, better than nothing
        return \Phar::MD5;
    }

    /**
     * @return callable
     */
    public function getIteratorFilter(): callable
    {
        return $this->iteratorFilter ?: function (\SplFileInfo $file) {
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
     * @param callable $iteratorFilter
     * @return $this
     */
    public function setIteratorFilter(callable $iteratorFilter)
    {
        $this->iteratorFilter = $iteratorFilter;

        return $this;
    }

    /**
     * @param resource $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }
}
