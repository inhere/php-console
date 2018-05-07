<?php

namespace Inhere\Console\Examples\Controllers;

use Toolkit\Cli\Cli;
use Toolkit\Cli\Terminal;
use Inhere\Console\Components\Symbol\ArtFont;
use Toolkit\Cli\Color;
use Toolkit\Cli\Download;
use Toolkit\Cli\Highlighter;
use Inhere\Console\Controller;
use Inhere\Console\IO\Input;
use Inhere\Console\Components\Symbol\Char;
use Inhere\Console\Components\Symbol\Emoji;
use Inhere\Console\Utils\Helper;
use Inhere\Console\Utils\Interact;
use Inhere\Console\Utils\ProgressBar;
use Inhere\Console\Utils\Show;

/**
 * default command controller. there are some command usage examples(1)
 * Class HomeController
 * @package Inhere\Console\Examples\Controllers
 */
class HomeController extends Controller
{
    protected static $description = 'This is a demo command controller. there are some command usage examples(2)';

    /**
     * @return array
     */
    protected static function commandAliases(): array
    {
        return [
            // now, 'home:i' is equals to 'home:index'
            'i' => 'index',
            'prg' => 'progress',
            'pgb' => 'progressBar',
            'pwd' => 'password',
            'l' => 'list',
            'h' => 'helpPanel',
            'hl' => 'highlight',
            'hp' => 'helpPanel',
            'af' => 'artFont',
            'ml' => 'multiList',
            'ms' => 'multiSelect',
            'sl' => 'splitLine',
        ];
    }

    protected function init()
    {
        parent::init();

        $this->addAnnotationVar('internalFonts', implode(',', ArtFont::getInternalFonts()));
    }

    protected function disabledCommands(): array
    {
         return ['disabled'];
    }

    protected function afterExecute()
    {
        // $this->write('after command execute');
    }

    /**
     * this is a command's description message
     * the second line text
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
    public function testCommand()
    {
        $this->write('hello, welcome!! this is ' . __METHOD__);
    }

    /**
     * this is a disabled command. please see 'disabledCommands()'
     */
    public function disabledCommand()
    {
        $this->write('hello, welcome!! this is ' . __METHOD__);
    }

    /**
     * command `defArgCommand` config
     * @throws \LogicException
     */
    protected function defArgConfigure()
    {
        $this->createDefinition()
            ->setDescription('the command arg/opt config use defined configure, it like symfony console: argument define by position')
            ->addArgument('name', Input::ARG_REQUIRED, "description for the argument 'name'")
            ->addOption('yes', 'y', Input::OPT_BOOLEAN, "description for the option 'yes'")
            ->addOption('opt1', null, Input::OPT_REQUIRED, "description for the option 'opt1'");
    }

    /**
     * the command arg/opt config use defined configure, it like symfony console: argument define by position
     */
    public function defArgCommand()
    {
        $this->output->dump($this->input->getArgs(), $this->input->getOpts(), $this->input->getBoolOpt('y'));
    }

    /**
     * a command for test throw exception
     * @throws \RuntimeException
     */
    public function exCommand()
    {
        throw new \RuntimeException('oo, this is a runtime exception!');
    }

    /**
     * a command for test trigger error
     */
    public function errorCommand()
    {
        trigger_error('oo, this is a runtime error!', E_USER_ERROR);
    }

    /**
     * will run other command in the command.
     */
    public function subRunCommand()
    {
        $this->writeln('hello this is: ' . __METHOD__);

        $this->getApp()->subRun('test', $this->input, $this->output);
    }

    /**
     * dump current env information
     */
    public function dumpEnvCommand()
    {
        $this->output->aList($_SERVER, '$_SERVER data');
    }

    /**
     * a example for highlight code
     * @options
     *  --ln    Display with line number
     * @param Input $in
     */
    public function highlightCommand($in)
    {
        // $file = $this->app->getRootPath() . '/examples/routes.php';
        $file = $this->app->getRootPath() . '/src/Utils/Show.php';
        $src = file_get_contents($file);

        $code = Highlighter::create()->highlight($src, $in->getBoolOpt('ln'));

        $this->output->writeRaw($code);
    }

    /**
     * a example for use color text output by Style::class
     * @usage {fullCommand}
     * @return int
     */
    public function colorCommand(): int
    {
        if (!$this->output->supportColor()) {
            $this->write('Current terminal is not support output color text.');

            return 0;
        }

        $this->write('color style text output:');
        $styles = $this->output->getStyle()->getStyleNames();

        foreach ($styles as $style) {
            $this->output->write("<$style>$style style text</$style>");
        }

        return 0;
    }

