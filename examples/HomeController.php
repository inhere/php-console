<?php

namespace inhere\console\examples;

use inhere\console\Controller;
use inhere\console\io\Input;
use inhere\console\utils\AnsiCode;
use inhere\console\utils\Download;
use inhere\console\utils\Helper;
use inhere\console\utils\Show;
use inhere\console\utils\Interact;

/**
 * default command controller. there are some command usage examples(1)
 *
 * Class HomeController
 * @package inhere\console\examples
 */
class HomeController extends Controller
{
    protected static $description = 'default command controller. there are some command usage examples(2)';

    /**
     * this is a command's description message
     * the second line text
     * @usage usage message
     * @arguments
     * arg1  argument description 1
     * arg2  argument description 2
     * @options
     * --long,-s option description 1
     * --opt    option description 2
     * @example example text one
     *  the second line example
     */
    public function indexCommand()
    {
        $this->write('hello, welcome!! this is ' . __METHOD__);
    }

    /**
     * a example for use color text output on command
     * @usage ./bin/app home/color
     */
    public function colorCommand()
    {
        if (!$this->output->supportColor()) {
            $this->write('Current terminal is not support output color text.');

            return 0;
        }

        $this->write('color text output:');
        $styles = $this->output->getStyle()->getStyleNames();

        foreach ($styles as $style) {
            $this->output->write("<$style>$style style text</$style>");
        }

        return 0;
    }

    /**
     * output block message text
     * @return int
     */
    public function blockMsgCommand()
    {
        $this->write('block message:');

        foreach (Interact::getBlockMethods() as $type) {
            $this->output->$type("$type style message text");
        }

        return 0;
    }

    /**
     * a progress bar example show
     *
     * @options
     * --type      the progress type, allow: bar,txt. default <cyan>txt</cyan>
     * --done-char the done show char. default <info>=</info>
     * --wait-char the waiting show char. default <info>-</info>
     * --sign-char the sign char show. default <info>></info>
     * @example
     * {script} home/progress
     * {script} home/progress --done-char '#' --wait-char ' '
     * @param Input $input
     * @return int
     */
    public function progressCommand($input)
    {
        $i = 0;
        $total = 120;
        if ($input->getOpt('type') === 'bar') {
            $bar = $this->output->progressBar($total, [
                'msg' => 'Msg Text',
                'doneChar' => $input->getOpt('done-char', '='), // ▓
                'waitChar' => $input->getOpt('wait-char', '-'), // ░
                'signChar' => $input->getOpt('sign-char', '>'),
            ]);
        } else {
            $bar = $this->output->progressTxt($total, 'Doing oo oo', 'Done');
        }

        $this->write('Progress:');

        while ($i <= $total) {
            $bar->send($i);
            usleep(50000);
            $i++;
        }

        return 0;
    }

    /**
     * output more format message text
     */
    public function fmtMsgCommand()
    {
        $this->output->title('title show');
        echo "\n";

        $body = 'If screen size could not be detected, or the indentation is greater than the screen size, the text will not be wrapped.' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,';

        $this->output->section('section show', $body, [
            'pos' => 'l'
        ]);

        $data = [
            'application version' => '1.2.0',
            'system version' => '5.2.3',
            'see help' => 'please use php bin/app -h',
            'a only value message text',
        ];
        Show::panel($data, 'panel show', [
            'borderChar' => '#'
        ]);

        echo "\n";
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

        $commands = [
            'version' => 'Show application version information',
            'help' => 'Show application help information',
            'list' => 'List all group and independent commands',
            'a only value message text'
        ];
        Show::aList($commands, 'a List show');

        Show::table([
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
        ], 'table show');
    }

