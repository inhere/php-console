<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-01-05
 * Time: 09:54
 */

namespace Inhere\Console\Concern;

use Inhere\Console\AbstractHandler;
use Inhere\Console\Console;
use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Router;
use Inhere\Console\Util\FormatUtil;
use Inhere\Console\Util\Show;
use Toolkit\Cli\ColorTag;
use Toolkit\Cli\Style;
use function array_merge;
use function basename;
use function date;
use function dirname;
use function file_exists;
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
use function strpos;
use function strtr;
use const PHP_EOL;
use const PHP_OS;
use const PHP_VERSION;

/**
 * Trait ApplicationHelpTrait
 *
 * @package Inhere\Console\Concern
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
            "$logo\n  <info>$name</info>, Version <comment>$version</comment>\n",
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
            $this->debugf('display command help by use help: help COMMAND');
            $in->setCommand($command);
            $in->setSOpt('h', true);
            $in->clearArgs();
            $this->dispatch($command);
            return;
        }

        $this->debugf('display application help by input -h, --help');
        $delimiter = $this->delimiter;
        $binName   = $in->getScriptName();

        // built in options
        $globalOptions = self::$globalOptions;
        // append generate options:
        // php examples/app --auto-completion --shell-env zsh --gen-file
        // php examples/app --auto-completion --shell-env zsh --gen-file stdout
        if ($this->isDebug()) {
            $globalOptions['--auto-completion'] = 'Open generate auto completion script';
            $globalOptions['--shell-env']       = 'The shell env name for generate auto completion script';
            $globalOptions['--gen-file']        = 'The output file for generate auto completion script';
        }

        $globalOptions = FormatUtil::alignOptions($globalOptions);

        /** @var Output $out */
        $out = $this->output;
        $out->helpPanel([
            'Usage'   => "$binName <info>{command}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
            'Options' => $globalOptions,
            'Example' => [
                "$binName test                     run a independent command",
                "$binName home index               run a sub-command of the group",
                sprintf("$binName home%sindex               run a sub-command of the group", $delimiter),
                "$binName help {command}           see a command help information",
                "$binName home index -h            see a sub-command help of the group",
                sprintf("$binName home%sindex -h            see a sub-command help of the group", $delimiter),
            ],
            'Help'    => [
                'Generate shell auto completion scripts:',
                "  <info>$binName --auto-completion --shell-env [zsh|bash] [--gen-file stdout] [--tpl-file filepath]</info>",
                ' eg:',
                "  $binName --auto-completion --shell-env bash --gen-file stdout",
                "  $binName --auto-completion --shell-env zsh --gen-file stdout",
                "  $binName --auto-completion --shell-env bash --gen-file myapp.sh",
            ],
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
        // input is an path: /bin/bash
        if ($shellEnv && strpos($shellEnv, '/') !== false) {
            $shellEnv = basename($shellEnv);
        }

        // php bin/app list --only-name
        if ($autoComp && $shellEnv === 'bash') {
            $this->dumpAutoCompletion('bash', []);
            return;
        }

        $this->logf(Console::VERB_DEBUG, 'Display the application commands list');

        /** @var Output $output */ // $output = $this->output;
        /** @var Router $router */
        $router = $this->getRouter();

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
            $extra   = $aliases ? ColorTag::wrap(' (alias: ' . implode(',', $aliases) . ')', 'info') : '';

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

        $scriptName = $this->getScriptName();

        // built in options
        $globOpts = self::$globalOptions;

        Show::mList([
            'Usage:'              => "$scriptName <info>{COMMAND}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
            'Options:'            => FormatUtil::alignOptions($globOpts),
            'Internal Commands:'  => $internalCommands,
            'Available Commands:' => array_merge($groupArr, $commandArr),
        ], [
            'sepChar' => '  ',
        ]);

        unset($groupArr, $commandArr, $internalCommands);
        Console::write("More command information, please use: <cyan>$scriptName {command} -h</cyan>");
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
        $genFile  = $input->getStringOpt('gen-file', 'none');
        $tplDir   = dirname(__DIR__, 2) . '/resource/templates';

        if ($shellEnv === 'bash') {
            $tplFile = $tplDir . '/bash-completion.tpl';

            $list = array_merge(
                $router->getCommandNames(),
                $router->getControllerNames(),
                $this->getInternalCommands()
            );
        } else {
            $tplFile = $tplDir . '/zsh-completion.tpl';

            $glue = PHP_EOL;
            $list = [];
            foreach ($data as $name => $desc) {
                $list[] = $name . ':' . str_replace(':', '\:', $desc);
            }
        }

        // new: support custom tpl file for gen completion script
        $userTplFile = $input->getStringOpt('tpl-file');
        if ($userTplFile && file_exists($userTplFile)) {
            $tplFile = $userTplFile;
        }

        $commands = implode($glue, $list);

        // only dump commands to stdout.
        if ($genFile === 'none') {
            $output->write($commands, true, false, ['color' => false]);
            return;
        }

        if ($shellEnv === 'zsh') {
            $commands = "'" . implode("'\n'", $list) . "'";
            $commands = Style::stripColor($commands);
        }

        $toStdout = $genFile === 'stdout';
        $filename = 'auto-completion.' . $shellEnv;
        if (!$toStdout) {
            if ($genFile === '1') {
                $targetFile = $input->getPwd() . '/' . $filename;
            } else {
                $filename = basename($genFile);
                // $targetDir = dirname($genFile);
                $targetFile = $genFile;
            }
        }

        // dump to script file
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

        // dump script contents to stdout
        if ($toStdout) {
            file_put_contents('php://stdout', $content);
            return;
        }

        $output->write(['Target File:', $targetFile, '']);

        if (file_put_contents($targetFile, $content) > 10) {
            $output->success("O_O! Generate completion file '$filename' successful!");
        } else {
            $output->error("O^O! Generate completion file '$filename' failure!");
        }
    }
}
