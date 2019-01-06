<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/18 0018
 * Time: 21:32
 */

namespace Inhere\Console\Component;

use Inhere\Console\Util\Helper;
use Seld\PharUtils\Timestamps;
use Toolkit\Sys\Sys;

/**
 * Class PharCompiler
 * @package Inhere\Console\Component
 */
class PharCompiler
{
    /** @var array */
    private static $supportedSignatureTypes = [
        \Phar::SHA512 => 1,
        \Phar::SHA256 => 1,
        \Phar::SHA1   => 1
    ];

    /** @var resource */
    private $key;

    /** @var */
    private $signatureType;

    /**
     * @var int compress Mode @see \Phar::NONE, \Phar::GZ, \Phar::BZ2
     */
    private $compressMode = 0;

    /**
     * @var string|null The latest commit id
     */
    private $version;

    /**
     * @var string|null The latest tag name
     */
    private $branchAliasVersion = 'UNKNOWN';

    /**
     * @var \DateTime
     */
    private $versionDate;

    /**
     * 记录上面三个信息的文件, 相对于basePath
     * 当里面存在下面的占位符时会自动替换为获取到的信息
     * [
     * 'version' => '{@package_version}',
     * 'tag' => '{@package_branch_alias_version}',
     * 'releaseDate' => '{@release_date}',
     * ]
     * @var string
     */
    private $versionFile;

    /**
     * @var string The want to packaged project path
     */
    private $basePath;

    /**
     * @var string|null
     */
    private $cliIndex;

    /**
     * @var string|null
     */
    private $webIndex;

    /**
     * @var string|bool Set the shebang. eg '#!/usr/bin/env php'
     */
    private $shebang;

    /**
     * @var array Want to added files. (It is relative the $basePath)
     */
    private $files = [];

    /**
     * @var array Want to include files suffix name list
     */
    private $suffixes = ['.php'];

    /**
     * @var array Want to exclude file name list
     */
    private $notNames = [];

    /**
     * @var array Want to exclude directory name list
     */
    private $excludes = [];

    /**
     * @var array The directory paths, will collect files in there.
     */
    private $directories = [];

    /**
     * @var array|\Iterator The modifies files list. if not empty, will skip find dirs.
     */
    private $modifies;

    /**
     * @var \Closure[] Some events. if you want to get some info on packing.
     */
    private $events = [
        'add'   => 0,
        'error' => 0,
    ];

    /**
     * @var \Closure Maybe you not want strip all files.
     */
    private $stripFilter;

    /**
     * @var bool Whether strip comments
     */
    private $stripComments = true;

    /**
     * @var bool Whether auto collect version info by git log.
     */
    private $collectVersionInfo = true;

    // -------------------- internal props --------------------

    /** @var int */
    private $counter = 0;

    /**
     * @var string Phar file path. e.g '/some/path/app.phar'
     */
    private $pharFile;

    /**
     * @var string Phar file name. eg 'app.phar'
     */
    private $pharName;

    /**
     * @var \Closure File filter
     */
    private $fileFilter;

    /**
     * @param string            $pharFile
     * @param string            $extractTo
     * @param string|array|null $files Only fetch the listed files
     * @param bool              $overwrite
     * @return bool
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     */
    public static function unpack(string $pharFile, string $extractTo, $files = null, $overwrite = false): bool
    {
        self::checkEnv();

        $phar = new \Phar($pharFile);

        return $phar->extractTo($extractTo, $files, $overwrite);
    }

    /**
     *
     * @throws \RuntimeException
     */
    private static function checkEnv()
    {
        if (!\class_exists(\Phar::class, false)) {
            throw new \RuntimeException("The 'phar' extension is required for build phar package");
        }

        if (\ini_get('phar.readonly')) {
            throw new \RuntimeException(
                "The 'phar.readonly' is 'On', build phar must setting it 'Off' or exec with 'php -d phar.readonly=0'"
            );
        }
    }

    /**
     * PharCompiler constructor.
     * @param string $basePath
     * @throws \RuntimeException
     */
    public function __construct(string $basePath)
    {
        self::checkEnv();

        $this->basePath = \realpath($basePath);

        if (!\is_dir($this->basePath)) {
            throw new \RuntimeException("The inputted project path is not exists. DIR: {$this->basePath}");
        }
    }

    /**
     * @param string|array $suffixes
     * @return $this
     */
    public function addSuffix($suffixes): self
    {
        $this->suffixes = \array_merge($this->suffixes, (array)$suffixes);

        return $this;
    }