    /**
     * check color support for current env.
     */
    public function colorCheckCommand()
    {
        // $char= '❤';
        // $ret = ProcessUtil::run('echo ❤');
        // var_dump($ret, trim($ret[1]), $char === trim($ret[1]));
        //
        // die;

        $this->output->aList([
            'basic color output?' => Cli::isSupportColor() ? '<info>Y</info>' : 'N',
            'ansi char output?' => Cli::isAnsiSupport() ? 'Y' : 'N',
            '256 color output?' => Cli::isSupport256Color() ? 'Y' : 'N',
            'font symbol output?' => Cli::isSupport256Color() ? 'Y' : 'N',
        ], 'color support check');
    }

    /**
     * a example for use color text output by LiteStyle::class
     */
    public function colorLiteCommand(): int
    {
        if (!$this->output->supportColor()) {
            $this->write('Current terminal is not support output color text.');

            return -2;
        }

        $this->output->startBuffer();

        foreach (Color::getStyles() as $style) {
            $this->output->write(Color::render("color text(style:$style)", $style));
        }

        $this->output->flush();

        return 0;
    }

    /**
     * output block message text
     * @return int
     */
    public function blockMsgCommand(): int
    {
        if (!$this->output->supportColor()) {
            $this->write('Current terminal is not support output color text.');

            return 0;
        }

        $this->write('block message:');

        foreach (Show::getBlockMethods() as $type) {
            $this->output->$type("$type style message text");
        }

        return 0;
    }

    /**
     * output art font text
     * @options
     *  --font    Set the art font name(allow: {internalFonts}).
     *  --italic  Set the art font type is italic.
     *  --style   Set the art font style.
     * @return int
     */
    public function artFontCommand(): int
    {
        $name = $this->input->getLongOpt('font', '404');

        if (!ArtFont::isInternalFont($name)) {
            return $this->output->liteError("Your input font name: $name, is not exists. Please use '-h' see allowed.");
        }

        ArtFont::create()->show($name, ArtFont::INTERNAL_GROUP,[
            'type' => $this->input->getBoolOpt('italic') ? 'italic' : '',
            'style' => $this->input->getOpt('style'),
        ]);

        return 0;
    }

    /**
     * display some special chars
     * @return int
     * @throws \ReflectionException
     */
    public function charCommand(): int
    {
        $this->output->aList(Char::getConstants(), 'some special char', [
            'ucFirst' => false,
        ]);

        return 0;
    }

    /**
     * display some special emoji chars
     * @return int
     * @throws \ReflectionException
     */
    public function emojiCommand(): int
    {
        $this->output->aList(Emoji::getConstants(), 'some emoji char', [
            'ucFirst' => false,
        ]);

        return 0;
    }

