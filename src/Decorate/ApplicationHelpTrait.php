<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Decorate;

use Inhere\Console\Console;
use Inhere\Console\ConsoleEvent;
use Inhere\Console\Contract\CommandInterface;
use Inhere\Console\Handler\AbstractHandler;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\Show;
use Toolkit\Cli\Color\ColorTag;
use Toolkit\Cli\Style;
use Toolkit\PFlag\FlagUtil;
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
use function strtr;
use const PHP_EOL;
use const PHP_OS;
use const PHP_VERSION;
use const STR_PAD_LEFT;

/**
 * Trait ApplicationHelpTrait
 *
 * @package Inhere\Console\Decorate
 */
trait ApplicationHelpTrait
{
    /**
     * @var string|array
     */
    protected string|array $moreHelpInfo = '';

    /***************************************************************************
     * Show information for the application
     ***************************************************************************/

    /**
     * Display the application version information
     */
    public function showVersionInfo(): void
    {
        $this->fire(ConsoleEvent::BEFORE_RENDER_APP_VERSION, $this);

        Show::aList($this->buildVersionInfo(), '', [
            'leftChar'  => '',
            'sepChar'   => ' :  ',
            'keyPadPos' => STR_PAD_LEFT,
        ]);
    }

    /**
     * @return string[]
     */
    protected function buildVersionInfo(): array
    {
        $logo = '';
        $date = date('Y.m.d');
        $name = $this->getParam('name', 'Console Application');

        $osName  = PHP_OS;
        $phpVer  = PHP_VERSION;
        $version = $this->getParam('version', 'Unknown');

        $updateAt  = $this->getParam('updateAt', 'Unknown');
        $publishAt = $this->getParam('publishAt', 'Unknown');

        if ($logoTxt = $this->getLogoText()) {
            $logo = ColorTag::wrap($logoTxt, $this->getLogoStyle());
        }

        $info = [
            "$logo\n  <info>$name</info>, Version <comment>$version</comment>\n",
            'System Info'      => "PHP version <info>$phpVer</info>, on <info>$osName</info> system",
            'Application Info' => "Update at <info>$updateAt</info>, publish at <info>$publishAt</info>(current $date)",
        ];

        if ($hUrl = $this->getParam('homepage')) {
            $info['Homepage URL'] = $hUrl;
        }

        return $info;
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
            $this->dispatch($command, ['-h']);
            return;
        }

        $this->debugf('display application help by input -h, --help');
        $this->fire(ConsoleEvent::BEFORE_RENDER_APP_HELP, $this);

        // built in options
        // $globalOptions = self::$globalOptions;
        $globalOptions = $this->flags->getOptsHelpLines();
        // append generate options:
        // php examples/app --auto-completion --shell-env zsh --gen-file
        // php examples/app --auto-completion --shell-env zsh --gen-file stdout
        if ($this->isDebug()) {
            $globalOptions['--auto-completion'] = 'Open generate auto completion script';
            $globalOptions['--shell-env']       = 'The shell env name for generate auto completion script';
            $globalOptions['--gen-file']        = 'The output file for generate auto completion script';
        }

        $binName  = $in->getScriptName();
        $helpInfo = [
            'Usage'   => "$binName <info>{command}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
            'Options' => FlagUtil::alignOptions($globalOptions),
            'Example' => [
                '- run a command/subcommand:',
                "$binName test                     run a independent command",
                "$binName home index               run a subcommand of the group",
                '',
                '- display help for command:',
                "$binName help COMMAND             see a command help information",
                "$binName home index -h            see a subcommand help of the group",
            ],
            'Help'    => [
                'Generate shell auto completion scripts:',
                "  <info>$binName --auto-completion --shell-env [zsh|bash] [--gen-file stdout] [--tpl-file filepath]</info>",
                ' eg:',
                "  $binName --auto-completion --shell-env bash --gen-file stdout",
                "  $binName --auto-completion --shell-env zsh --gen-file stdout",
                "  $binName --auto-completion --shell-env bash --gen-file myapp.sh",
            ],
        ];