    /**
     * @param string|array $filename
     * @return $this
     */
    public function notName($filename): self
    {
        $this->notNames = \array_merge($this->notNames, (array)$filename);

        return $this;
    }

    /**
     * @param string|array $dirs
     * @return $this
     */
    public function addExclude($dirs): self
    {
        $this->excludes = \array_merge($this->excludes, (array)$dirs);

        return $this;
    }

    /**
     * @param string|array $files
     * @return $this
     */
    public function addFile($files): self
    {
        $this->files = \array_merge($this->files, (array)$files);

        return $this;
    }

    /**
     * @param bool $value
     * @return PharCompiler
     */
    public function stripComments($value): self
    {
        $this->stripComments = (bool)$value;

        return $this;
    }

    /**
     * @param bool $value
     * @return PharCompiler
     */
    public function collectVersion($value): self
    {
        $this->collectVersionInfo = (bool)$value;

        return $this;
    }

    /**
     * @param \Closure $stripFilter
     * @return PharCompiler
     */
    public function setStripFilter(\Closure $stripFilter): PharCompiler
    {
        $this->stripFilter = $stripFilter;

        return $this;
    }

    /**
     * @param bool|string $shebang
     * @return PharCompiler
     */
    public function setShebang($shebang): PharCompiler
    {
        $this->shebang = $shebang;

        return $this;
    }

    /**
     * @param string|array $dirs
     * @return PharCompiler
     */
    public function in($dirs): self
    {
        $this->directories = \array_merge($this->directories, (array)$dirs);

        return $this;
    }

    /**
     * @param array|\Iterator $modifies
     * @return PharCompiler
     */
    public function setModifies($modifies): self
    {
        $this->modifies = $modifies;

        return $this;
    }

    /**
     * Compiles composer into a single phar file
     * @param  string $pharFile The full path to the file to create
     * @param bool    $refresh
     * @return string
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function pack(string $pharFile, $refresh = true): string
    {
        if (!$this->directories) {
            throw new \RuntimeException("Please setting the 'directories' want building directories by 'in()'");
        }

        $exists = \file_exists($pharFile);

        if ($refresh && $exists) {
            \unlink($pharFile);
        }

        $this->pharFile = $pharFile;
        $this->pharName = $pharName = \basename($this->pharFile);
        $this->excludes = \array_flip($this->excludes);

        $this->collectInformation();

        $phar = new \Phar($pharFile, 0, $pharName);

        if ($this->key !== null) {
            $privateKey = '';
            \openssl_pkey_export($this->key, $privateKey);
            $phar->setSignatureAlgorithm(\Phar::OPENSSL, $privateKey);
            $keyDetails = \openssl_pkey_get_details($this->key);
            \file_put_contents($pharFile . '.pubkey', $keyDetails['key']);
        } else {
            $phar->setSignatureAlgorithm($this->selectSignatureType());
        }

        $basePath = $this->basePath;
        $phar->startBuffering();

        // only build modifies
        if (!$refresh && $exists && $this->modifies) {
            foreach ($this->modifies as $file) {
                if ('/' === $file[0] || \is_file($file = $basePath . '/' . $file)) {
                    $this->packFile($phar, new \SplFileInfo($file));
                }
            }
        } else {
            // collect files in there are dirs.
            foreach ($this->directories as $directory) {
                foreach ($this->findFiles($directory) as $file) {
                    $this->packFile($phar, $file);
                }
            }
        }

        // add special files
        foreach ($this->files as $filename) {
            if ('/' === $filename[0] || \is_file($filename = $basePath . '/' . $filename)) {
                $this->packFile($phar, new \SplFileInfo($filename));
            }
        }

        // add index files
        $this->packIndexFile($phar);

        // Stubs
        // $phar->setDefaultStub($this->cliIndex, $this->webIndex));
        $phar->setStub($this->createStub());

        if ($this->compressMode) {
            $phar->compressFiles($this->compressMode);
        }

        $phar->stopBuffering();
        unset($phar);

        // re-sign the phar with reproducible timestamp / signature
        if (\class_exists(Timestamps::class)) {
            $util = new Timestamps($pharFile);
            $util->updateTimestamps($this->versionDate);
            $util->save($pharFile, \Phar::SHA1);
        }

        return $pharFile;
    }

    /**
     * find changed or new created files by git status.
     * @return \Generator
     */
    public function findChangedByGit()
    {
        // -u expand dir's files
        list(, $output,) = Sys::run('git status -s -u', $this->basePath);

        // 'D some.file'    deleted
        // ' M some.file'   modified
        // '?? some.file'   new file
        foreach (\explode("\n", \trim($output)) as $file) {
            $file = \trim($file);

            // only php file.
            if (!\strpos($file, '.php')) {
                continue;
            }

            // modified files
            if (\strpos($file, 'M ') === 0) {
                yield \substr($file, 2);

                // new files
            } elseif (\strpos($file, '?? ') === 0) {
                yield \substr($file, 3);
            }
        }
    }

