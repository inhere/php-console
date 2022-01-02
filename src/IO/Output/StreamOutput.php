<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO\Output;

use Inhere\Console\Concern\AbstractOutput;
use InvalidArgumentException;
use Toolkit\FsUtil\File;
use Toolkit\Stdlib\Helper\DataHelper;
use Toolkit\Stdlib\OS;
use function sprintf;
use function stream_get_meta_data;
use const PHP_EOL;
use const STDOUT;

/**
 * Class StreamOutput
 * @package Inhere\Console\IO\Output
 */
class StreamOutput extends AbstractOutput
{
    /**
     * Normal output stream. Default is STDOUT
     *
     * @var resource
     */
    protected $stream = STDOUT;

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
     * @param string $format
     * @param mixed ...$args
     *
     * @return int
     */
    public function writef(string $format, ...$args): int
    {
        $content = sprintf($format, ...$args);

        return File::streamWrite($this->stream, $content);
    }

    /**
     * @param string $content
     * @param bool $quit
     * @param array $opts
     *
     * @return int
     */
    public function writeln($content, bool $quit = false, array $opts = []): int
    {
        $content = DataHelper::toString($content);

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
    public function setStream($stream): void
    {
        File::assertStream($stream);

        $meta = stream_get_meta_data($stream);
        if (!str_contains($meta['mode'], 'w') && !str_contains($meta['mode'], '+')) {
            throw new InvalidArgumentException('Expected a writeable stream');
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
