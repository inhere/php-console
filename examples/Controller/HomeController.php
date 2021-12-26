<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Examples\Controller;

use Inhere\Console\Component\Symbol\ArtFont;
use Inhere\Console\Controller;
use Inhere\Console\Examples\Controller\Attach\DemoSubCommand;
use Inhere\Console\Handler\CommandWrapper;
use Inhere\Console\Util\Interact;
use Inhere\Console\Util\ProgressBar;
use Inhere\Console\Util\Show;
use JetBrains\PhpStorm\NoReturn;
use LogicException;
use RuntimeException;
use Toolkit\Cli\Cli;
use Toolkit\Cli\Util\Download;
use Toolkit\PFlag\FlagsParser;
use Toolkit\Stdlib\Php;
use function sleep;
use function trigger_error;
use function vdump;

/**
 * default command controller. there are some command usage examples(1)
 * Class HomeController
 *
 * @package Inhere\Console\Examples\Controller
 */
class HomeController extends Controller
{
    protected static string $name = 'home';

    protected static string $desc = 'This is a demo command controller. there are some command usage examples(2)';

    public static function aliases(): array
    {
        return ['h'];
    }

    /**
     * @return array
     */
    protected static function commandAliases(): array
    {
        return [
            // now, 'home:i' is equals to 'home:index'
            'i'      => 'index',
            'prg'    => 'progress',
            'pgb'    => 'progressBar',
            'l'      => 'list',
            'af'     => 'artFont',
            'ml'     => 'multiList',
            'sl'     => 'splitLine',
            'dt'     => 'dynamicText',
            'defArg' => ['da', 'defarg'],
        ];
    }

