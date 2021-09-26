<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO\Output;

use function fopen;

/**
 * Class BufferOutput
 *
 * @package Inhere\Console\IO\Output
 */
class MemoryOutput extends StreamOutput
{
    public function __construct()
    {
        parent::__construct(fopen('php://memory', 'rwb'));
    }

    public function getBuffer(): string
    {
        return '';
    }
}