    /**
     * @param string $directory
     * @return \Iterator|\SplFileInfo[]
     */
    protected function findFiles(string $directory)
    {
        return Helper::directoryIterator(
            $directory,
            $this->createIteratorFilter(),
            \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
        );
    }

    /**
     * Add a file to the Phar.
     * @param \Phar        $phar
     * @param \SplFileInfo $file
     */
    private function packFile(\Phar $phar, \SplFileInfo $file)
    {
        // skip error
        if (!\file_exists($file)) {
            $this->reportError("File $file is not exists!");
            return;
        }

        $this->counter++;
        $path = $this->getRelativeFilePath($file);
        $strip = $this->stripComments;
        $content = \file_get_contents($file);

        // clear php file comments
        if ($strip && \strpos($path, '.php')) {
            $filter = $this->stripFilter;

            if (!$filter || ($filter && $filter($file))) {
                $content = $this->stripWhitespace($content);
            }
        }

        // have versionFile
        if ($path === $this->versionFile) {
            $content = \str_replace([
                '{@package_version}',
                '{@package_branch_alias_version}',
                '{@release_date}',
            ], [
                $this->version,
                $this->branchAliasVersion,
                $this->versionDate->format('Y-m-d H:i:s')
            ], $content);
        }

        if ($cb = $this->events['add']) {
            $cb($path, $this->counter);
        }

        $phar->addFromString($path, $content);
    }

    /**
     * @param \Phar $phar
     */
    private function packIndexFile(\Phar $phar)
    {
        if ($this->cliIndex) {
            $this->counter++;
            $path = $this->basePath . '/' . $this->cliIndex;
            $content = \preg_replace('{^#!/usr/bin/env php\s*}', '', \file_get_contents($path));

            if ($cb = $this->events['add']) {
                $cb($this->cliIndex, $this->counter);
            }

            $phar->addFromString($this->cliIndex, $content);
        }

        if ($this->webIndex) {
            $this->counter++;
            $path = $this->basePath . '/' . $this->webIndex;
            $content = \file_get_contents($path);

            if ($cb = $this->events['add']) {
                $cb($this->webIndex, $this->counter);
            }

            $phar->addFromString($this->webIndex, $content);
        }
    }

