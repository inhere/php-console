<?php declare(strict_types=1);


namespace Inhere\Console\IO\Input;


use Inhere\Console\IO\AbstractInput;
use InvalidArgumentException;
use Toolkit\FsUtil\File;
use Toolkit\Stdlib\OS;
use function fwrite;
use function stream_get_meta_data;
use function strpos;
use const STDIN;

/**
 * Class StreamInput
 * @package Inhere\Console\IO\Input
 */
class StreamInput extends AbstractInput
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
    public function __construct($stream = STDIN)
    {
        $this->setStream($stream);
    }

    public function toString(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function readAll(): string
    {
        return File::streamReadAll($this->stream);
    }

    /**
     * @param string $question
     * @param bool   $nl
     *
     * @return string
     */
    public function read(string $question = '', bool $nl = false): string
    {
        if ($question) {
            fwrite($this->stream, $question . ($nl ? "\n" : ''));
        }

        return File::streamFgets($this->stream);
    }

    /**
     * @param string $question
     * @param bool   $nl
     *
     * @return string
     */
    public function readln(string $question = '', bool $nl = false): string
    {
        if ($question) {
            fwrite($this->stream, $question . ($nl ? "\n" : ''));
        }

        return File::streamFgets($this->stream);
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
        if (strpos($meta['mode'], 'r') === false && strpos($meta['mode'], '+') === false) {
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