<?php declare(strict_types=1);

namespace Inhere\Console\Component\Interact;

use Inhere\Console\Component\InteractiveHandle;
use RuntimeException;
use Toolkit\Sys\Sys;
use Toolkit\Sys\Util\ShellUtil;
use function addslashes;
use function escapeshellarg;
use function file_put_contents;
use function rtrim;
use function shell_exec;
use function sprintf;
use function unlink;

/**
 * Class Password
 *
 * @package Inhere\Console\Component\Interact
 */
class Password extends InteractiveHandle
{
    /**
     * Interactively prompts for input without echoing to the terminal.
     * Requires a bash shell or Windows and won't work with
     * safe_mode settings (Uses `shell_exec`)
     *
     * @param string $prompt
     *
     * @return string
     * @throws RuntimeException
     * @link http://www.sitepoint.com/blogs/2009/05/01/interactive-cli-password-prompt-in-php
     * @link https://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
     */
    public static function ask(string $prompt = 'Enter Password:'): string
    {
        $prompt = $prompt ? addslashes($prompt) : 'Enter:';

        // $checkCmd = "/usr/bin/env bash -c 'echo OK'";
        // $shell = 'echo $0';

        // linux, unix, git-bash
        if (ShellUtil::shIsAvailable()) {
            // COMMAND: sh -c 'read -p "Enter Password:" -s user_input && echo $user_input'
            $command  = sprintf('sh -c "read -p \'%s\' -s user_input && echo $user_input"', $prompt);
            $password = Sys::execute($command, false);

            print "\n";
            return $password;
        }

        // at windows cmd.
        if (Sys::isWindows()) {
            $vbFile = Sys::getTempDir() . '/hidden_prompt_input.vbs';

            file_put_contents($vbFile, sprintf('wscript.echo(InputBox("%s", "", "password here"))', $prompt));

            $command  = 'cscript //nologo ' . escapeshellarg($vbFile);
            $password = rtrim(shell_exec($command));
            unlink($vbFile);

            return $password;
        }

        throw new RuntimeException('Can not invoke bash shell env');
    }
}