    /**
     * @return int
     */
    public function getCounter(): int
    {
        return $this->counter;
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    private function createStub(): string
    {
        // var_dump($this);die;
        $date = \date('Y-m-d H:i');
        $pharName = $this->pharName;
        $stub = <<<EOF
<?php
/**
 * @date $date
 * @author inhere <in.798@qq.com>
 */

define('IN_PHAR', true);
Phar::mapPhar('$pharName');

EOF;
        // add shebang
        if ($shebang = $this->shebang) {
            $shebang = \is_string($shebang) ? $shebang : '#!/usr/bin/env php';
            $stub = "$shebang\n$stub";
        }

        if ($this->cliIndex && $this->webIndex) {
            $stub .= <<<EOF
// for command line            
if (PHP_SAPI === 'cli') {
    require 'phar://$pharName/{$this->cliIndex}';
} else {
    require 'phar://$pharName/{$this->webIndex}';
}
EOF;
        } elseif ($this->cliIndex) {
            $stub .= "\nrequire 'phar://$pharName/{$this->cliIndex}';\n";
        } elseif ($this->webIndex) {
            $stub .= "\nrequire 'phar://$pharName/{$this->webIndex}';\n";
        } else {
            throw new \RuntimeException("'cliIndex' and 'webIndex', please set at least one");
        }

        return $stub . "\n__HALT_COMPILER();\n";
    }

    /**
     * @return \Closure
     */
    private function createIteratorFilter(): \Closure
    {
        if (!$this->fileFilter) {
            $this->fileFilter = function (\SplFileInfo $file) {
                $name = $file->getFilename();

                // Skip hidden files and directories.
                if (\strpos($name, '.') === 0) {
                    return false;
                }

                // skip exclude directories.
                if ($file->isDir()) {
                    return !isset($this->excludes[$name]);
                }

                // skip exclude files.
                if ($this->notNames && \in_array($name, $this->notNames, true)) {
                    return false;
                }

                if ($this->suffixes) {
                    foreach ($this->suffixes as $suffix) {
                        if (\stripos($name, $suffix)) {
                            return true;
                        }
                    }
                    return false;
                }

                return true;
            };
        }

        return $this->fileFilter;
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     * @param  string $source A PHP string
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace(string $source): string
    {
        if (!\function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (\token_get_all($source) as $token) {
            if (\is_string($token)) {
                $output .= $token;
            } elseif (\in_array($token[0], [\T_COMMENT, \T_DOC_COMMENT], true)) {
                $output .= \str_repeat("\n", \substr_count($token[1], "\n"));
            } elseif (\T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = \preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = \preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = \preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    /**
     * auto collect project information by git log
     * @throws \RuntimeException
     * @throws \Exception
     */
    private function collectInformation()
    {
        if (!$this->collectVersionInfo) {
            return;
        }

        $basePath = $this->basePath;
        list($code, $ret,) = Sys::run('git log --pretty="%H" -n1 HEAD', $basePath);

        if ($code !== 0) {
            throw new \RuntimeException(
                'Can\'t run git log. You must ensure to run compile from git repository clone and that git binary is available.'
            );
        }

        $this->version = \trim($ret);

        list($code, $ret,) = Sys::run('git log -n1 --pretty=%ci HEAD', $basePath);

        if ($code !== 0) {
            throw new \RuntimeException(
                'Can\'t run git log. You must ensure to run compile from git repository clone and that git binary is available.'
            );
        }

        $this->versionDate = new \DateTime(\trim($ret));
        $this->versionDate->setTimezone(new \DateTimeZone('UTC'));

        // 获取到最新的 tag
        list($code, $ret,) = Sys::run('git describe --tags --exact-match HEAD', $basePath);
        if ($code === 0) {
            $this->version = \trim($ret);
        } else {
            list($code1, $ret,) = Sys::run('git branch', $basePath);

            if ($code1 === 0) {
                $this->branchAliasVersion = \explode("\n", \trim($ret, "* \n"), 2)[0];
            }
        }
    }

    /**
     * @param  \SplFileInfo $file
     * @return string
     */
    private function getRelativeFilePath($file): string
    {
        $realPath = $file->getRealPath();
        $pathPrefix = $this->basePath . DIRECTORY_SEPARATOR;

        $pos = \strpos($realPath, $pathPrefix);
        $relativePath = $pos !== false ? \substr_replace($realPath, '', $pos, \strlen($pathPrefix)) : $realPath;

        return \str_replace('\\', '/', $relativePath);
    }

    /**
     * @param string $error
     */
    private function reportError($error)
    {
        if ($cb = $this->events['error']) {
            $cb($error);
        }
    }

    /**
     * add event handler
     * @param string   $event
     * @param \Closure $closure
     */
    public function on(string $event, \Closure $closure)
    {
        $this->events[$event] = $closure;
    }

    /**
     * @param \Closure $onAdd
     */
    public function onAdd(\Closure $onAdd)
    {
        $this->events['add'] = $onAdd;
    }

    /**
     * @param \Closure $onError
     */
    public function onError(\Closure $onError)
    {
        $this->events['error'] = $onError;
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
     * @return array
     */
    public function getExcludes(): array
    {
        return $this->excludes;
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
     * @return \Closure[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @return string|null
     */
    public function getVersionFile()
    {
        return $this->versionFile;
    }

    /**
     * @param string $versionFile
     * @return PharCompiler
     */
    public function setVersionFile(string $versionFile): PharCompiler
    {
        $this->versionFile = $versionFile;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param null|string $version
     * @return PharCompiler
     */
    public function setVersion(string $version): PharCompiler
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getBranchAliasVersion()
    {
        return $this->branchAliasVersion;
    }

    /**
     * @param null|string $branchAliasVersion
     * @return PharCompiler
     */
    public function setBranchAliasVersion(string $branchAliasVersion): PharCompiler
    {
        $this->branchAliasVersion = $branchAliasVersion;
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

    /**
     * @return bool
     */
    public function hasDirectory(): bool
    {
        return !empty($this->directories);
    }
}
