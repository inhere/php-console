<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

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

    // ----- group command and subcommand

    // every command/subcommand run will fire it
    public const COMMAND_RUN_BEFORE = 'every.command.run.before';

    // every command/subcommand exec will fire it
    public const COMMAND_EXEC_BEFORE = 'every.command.exec.before';

    public const COMMAND_EXEC_AFTER  = 'every.command.exec.after';

    public const SUBCOMMAND_RUN_BEFORE = 'group.subcommand.run.before';

    // ----- group/subcommand

    public const SUBCOMMAND_NOT_FOUND = 'group.subcommand.not.found';

    public const BEFORE_RENDER_GROUP_HELP = 'group.help.render.before';

    public const AFTER_RENDER_GROUP_HELP  = 'group.help.render.after';

    public const BEFORE_RENDER_SUBCOMMAND_HELP = 'group.command.help.render.before';

    public const AFTER_RENDER_SUBCOMMAND_HELP  = 'group.command.help.render.after';

    // ----- command

    public const BEFORE_RENDER_COMMAND_HELP = 'command.help.render.before';

    public const AFTER_RENDER_COMMAND_HELP  = 'command.help.render.after';
}