    /**
     * dynamic notice message show: counterTxt. It is like progress txt, but no max value.
     * @example
     *  {script} {command}
     * @return int
     */
    public function counterCommand(): int
    {
        $total = 120;
        $ctt = Show::counterTxt('handling ...', 'handled.');
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
     * dynamic notice message show: spinner
     */
    public function spinnerCommand()
    {
        $total = 5000;

        while ($total--) {
            Show::spinner();
            usleep(100);
        }

        Show::spinner('Done', true);
    }

    /**
     * dynamic notice message show: pending
     */
    public function pendingCommand()
    {
        $total = 8000;

        while ($total--) {
            Show::pending();
            usleep(200);
        }

        Show::pending('Done', true);
    }

    /**
     * dynamic notice message show: pointing
     */
    public function pointingCommand()
    {
        $total = 100;

        while ($total--) {
            Show::pointing();
            usleep(10000);
        }

        Show::pointing('Done', true);
    }

    /**
     * a progress bar example show, by Show::progressBar()
     * @options
     *  --type      the progress type, allow: bar,txt. <cyan>txt</cyan>
     *  --done-char the done show char. <info>=</info>
     *  --wait-char the waiting show char. <info>-</info>
     *  --sign-char the sign char show. <info>></info>
     * @example
     *  {script} {command}
     *  {script} {command} --done-char '#' --wait-char ' '
     * @param Input $input
     * @return int
     */
    public function progressCommand($input): int
    {
        $i = 0;
        $total = 120;
        if ($input->getOpt('type') === 'bar') {
            $bar = $this->output->progressBar($total, [
                'msg' => 'Msg Text',
                'doneMsg' => 'Done Msg Text',
                'doneChar' => $input->getOpt('done-char', '='), // ▓
                'waitChar' => $input->getOpt('wait-char', '-'), // ░
                'signChar' => $input->getOpt('sign-char', '>'),
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
     * @throws \LogicException
     */
    public function progressBarCommand()
    {
        $i = 0;
        $total = 120;
        $bar = new ProgressBar();
        $bar->start(120);

        while ($i <= $total) {
            $bar->advance();
            usleep(50000);
            $i++;
        }

        $bar->finish();
    }

    /**
     * output format message: title
     */
    public function titleCommand()
    {
        $this->output->title('title show');

        return 0;
    }

    /**
     * output format message: splitLine
     * @options
     *  -w, --width WIDTH   The split line width. default is current screen width.
     */
    public function splitLineCommand(): int
    {
        $this->output->splitLine('', '=', $this->input->getSameOpt(['w', 'width'], 0));
        $this->output->splitLine('split Line', '-', $this->input->getSameOpt(['w', 'width'], 0));

        $this->output->splitLine('split 中文 Line', '-', $this->input->getSameOpt(['w', 'width'], 0));


        return 0;
    }

    /**
     * output format message: section
     */
    public function sectionCommand(): int
    {
        $body = 'If screen size could not be detected, or the indentation is greater than the screen size, the text will not be wrapped.' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,';

        $this->output->section('section show', $body, [
            'pos' => 'l'
        ]);

        return 0;
    }

    /**
     * output format message: panel
     */
    public function panelCommand()
    {
        $data = [
            'application version' => '1.2.0',
            'system version' => '5.2.3',
            'key' => 'value ...',
            'a only value message text',
        ];

        Show::panel($data, 'panel show', [
            'borderChar' => '*'
        ]);

        Show::panel($data, 'panel show', [
            'borderChar' => '='
        ]);
    }

    /**
     * output format message: helpPanel
     */
    public function helpPanelCommand()
    {
        Show::helpPanel([
            Show::HELP_DES => 'a help panel description text. (help panel show)',
            Show::HELP_USAGE => 'a usage text',
            Show::HELP_ARGUMENTS => [
                'arg1' => 'arg1 description',
                'arg2' => 'arg2 description',
            ],
            Show::HELP_OPTIONS => [
                '--opt1' => 'a long option',
                '-s' => 'a short option',
                '-d' => 'Run the server on daemon.(default: <comment>false</comment>)',
                '-h, --help' => 'Display this help message'
            ],
        ], false);
    }

    /**
     * output format message: list
     */
    public function listCommand()
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
            'help' => 'Show application help information',
            'list' => 'List all group and independent commands',
            'a only value message text'
        ];

        Show::aList($commands, 'a List show(Has key)');
    }

    /**
     * output format message: multiList
     */
    public function multiListCommand()
    {
        Show::multiList([
            'list0' => [
                'value in the list 0',
                'key' => 'value in the list 0',
                'key1' => 'value1 in the list 0',
                'key2' => 'value2 in the list 0',
            ],
            'list1' => [
                'key' => 'value in the list 1',
                'key1' => 'value1 in the list 1',
                'key2' => 'value2 in the list 1',
                'value in the list 1',
            ],
            'list2' => [
                'key' => 'value in the list 2',
                'value in the list 2',
                'key1' => 'value1 in the list 2',
                'key2' => 'value2 in the list 2',
            ],
        ]);
    }

    /**
     * output format message: table
     */
    public function tableCommand()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'john',
                'status' => 2,
                'email' => 'john@email.com',
            ],
            [
                'id' => 2,
                'name' => 'tom',
                'status' => 0,
                'email' => 'tom@email.com',
            ],
            [
                'id' => 3,
                'name' => 'jack',
                'status' => 1,
                'email' => 'jack-test@email.com',
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
     * output format message: tree
     */
    public function treeCommand()
    {
        Show::tree([
            123,
            true,
            false,
            null,
            'one-level',
            'one-level1',

            [
                'two-level',
                'two-level1',
                'two-level2',
                [
                    'three-level',
                    'three-level1',
                    'three-level2',
                ],
            ],

            'one-level99',
        ]);
    }

    /**
     * output format message: padding
     */
    public function paddingCommand()
    {
        $data = [
            'Eggs' => '$1.99',
            'Oatmeal' => '$4.99',
            'Bacon' => '$2.99',
        ];

        Show::padding($data, 'padding data show');
    }

    /**
     * output format message: dump
     */
    public function jsonCommand()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'john',
                'status' => 2,
                'email' => 'john@email.com',
            ],
            [
                'id' => 2,
                'name' => 'tom',
                'status' => 0,
                'email' => 'tom@email.com',
            ],
            [
                'id' => 3,
                'name' => 'jack',
                'status' => 1,
                'email' => 'jack-test@email.com',
            ],
        ];

        $this->output->write('use dump:');
        $this->output->dump($data);

        $this->output->write('use print:');
        $this->output->prints($data);

        $this->output->write('use json:');
        $this->output->json($data);
    }

