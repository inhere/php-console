<?php declare(strict_types=1);

namespace Inhere\Console;

/**
 * Class ConsoleEvent
 *
 * @package Inhere\Console
 */
final class ConsoleEvent
{
    // event name list
    public const ON_BEFORE_RUN = 'app.beforeRun';

    public const ON_AFTER_RUN  = 'app.afterRun';

    public const ON_RUN_ERROR  = 'app.runError';

    public const ON_STOP_RUN   = 'app.stopRun';

    public const ON_NOT_FOUND  = 'app.notFound';
}
