<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-01-05
 * Time: 09:54
 */

namespace Inhere\Console\Traits;

use Inhere\Console\AbstractHandler;
use Inhere\Console\Component\Style\Style;
use Inhere\Console\Console;
use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Router;
use Inhere\Console\Util\FormatUtil;
use Inhere\Console\Util\Show;
use Toolkit\Cli\ColorTag;
use function array_merge;
use function date;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function get_class;
use function implode;
use function is_object;
use function is_string;
use function is_subclass_of;
use function ksort;
use function sprintf;
use function str_replace;
use function strtr;
use const PHP_EOL;
use const PHP_OS;
use const PHP_VERSION;

/**
 * Trait ApplicationHelpTrait
 *
 * @package Inhere\Console\Traits
 */
trait ApplicationHelpTrait
{
    /***************************************************************************
     * Show information for the application
     ***************************************************************************/

    /**
     * Display the application version information
     */
    public function showVersionInfo(): void
    {
        $os         = PHP_OS;
        $date       = date('Y.m.d');
        $logo       = '';
        $name       = $this->getParam('name', 'Console Application');
        $version    = $this->getParam('version', 'Unknown');
        $publishAt  = $this->getParam('publishAt', 'Unknown');
        $updateAt   = $this->getParam('updateAt', 'Unknown');
        $phpVersion = PHP_VERSION;

        if ($logoTxt = $this->getLogoText()) {
            $logo = ColorTag::wrap($logoTxt, $this->getLogoStyle());
        }

        /** @var Output $out */
        $out = $this->output;
        $out->aList([
            "$logo\n  <info>{$name}</info>, Version <comment>$version</comment>\n",
            'System Info'      => "PHP version <info>$phpVersion</info>, on <info>$os</info> system",
            'Application Info' => "Update at <info>$updateAt</info>, publish at <info>$publishAt</info>(current $date)",
        ], '', [
            'leftChar' => '',
            'sepChar'  => ' :  '
        ]);
    }

    /**
     * Display the application help information
     *
     * @param string $command
     */
    public function showHelpInfo(string $command = ''): void
    {
        /** @var Input $in */
        $in = $this->input;

        // display help for a special command
        if ($command) {
            $in->setCommand($command);
            $in->setSOpt('h', true);
            $in->clearArgs();
            $this->dispatch($command);
            return;
        }

        $sep    = $this->delimiter;
        $script = $in->getScript();

        /** @var Output $out */
        $out = $this->output;
        $out->helpPanel([
            'usage'   => "$script <info>{command}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
            'example' => [
                "$script test (run a independent command)",
                "$script home{$sep}index (run a command of the group)",
                "$script help {command} (see a command help information)",
                "$script home{$sep}index -h (see a command help of the group)",
                "$script --auto-completion --shell-env [zsh|bash] [--gen-file stdout]",
            ]
        ]);
    }