        // custom more help info
        if ($this->moreHelpInfo) {
            $helpInfo['More Information'] = $this->moreHelpInfo;
        }

        /** @var Output $out */
        $out = $this->output;
        $out->helpPanel($helpInfo);
    }

    /**
     * Display the application group/command list information
     */
    public function showCommandList(): void
    {
        $flags = $this->flags;
        // has option: --auto-completion
        $autoComp = $flags->getOpt('auto-completion');
        // has option: --shell-env
        $shellEnv = $flags->getOpt('shell-env');
        // input is an path: /bin/bash
        if ($shellEnv && str_contains($shellEnv, '/')) {
            $shellEnv = basename($shellEnv);
        }

        // php bin/app list --only-name
        if ($autoComp && $shellEnv === 'bash') {
            $this->dumpAutoCompletion('bash', []);
            return;
        }

        $this->logf(Console::VERB_DEBUG, 'Display the application commands list');

        $hasGroup = $hasCommand = false;
        $groupArr = $commandArr = [];

        // all console groups/controllers
        $router = $this->getRouter();
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
        // if (!$autoComp && $hasCommand && $hasGroup) {
            // $groupArr[]   = PHP_EOL . '- <bold>Group Commands</bold>';
            // $commandArr[] = PHP_EOL . '- <bold>Alone Commands</bold>';
        // }

        $placeholder = 'No description of the command';
        foreach ($groups as $name => $info) {
            $controller = $info['handler'];
            /** @var AbstractHandler $controller */
            $desc    = $controller::getDesc() ?: $placeholder;
            $config  = $info['config'];
            $aliases = $config['aliases'];
            $extra   = $aliases ? ColorTag::wrap(' (alias: ' . implode(',', $aliases) . ')', 'info') : '';

            // collect
            $groupArr[$name] = $desc . $extra;
        }

        if (!$hasGroup && $this->isDebug()) {
            $groupArr[] = '... Not register any group command(controller)';
        }

        foreach ($commands as $name => $info) {
            $desc   = $placeholder;
            $config = $info['config'];
            $command = $info['handler'];

            /** @var AbstractHandler $command */
            if (is_subclass_of($command, CommandInterface::class)) {
                $desc = $command::getDesc() ?: $placeholder;
            } elseif ($msg = $config['desc'] ?? '') {
                $desc = $msg;
            } elseif (is_string($command)) {
                $desc = 'A handler : ' . $command;
            } elseif (is_object($command)) {
                $desc = 'A handler by ' . get_class($command);
            }

            $aliases = $config['aliases'];
            $extra   = $aliases ? ColorTag::wrap(' (alias: ' . implode(',', $aliases) . ')', 'info') : '';

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

        if ($appDesc = $this->getParam('desc', '')) {
            $appVer = $this->getParam('version', '');
            Console::writeln(sprintf('%s%s' . PHP_EOL, $appDesc, $appVer ? " (Version: <info>$appVer</info>)" : ''));
        }

        $scriptName = $this->getScriptName();

        // built in options
        // $globOpts = self::$globalOptions;
        $globOpts = $this->flags->getOptsHelpLines();

        Show::mList([
            'Usage:'              => "$scriptName <info>{COMMAND}</info> [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]",
            'Options:'            => FlagUtil::alignOptions($globOpts),
            'Internal Commands:'  => $internalCommands,
            'Available Commands:' => array_merge($groupArr, $commandArr),
        ], [
            'sepChar' => '  ',
        ]);

        unset($groupArr, $commandArr, $internalCommands);
        Console::write("More command information, please use: <cyan>$scriptName COMMAND -h</cyan>");
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
        $router = $this->getRouter();

        // info
        $glue    = ' ';
        $genFile = $this->flags->getOpt('gen-file', 'none');
        $tplDir  = dirname(__DIR__, 2) . '/resource/templates';

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
        $userTplFile = $this->flags->getOpt('tpl-file');
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