    /**
     * a example for use arguments on command
     * @usage home:useArg [arg1=val1 arg2=arg2] [options]
     * @example
     *  home:useArg status=2 name=john arg0 -s=test --page=23 -d -rf --debug --test=false -a v1 --ab -c -g --cd val -h '' -i stat=online
     *  home:useArg status=2 name=john name=tom name=jack arg0 -s=test --page=23 --id=23 --id=154 --id=456  -d -rf --debug --test=false
     */
    public function useArgCommand()
    {
        $this->write('input arguments:');
        echo Helper::dumpVars($this->input->getArgs());

        $this->write('input options:');
        echo Helper::dumpVars($this->input->getOpts());

        $this->write('raw argv:');
        $this->output->dump($this->input->getTokens());

        $this->write('raw argv(string):');
        $this->output->dump($this->input->getFullScript());
    }

    /**
     * This is a demo for use <magenta>Interact::confirm</magenta> method
     */
    public function confirmCommand()
    {
        // can also: $this->confirm();
        $a = Interact::confirm('continue');

        $this->write('Your answer is: ' . ($a ? 'yes' : 'no'));
    }

    /**
     * This is a demo for use <magenta>Interact::select()</magenta> method
     */
    public function selectCommand()
    {
        $opts = ['john', 'simon', 'rose'];
        // can also: $this->select();
        $a = Interact::select('you name is', $opts);

        $this->write('Your answer is: ' . $opts[$a]);
    }

    /**
     * This is a demo for use <magenta>Interact::multiSelect()</magenta> method
     */
    public function multiSelectCommand()
    {
        $opts = ['john', 'simon', 'rose', 'tom'];

        // can also: $a = Interact::multiSelect('Your friends are', $opts);
        $a = $this->multiSelect('Your friends are', $opts);

        $this->write('Your answer is: ' . json_encode($a));
    }

    /**
     * This is a demo for use <magenta>Interact::ask()</magenta> method
     */
    public function askCommand()
    {
        $a = Interact::ask('you name is: ', null, function ($val, &$err) {
            if (!preg_match('/^\w{2,}$/', $val)) {
                $err = 'Your input must match /^\w{2,}$/';

                return false;
            }

            return true;
        });

        $this->write('Your answer is: ' . $a);
    }

    /**
     * This is a demo for use <magenta>Interact::limitedAsk()</magenta> method
     * @options
     *  --nv   Not use validator.
     *  --limit  limit times.(default: 3)
     */
    public function limitedAskCommand()
    {
        $times = (int)$this->input->getOpt('limit', 3);

        if ($this->input->getBoolOpt('nv')) {
            $a = Interact::limitedAsk('you name is: ', null, null, $times);
        } else {
            $a = Interact::limitedAsk('you name is: ', null, function ($val) {
                if (!preg_match('/^\w{2,}$/', $val)) {
                    Show::error('Your input must match /^\w{2,}$/');

                    return false;
                }

                return true;
            }, $times);
        }

        $this->write('Your answer is: ' . $a);
    }

    /**
     * This is a demo for input password. use: <magenta>Interact::askPassword()</magenta>
     * @usage {fullCommand}
     */
    public function passwordCommand()
    {
        $pwd = $this->askPassword();

        $this->write('Your input is: ' . $pwd);
    }

    /**
     * output current env info
     */
    public function envCommand()
    {
        $info = [
            'phpVersion' => PHP_VERSION,
            'env' => 'test',
            'debug' => true,
        ];

        Interact::panel($info);

        echo Helper::printVars($_SERVER);
    }

    /**
     * This is a demo for download a file to local
     * @usage {command} url=url saveTo=[saveAs] type=[bar|text]
     * @example {command} url=https://github.com/inhere/php-console/archive/master.zip type=bar
     */
    public function downCommand()
    {
        $url = $this->input->getArg('url');

        if (!$url) {
            $this->output->liteError('Please input you want to downloaded file url, use: url=[url]', 1);
        }

        $saveAs = $this->input->getArg('saveAs');
        $type = $this->input->getArg('type', 'text');

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

    /**
     * This is a demo for show cursor move on the Terminal screen
     */
    public function cursorCommand()
    {
        $this->write('hello, this in ' . __METHOD__);
        $this->write('this is a message text.', false);

        sleep(1);
        Terminal::make()->cursor(Terminal::CURSOR_BACKWARD, 6);

        sleep(1);
        Terminal::make()->cursor(Terminal::CURSOR_FORWARD, 3);

        sleep(1);
        Terminal::make()->cursor(Terminal::CURSOR_BACKWARD, 2);

        sleep(2);

        Terminal::make()->screen(Terminal::CLEAR_LINE, 3);

        $this->write('after 2s scroll down 3 row.');

        sleep(2);

        Terminal::make()->screen(Terminal::SCROLL_DOWN, 3);

        $this->write('after 3s clear screen.');

        sleep(3);

        Terminal::make()->screen(Terminal::CLEAR);
    }
}
