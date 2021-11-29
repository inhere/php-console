<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component;

use Inhere\Console\Application;
use Toolkit\Stdlib\Obj;

/**
 * Class CompletionDumper
 * - Generate auto completion script for zsh/bash/sh shell
 *
 * @package Inhere\Console\Component
 */
class CompletionDumper extends Obj\AbstractObj
{
    /**
     * @var Application|null
     */
    public ?Application $app;
}
