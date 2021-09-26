<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component\Formatter;

use Inhere\Console\Component\MessageFormatter;
use Toolkit\Stdlib\Str;
use function json_decode;

/**
 * class JSONPretty
 */
class JSONPretty extends MessageFormatter
{
    /**
     * @var array|iterable
     */
    public $data;

    /**
     * @param string $json
     *
     * @return static
     */
    public static function newFromString(string $json): self
    {
        $self = new self();
        $self->setData((array)json_decode($json, true));

        return $self;
    }

    /**
     * @return string
     */
    public function format(): string
    {
        $buf = Str\StrBuffer::new();

        foreach ($this->data as $key => $item) {
            // TODO
        }

        return $buf->toString();
    }

    /**
     * @param array|iterable $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }
}
