<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO\Output;

use Inhere\Console\IO\AbstractOutput;
use InvalidArgumentException;
use Toolkit\FsUtil\File;
use Toolkit\Stdlib\OS;
use function stream_get_meta_data;
use function strpos;
use const PHP_EOL;
use const STDOUT;

/**
 * Class StreamOutput
 * @package Inhere\Console\IO\Output
 */
class StreamOutput extends AbstractOutput
{
    /**
     * @var resource
     */
    protected $stream;

    /**
     * StreamInput constructor.
     *
     * @param resource $stream
     */
    public function __construct($stream = STDOUT)
    {
        $this->setStream($stream);
    }

    /**
     * @param string $content
     *
     * @return int
     */
    public function write(string $content): int
    {
        return File::streamWrite($this->stream, $content);
    }

    /**
     * @param string $content
     *
     * @return int
     */
    public function writeln(string $content): int
    {
        return File::streamWrite($this->stream, $content . PHP_EOL);
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @param resource $stream
     */
    protected function setStream($stream): void
    {
        File::assertStream($stream);

        $meta = stream_get_meta_data($stream);
        if (strpos($meta['mode'], 'w') === false && strpos($meta['mode'], '+') === false) {
            throw new InvalidArgumentException('Expected a readable stream');
        }

        $this->stream = $stream;
    }

    /**
     * Whether the stream is an interactive terminal
     *
     * @return bool
     */
    public function isInteractive(): bool
    {
        return OS::isInteractive($this->stream);
    }
}
