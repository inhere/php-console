<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO\Input;

use Inhere\Console\Concern\AbstractInput;
use InvalidArgumentException;
use Toolkit\FsUtil\File;
use Toolkit\Stdlib\OS;
use function fwrite;
use function stream_get_meta_data;
use const STDIN;

/**
 * Class StreamInput
 *
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

    /**
     * @param bool $blocking
     *
     * @return string
     */
    public function readAll(bool $blocking = true): string
    {
        return File::streamReadAll($this->stream, $blocking);
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
        if (!str_contains($meta['mode'], 'r') && !str_contains($meta['mode'], '+')) {
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
