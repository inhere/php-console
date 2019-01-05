<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-01-05
 * Time: 09:54
 */

namespace Inhere\Console\Traits;

use Inhere\Console\Face\CommandInterface;
use Inhere\Console\Util\FormatUtil;
use Inhere\Console\Util\Helper;

/**
 * Trait ApplicationHelpTrait
 * @package Inhere\Console\Traits
 */
trait ApplicationHelpTrait
{
    /**
     * show the application version information
     * @param bool $quit
     */
    public function showVersionInfo($quit = true)
    {
        $os = \PHP_OS;
        $date = \date('Y.m.d');
        $logo = '';
        $name = $this->getConfig('name', 'Console Application');
        $version = $this->getConfig('version', 'Unknown');
        $publishAt = $this->getConfig('publishAt', 'Unknown');
        $updateAt = $this->getConfig('updateAt', 'Unknown');
        $phpVersion = \PHP_VERSION;

        if ($logoTxt = $this->getLogoText()) {
            $logo = Helper::wrapTag($logoTxt, $this->getLogoStyle());
        }

        /** @var \Inhere\Console\IO\Output $out */
        $out = $this->output;
        $out->aList([
            "$logo\n  <info>{$name}</info>, Version <comment>$version</comment>\n",
            'System Info'      => "PHP version <info>$phpVersion</info>, on <info>$os</info> system",
            'Application Info' => "Update at <info>$updateAt</info>, publish at <info>$publishAt</info>(current $date)",
        ], null, [
            'leftChar' => '',
            'sepChar'  => ' :  '
        ]);

        $quit && $this->stop();
    }

    /***************************************************************************
     * some information for the application
     ***************************************************************************/

    /**
     * show the application help information
     * @param bool   $quit
     * @param string $command
     */
    public function showHelpInfo(bool $quit = true, string $command = '')
    {
        /** @var \Inhere\Console\IO\Input $in */
        $in = $this->input;

        // display help for a special command
        if ($command) {
            $in->setCommand($command);
            $in->setSOpt('h', true);
            $in->clearArgs();
            $this->dispatch($command);
            $quit && $this->stop();
        }

        $sep = $this->delimiter;
        $script = $in->getScript();

        /** @var \Inhere\Console\IO\Output $out */
        $out = $this->output;
        $out->helpPanel([
            'usage'   => "$script <info>{command}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
            'example' => [
                "$script test (run a independent command)",
                "$script home{$sep}index (run a command of the group)",
                "$script help {command} (see a command help information)",
                "$script home{$sep}index -h (see a command help of the group)",
            ]
        ], false);

        $quit && $this->stop();
    }

    /**
     * show the application command list information
     * @param bool $quit
     */
    public function showCommandList($quit = true)
    {
        $script = $this->getScriptName();
        $hasGroup = $hasCommand = false;
        $controllerArr = $commandArr = [];
        $desPlaceholder = 'No description of the command';

        // all console controllers
        if ($controllers = $this->controllers) {
            $hasGroup = true;
            \ksort($controllers);
        }

        // all independent commands, Independent, Single, Alone
        if ($commands = $this->commands) {
            $hasCommand = true;
            \ksort($commands);
        }

        // add split title on both exists.
        if ($hasCommand && $hasGroup) {
            $commandArr[] = \PHP_EOL . '- <bold>Alone Commands</bold>';
            $controllerArr[] = \PHP_EOL . '- <bold>Group Commands</bold>';
        }

        foreach ($controllers as $name => $controller) {
            /** @var \Inhere\Console\AbstractCommand $controller */
            $desc = $controller::getDescription() ?: $desPlaceholder;
            $aliases = $this->getCommandAliases($name);
            $extra = $aliases ? Helper::wrapTag(' [alias: ' . \implode(',', $aliases) . ']', 'info') : '';
            $controllerArr[$name] = $desc . $extra;
        }

        if (!$hasGroup && $this->isDebug()) {
            $controllerArr[] = '... Not register any group command(controller)';
        }

        foreach ($commands as $name => $command) {
            $desc = $desPlaceholder;

            /** @var \Inhere\Console\AbstractCommand $command */
            if (\is_subclass_of($command, CommandInterface::class)) {
                $desc = $command::getDescription() ?: $desPlaceholder;
            } elseif ($msg = $this->getCommandMetaValue($name, 'description')) {
                $desc = $msg;
            } elseif (\is_string($command)) {
                $desc = 'A handler : ' . $command;
            } elseif (\is_object($command)) {
                $desc = 'A handler by ' . \get_class($command);
            }

            $aliases = $this->getCommandAliases($name);
            $extra = $aliases ? Helper::wrapTag(' [alias: ' . \implode(',', $aliases) . ']', 'info') : '';
            $commandArr[$name] = $desc . $extra;
        }

        if (!$hasCommand && $this->isDebug()) {
            $commandArr[] = '... Not register any alone command';
        }

        // built in commands
        $internalCommands = static::$internalCommands;
        \ksort($internalCommands);

        /** @var \Inhere\Console\IO\Output $output */
        $output = $this->output;
        // built in options
        $internalOptions = FormatUtil::alignOptions(self::$internalOptions);

        $output->mList([
            'Usage:'              => "$script <info>{command}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
            'Options:'            => $internalOptions,
            'Internal Commands:'  => $internalCommands,
            'Available Commands:' => \array_merge($controllerArr, $commandArr),
        ], [
            'sepChar' => '  ',
        ]);

        unset($controllerArr, $commandArr, $internalCommands);
        $output->write("More command information, please use: <cyan>$script {command} -h</cyan>");

        $quit && $this->stop();
    }

}
