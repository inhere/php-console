<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Concern;

use Inhere\Console\Contract\OutputInterface;

/**
 * Class AbstractOutput
 *
 * @package Inhere\Console\Concern
 */
abstract class AbstractOutput implements OutputInterface
{
    /**
     * @return bool
     */
    public function isInteractive(): bool
    {
        return false;
    }
}