    /**
     * Display the application group/command list information
     */
    public function showCommandList(): void
    {
        /** @var Input $input */
        $input = $this->input;
        // has option: --auto-completion
        $autoComp = $input->getBoolOpt('auto-completion');
        // has option: --shell-env
        $shellEnv = (string)$input->getLongOpt('shell-env', '');

        // php bin/app list --only-name
        if ($autoComp && $shellEnv === 'bash') {
            $this->dumpAutoCompletion($shellEnv, []);
            return;
        }

        /** @var Output $output */
        $output = $this->output;
        /** @var Router $router */
        $router = $this->getRouter();
        $script = $this->getScriptName();

        $hasGroup    = $hasCommand = false;
        $groupArr    = $commandArr = [];
        $placeholder = 'No description of the command';

        // all console groups/controllers
        if ($groups = $router->getControllers()) {
            $hasGroup = true;
            ksort($groups);
        }

        // all independent commands, Independent, Single, Alone
        if ($commands = $router->getCommands()) {
            $hasCommand = true;
            ksort($commands);
        }

        // add split title on both exists.
        if (!$autoComp && $hasCommand && $hasGroup) {
            $groupArr[]   = PHP_EOL . '- <bold>Group Commands</bold>';
            $commandArr[] = PHP_EOL . '- <bold>Alone Commands</bold>';
        }

        foreach ($groups as $name => $info) {
            $options    = $info['options'];
            $controller = $info['handler'];
            /** @var AbstractHandler $controller */
            $desc    = $controller::getDescription() ?: $placeholder;
            $aliases = $options['aliases'];
            $extra   = $aliases ? ColorTag::wrap(' [alias: ' . implode(',', $aliases) . ']', 'info') : '';

            // collect
            $groupArr[$name] = $desc . $extra;
        }

        if (!$hasGroup && $this->isDebug()) {
            $groupArr[] = '... Not register any group command(controller)';
        }

        foreach ($commands as $name => $info) {
            $desc    = $placeholder;
            $options = $info['options'];
            $command = $info['handler'];

            /** @var AbstractHandler $command */
            if (is_subclass_of($command, CommandInterface::class)) {
                $desc = $command::getDescription() ?: $placeholder;
            } elseif ($msg = $options['description'] ?? '') {
                $desc = $msg;
            } elseif (is_string($command)) {
                $desc = 'A handler : ' . $command;
            } elseif (is_object($command)) {
                $desc = 'A handler by ' . get_class($command);
            }

            $aliases = $options['aliases'];
            $extra   = $aliases ? ColorTag::wrap(' [alias: ' . implode(',', $aliases) . ']', 'info') : '';

            $commandArr[$name] = $desc . $extra;
        }

        if (!$hasCommand && $this->isDebug()) {
            $commandArr[] = '... Not register any alone command';
        }

        // built in commands
        $internalCommands = static::$internalCommands;

        if ($autoComp && $shellEnv === 'zsh') {
            $map = array_merge($internalCommands, $groupArr, $commandArr);
            $this->dumpAutoCompletion('zsh', $map);
            return;
        }

        ksort($internalCommands);
        Console::startBuffer();

        if ($appDesc = $this->getParam('description', '')) {
            $appVer = $this->getParam('version', '');
            Console::writeln(sprintf('%s%s' . PHP_EOL, $appDesc, $appVer ? " (Version: <info>$appVer</info>)" : ''));
        }

        // built in options
        $internalOptions = FormatUtil::alignOptions(self::$globalOptions);

        Show::mList([
            'Usage:'              => "$script <info>{command}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
            'Options:'            => $internalOptions,
            'Internal Commands:'  => $internalCommands,
            'Available Commands:' => array_merge($groupArr, $commandArr),
        ], [
            'sepChar' => '  ',
        ]);

        unset($groupArr, $commandArr, $internalCommands);
        Console::write("More command information, please use: <cyan>$script {command} -h</cyan>");
        Console::flushBuffer();
    }

    /**
     * zsh:
     *  php examples/app --auto-completion  --shell-env zsh
     *  php examples/app --auto-completion --shell-env zsh --gen-file
     *  php examples/app --auto-completion --shell-env zsh --gen-file stdout
     * bash:
     *  php examples/app --auto-completion --shell-env bash
     *  php examples/app --auto-completion --shell-env bash --gen-file
     *  php examples/app --auto-completion --shell-env bash --gen-file stdout
     *
     * @param string $shellEnv
     * @param array  $data
     */
    protected function dumpAutoCompletion(string $shellEnv, array $data): void
    {
        /** @var Input $input */
        $input = $this->input;
        /** @var Output $output */
        $output = $this->output;
        /** @var Router $router */
        $router = $this->getRouter();

        // info
        $glue     = ' ';
        $genFile  = (string)$input->getLongOpt('gen-file');
        $filename = 'auto-completion.' . $shellEnv;
        $tplDir   = dirname(__DIR__, 2) . '/resource/templates';

        if ($shellEnv === 'bash') {
            $tplFile = $tplDir . '/bash-completion.tpl';
            $list    = array_merge($router->getCommandNames(), $router->getControllerNames(),
                $this->getInternalCommands());
        } else {
            $glue    = PHP_EOL;
            $list    = [];
            $tplFile = $tplDir . '/zsh-completion.tpl';
            foreach ($data as $name => $desc) {
                $list[] = $name . ':' . str_replace(':', '\:', $desc);
            }
        }

        $commands = implode($glue, $list);

        // dump to stdout.
        if (!$genFile) {
            $output->write($commands, true, false, ['color' => false]);
            return;
        }

        if ($shellEnv === 'zsh') {
            $commands = "'" . implode("'\n'", $list) . "'";
            $commands = Style::stripColor($commands);
        }

        // dump at script file
        $binName = $input->getBinName();
        $tplText = file_get_contents($tplFile);
        $content = strtr($tplText, [
            '{{version}}'    => $this->getVersion(),
            '{{filename}}'   => $filename,
            '{{commands}}'   => $commands,
            '{{binName}}'    => $binName,
            '{{datetime}}'   => date('Y-m-d H:i:s'),
            '{{fmtBinName}}' => str_replace('/', '_', $binName),
        ]);

        // dump to stdout
        if ($genFile === 'stdout') {
            file_put_contents('php://stdout', $content);
            return;
        }

        $targetFile = $input->getPwd() . '/' . $filename;
        $output->write(['Target File:', $targetFile, '']);

        if (file_put_contents($targetFile, $content) > 10) {
            $output->success("O_O! Generate $filename successful!");
        } else {
            $output->error("O^O! Generate $filename failure!");
        }
    }
}
