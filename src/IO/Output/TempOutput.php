<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO\Output;

use Toolkit\FsUtil\File;
use function fopen;

/**
 * Class BufferOutput
 *
 * @package Inhere\Console\IO\Output
 */
class TempOutput extends StreamOutput
{
    /**
     * Class constructor.
     *
     * @param array{stream: resource} $config
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['stream'])) {
            // Set the limit to 5 MB.
            $fiveMbs = 5 * 1024 * 1024;
            // open
            $config['stream'] = fopen("php://temp/maxmemory:$fiveMbs", 'rwb');
        }

        parent::__construct($config);
    }

    /**
     * @return string
     */
    public function fetch(): string
    {
        return File::streamReadAll($this->stream);
    }

    /**
     * @return string
     */
    public function getBuffer(): string
    {
        return $this->fetch();
    }
}