    /**
     * a example for use arguments on command
     * @usage home/useArg [arg1=val1 arg2=arg2] [options]
     * @example
     * home/useArg status=2 name=john arg0 -s=test --page=23 -d -rf --debug --test=false
     * home/useArg status=2 name=john name=tom name=jack arg0 -s=test --page=23 --id=23 --id=154 --id=456  -d -rf --debug --test=false
     */
    public function useArgCommand()
    {
        $this->write('input arguments:');
        echo Helper::dumpVars($this->input->getArgs());

        $this->write('input options:');
        echo Helper::dumpVars($this->input->getOpts());

        // $this->write('the Input object:');
        // var_dump($this->input);
    }

    /**
     * command `defArgCommand` config
     */
    protected function defArgConfigure()
    {
        $this->createDefinition()
            ->setDescription('the command arg/opt config use defined configure, it like symfony console: argument define by position')
            ->addArgument('name', Input::ARG_REQUIRED, 'description for the argument [name]')
            ->addOption('yes', 'y', Input::OPT_BOOLEAN, 'description for the option [yes]')
            ->addOption('opt1', null, Input::OPT_REQUIRED, 'description for the option [opt1]');
    }

    /**
     * the command arg/opt config use defined configure, it like symfony console: argument define by position
     */
    public function defArgCommand()
    {
        $this->output->dumpVars($this->input->getArgs(), $this->input->getOpts(), $this->input->getBoolOpt('y'));
    }

    /**
     * use <red>Interact::confirm</red> method
     *
     */
    public function confirmCommand()
    {
        $a = Interact::confirm('continue');

        $this->write('you answer is: ' . ($a ? 'yes' : 'no'));
    }

    /**
     * example for use <magenta>Interact::select</magenta> method
     *
     */
    public function selectCommand()
    {
        $opts = ['john', 'simon', 'rose'];
        $a = Interact::select('you name is', $opts);

        $this->write('you answer is: ' . $opts[$a]);
    }

    /**
     * output current env info
     */
    public function envCommand()
    {
        $info = [
            'phpVersion' => PHP_VERSION,
            'env'   => 'test',
            'debug' => true,
        ];

        Interact::panel($info);

        echo Helper::printVars($_SERVER);
    }

    /**
     * download a file to local
     *
     * @usage {command} url=url saveTo=[saveAs] type=[bar|text]
     * @example {command} url=https://github.com/inhere/php-librarys/archive/v2.0.1.zip type=bar
     */
    public function downCommand()
    {
        $url = $this->input->getArg('url');

        if (!$url) {
            Show::error('Please input you want to downloaded file url, use: url=[url]', 1);
        }

        $saveAs = $this->input->getArg('saveAs');
        $type = $this->input->getArg('type', 'text');

        if (!$saveAs) {
            $saveAs = __DIR__ . '/' . basename($url);
        }

        $goon = Interact::confirm("Now, will download $url to $saveAs, go on");

        if (!$goon) {
            Show::notice('Quit download, Bye!');

            return 0;
        }

        $d = Download::down($url, $saveAs, $type);

        echo Helper::dumpVars($d);

        return 0;
    }

    /**
     * show cursor move on the screen
     */
    public function cursorCommand()
    {
        $this->write('hello, this in ' . __METHOD__);

        // $this->output->panel($_SERVER, 'Server information', '');

        $this->write('this is a message text.', false);

        sleep(1);
        AnsiCode::make()->cursor(AnsiCode::CURSOR_BACKWARD, 6);

        sleep(1);
        AnsiCode::make()->cursor(AnsiCode::CURSOR_FORWARD, 3);

        sleep(1);
        AnsiCode::make()->cursor(AnsiCode::CURSOR_BACKWARD, 2);

        sleep(2);

        AnsiCode::make()->screen(AnsiCode::CLEAR_LINE, 3);

        $this->write('after 2s scroll down 3 row.');

        sleep(2);

        AnsiCode::make()->screen(AnsiCode::SCROLL_DOWN, 3);

        $this->write('after 3s clear screen.');

        sleep(3);

        AnsiCode::make()->screen(AnsiCode::CLEAR);
    }
}
