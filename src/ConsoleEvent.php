<?php declare(strict_types=1);

namespace Inhere\Console;

/**
 * Class ConsoleEvent
 * - event name list
 *
 * @package Inhere\Console
 */
final class ConsoleEvent
{
    // ----- application

    public const ON_BEFORE_RUN = 'app.beforeRun';

    public const ON_AFTER_RUN = 'app.afterRun';

    public const ON_RUN_ERROR = 'app.runError';

    public const ON_STOP_RUN = 'app.stopRun';

    // on group OR command not found
    public const ON_NOT_FOUND = 'app.notFound';

    public const BEFORE_RENDER_APP_HELP = 'app.help.render.before';

    public const BEFORE_RENDER_APP_VERSION = 'app.version.render.before';

    public const BEFORE_RENDER_APP_COMMANDS_LIST = 'app.commands.list.render.before';

    // ----- group/sub-command

    public const SUB_COMMAND_NOT_FOUND = 'group.command.notFound';

    public const BEFORE_RENDER_GROUP_HELP = 'group.help.render.before';

    public const AFTER_RENDER_GROUP_HELP  = 'group.help.render.after';

    public const BEFORE_RENDER_SUB_COMMAND_HELP = 'group.command.help.render.before';

    public const AFTER_RENDER_SUB_COMMAND_HELP  = 'group.command.help.render.after';

    // ----- command

    public const BEFORE_RENDER_COMMAND_HELP = 'command.help.render.before';

    public const AFTER_RENDER_COMMAND_HELP  = 'command.help.render.after';
}