    protected function init(): void
    {
        parent::init();

        $this->addCommentsVar('internalFonts', implode(',', ArtFont::getInternalFonts()));

        $this->setCommandMeta('defArg', [
            'desc' => 'the command args and opts config use defined configure, it like symfony console, please see defArgConfigure()',
        ]);
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            '-c, --common' => 'This is a common option for all sub-commands',
        ];
    }

    protected function subCommands(): array
    {
        return [
            DemoSubCommand::class,
            CommandWrapper::wrap(static function ($fs) {
                vdump(__METHOD__, $fs->getOpts());
            }, [
                'name' => 'sub2',
                'desc' => 'an sub command in group controller',
                'options' => [
                    'int1'     => 'int;an int option1',
                    'i2, int2' => 'int;an int option2',
                ]
            ]),
        ];
    }

    protected function disabledCommands(): array
    {
        return ['disabled'];
    }

    protected function afterExecute(): void
    {
        // $this->write('after command execute');
    }

    /**
     * this is a command's description message
     * the second line text
     *
     * @usage {command} [arg ...] [--opt ...]
     * @arguments
     *  arg1        argument description 1
     *              the second line
     *  a2,arg2     argument description 2
     *              the second line
     * @options
     *  -s, --long  option description 1
     *  --opt       option description 2
     * @example example text one
     *  the second line example
     */
    public function testCommand(): void
    {
        $this->write('hello, welcome!! this is ' . __METHOD__);
    }

    /**
     * this is a disabled command. please see 'disabledCommands()'
     */
    public function disabledCommand(): void
    {
        $this->write('hello, welcome!! this is ' . __METHOD__);
    }

    /**
     * command `defArgCommand` config
     *
     * @throws LogicException
     */
    protected function defArgConfigure(): void
    {
        $fs = $this->curActionFlags();
        $fs->addOptByRule('yes,y', "bool;description for the option 'yes'");
        $fs->addOptByRule('opt1', "bool;description for the option 'opt1'");

        $fs->addArgByRule('name', "string;description for the argument 'name';true");
    }

    // desc set at $this->commandMetas.
    public function defArgCommand(FlagsParser $fs): void
    {
        $this->output->dump(
            $fs->getArgs(),
            $fs->getOpts(),
            $fs->getOpt('yes')
        );
    }

    /**
     * a command for test throw exception
     *
     * @throws RuntimeException
     */
    public function exCommand(): void
    {
        throw new RuntimeException('oo, this is a runtime exception!');
    }

    /**
     * a command for test trigger error
     */
    #[NoReturn]
    public function errorCommand(): void
    {
        trigger_error('oo, this is a runtime error!', E_USER_ERROR);
    }

    /**
     * will run other command in the command.
     */
    public function subRunCommand(): void
    {
        $this->writeln('hello this is: ' . __METHOD__);

        $this->getApp()->subRun('test', $this->input, $this->output);
    }

    /**
     * dump current env information
     */
    public function dumpEnvCommand(): void
    {
        $this->output->aList($_SERVER, '$_SERVER data');
    }

    /**
     * check color support for current env.
     */
    public function colorCheckCommand(): void
    {
        // $char= '❤';
        // $ret = ProcessUtil::run('echo ❤');
        // var_dump($ret, trim($ret[1]), $char === trim($ret[1]));
        //
        // die;

        $this->output->aList([
            'basic color output?' => Cli::isSupportColor() ? '<info>Y</info>' : 'N',
            'ansi char output?'   => Cli::isAnsiSupport() ? 'Y' : 'N',
            '256 color output?'   => Cli::isSupport256Color() ? 'Y' : 'N',
            'font symbol output?' => Cli::isSupport256Color() ? 'Y' : 'N',
        ], 'color support check');
    }

    /**
     * output art font text
     *
     * @options
     *  --font      Set the art font name(allow: {internalFonts}).
     *  --italic    bool;Set the art font type is italic.
     *  --style     Set the art font style.
     *
     * @param FlagsParser $fs
     *
     * @return int
     */
    public function artFontCommand(FlagsParser $fs): int
    {
        $name = $fs->getOpt('font', '404');

        if (!ArtFont::isInternalFont($name)) {
            return $this->output->liteError("Your input font name: $name, is not exists. Please use '-h' see allowed.");
        }

        ArtFont::create()->show($name, ArtFont::INTERNAL_GROUP, [
            'type'  => $fs->getOpt('italic') ? 'italic' : '',
            'style' => $fs->getOpt('style'),
        ]);

        return 0;
    }

    /**
     * dynamic notice message show: counterTxt. It is like progress txt, but no max value.
     *
     * @return int
     * @example
     *  {script} {command}
     */
    public function counterCommand(): int
    {
        $total = 120;
        $ctt   = Show::counterTxt('handling ...', 'handled.');
        $this->write('Counter:');

        while ($total - 1) {
            $ctt->send(1);
            usleep(30000);
            $total--;
        }

        // end of the counter.
        $ctt->send(-1);

        // $this->output->aList($data, 'runtime profile');

        return 0;
    }

    /**
     * dynamic spinner message, by Show::spinner()
     */
    public function spinnerCommand(): void
    {
        $total = 5000;

        while ($total--) {
            Show::spinner('Data handling ');
            usleep(100);
        }

        Show::spinner('Done', true);
    }

    /**
     * dynamic notice message show: pending
     */
    public function pendingCommand(): void
    {
        $total = 800;

        while ($total--) {
            Show::pending();
            usleep(20000);
        }

        Show::pending('Done', true);
    }

    /**
     * dynamic notice message show: pointing
     */
    public function pointingCommand(): void
    {
        $total = 100;

        while ($total--) {
            Show::pointing();
            usleep(10000);
        }

        Show::pointing('Done', true);
    }

    /**
     * dynamic text message example, by Show::dynamicText
     */
    public function dynamicTextCommand(): void
    {
        $dt = Show::dynamicText('Complete', 'Download file: xyz.zip ... ');
        $dt->send('Start');

        foreach (['Request', 'Downloading', 'Save'] as $txt) {
            sleep(2);
            $dt->send($txt);
        }

        sleep(2);
        $dt->send(false);
    }

    /**
     * a progress bar example show, by Show::progressBar()
     *
     * @options
     *  --type          the progress type, allow: bar,txt. <cyan>txt</cyan>
     *  --done-char     the done show char. <info>=</info>
     *  --wait-char     the waiting show char. <info>-</info>
     *  --sign-char     the sign char show. <info>></info>
     *
     * @param FlagsParser $fs
     *
     * @return int
     * @example
     *  {script} {command}
     *  {script} {command} --done-char '#' --wait-char ' '
     */
    public function progressCommand(FlagsParser $fs): int
    {
        $i     = 0;
        $total = 120;
        if ($fs->getOpt('type') === 'bar') {
            $bar = $this->output->progressBar($total, [
                'msg'      => 'Msg Text',
                'doneMsg'  => 'Done Msg Text',
                'doneChar' => $fs->getOpt('done-char', '='), // ▓
                'waitChar' => $fs->getOpt('wait-char', '-'), // ░
                'signChar' => $fs->getOpt('sign-char', '>'),
            ]);
        } else {
            $bar = $this->output->progressTxt($total, 'Doing go g...', 'Done');
        }

        $this->write('Progress:');

        while ($i <= $total) {
            $bar->send(1);
            usleep(50000);
            $i++;
        }

        return 0;
    }

    /**
     * a progress bar example show, by class ProgressBar
     *
     * @throws LogicException
     */
    public function progressBarCommand(): void
    {
        $i     = 0;
        $total = 120;
        $bar   = new ProgressBar();
        $bar->start(120);

        while ($i <= $total) {
            $bar->advance();
            usleep(50000);
            $i++;
        }

        $bar->finish();
    }

    /**
     * output format message: list
     */
    public function listCommand(): void
    {
        $list = [
            'The is a list line 0',
            'The is a list line 1',
            'The is a list line 2',
            'The is a list line 3',
        ];

        Show::aList($list, 'a List show(No key)');

        $commands = [
            'version' => 'Show application version information',
            'help'    => 'Show application help information',
            'list'    => 'List all group and independent commands',
            'a only value message text'
        ];

        Show::aList($commands, 'a List show(Has key)');
    }

    /**
     * output format message: multiList
     */
    public function multiListCommand(): void
    {
        Show::multiList([
            'list0' => [
                'value in the list 0',
                'key'  => 'value in the list 0',
                'key1' => 'value1 in the list 0',
                'key2' => 'value2 in the list 0',
            ],
            'list1' => [
                'key'  => 'value in the list 1',
                'key1' => 'value1 in the list 1',
                'key2' => 'value2 in the list 1',
                'value in the list 1',
            ],
            'list2' => [
                'key'  => 'value in the list 2',
                'value in the list 2',
                'key1' => 'value1 in the list 2',
                'key2' => 'value2 in the list 2',
            ],
        ]);
    }

    /**
     * output format message: table
     */
    public function tableCommand(): void
    {
        $data = [
            [
                'id'     => 1,
                'name'   => 'john',
                'status' => 2,
                'email'  => 'john@email.com',
            ],
            [
                'id'     => 2,
                'name'   => 'tom',
                'status' => 0,
                'email'  => 'tom@email.com',
            ],
            [
                'id'     => 3,
                'name'   => 'jack',
                'status' => 1,
                'email'  => 'jack-test@email.com',
            ],
        ];
        Show::table($data, 'table show');

        Show::table($data, 'No border table show', [
            'showBorder' => 0
        ]);

        Show::table($data, 'change style table show', [
            'bodyStyle' => 'info'
        ]);

        $data1 = [
            [
                'Walter White',
                'Father',
                'Teacher',
            ],
            [
                'Skyler White',
                'Mother',
                'Accountant',
            ],
            [
                'Walter White Jr.',
                'Son',
                'Student',
            ],
        ];

        Show::table($data1, 'no head table show');
    }

    /**
     * output format message: padding
     */
    public function paddingCommand(): void
    {
        $data = [
            'Eggs'    => '$1.99',
            'Oatmeal' => '$4.99',
            'Bacon'   => '$2.99',
        ];

        Show::padding($data, 'padding data show');
    }

    /**
     * a example for use arguments on command
     *
     * @usage home useArg [arg1=val1 arg2=arg2] [options]
     *
     * @example
     *  home useArg status=2 name=john arg0 -s=test --page=23 -d -rf --debug --test=false -a v1 --ab -c -g --cd val -h '' -i stat=online
     *  home useArg status=2 name=john name=tom name=jack arg0 -s=test --page=23 --id=23 --id=154 --id=456  -d -rf --debug --test=false
     */
    public function useArgCommand(): void
    {
        $this->write('input flags:');
        $this->output->dump($this->input->getFlags());

        $this->write('raw argv:');
        $this->output->dump($this->input->getTokens());

        $this->write('raw argv(string):');
        $this->output->dump($this->input->getFullScript());
    }

    /**
     * output current env info
     */
    public function envCommand(): void
    {
        $info = [
            'phpVersion' => PHP_VERSION,
            'env'        => 'test',
            'debug'      => true,
        ];

        Show::panel($info);

        echo Php::printVars($_SERVER);
    }

    /**
     * This is a demo for download a file to local
     *
     * @usage {command} url=url saveTo=[saveAs] --type=[bar|text]
     *
     * @options
     * --type       The progress bar type. allow: bar, text(default)
     *
     * @arguments
     * url      string;The remote file url;required
     * saveAs   The local file path for save download file.
     *
     * @example {command} url=https://github.com/inhere/php-console/archive/master.zip type=bar
     */
    public function downCommand(FlagsParser $fs): int
    {
        $url = $fs->getArg('url');
        if (!$url) {
            $this->output->liteError('Please input the downloaded file url');
            return 1;
        }

        $saveAs = $fs->getArg('saveAs');
        $type   = $fs->getOpt('type', 'text');

        if (!$saveAs) {
            $saveAs = __DIR__ . '/' . basename($url);
        }

        $goon = Interact::confirm("Now, will download $url \nto dir $saveAs, go on");

        if (!$goon) {
            Show::notice('Quit download, Bye!');
            return 0;
        }

        Download::file($url, $saveAs, $type);
        // $d = Download::down($url, $saveAs, $type);
        // echo Helper::dumpVars($d);

        return 0;
    }
}
