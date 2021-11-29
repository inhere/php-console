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
use Inhere\Console\Component\Interact\IShell;

/**
 * Class ConsoleAppIShell
 *
 * @package Inhere\Console
 */
class ConsoleAppIShell extends IShell
{
    /**
     * @var Application|null
     */
    public ?Application $app;

    // TODO start an shell for run app.
}
