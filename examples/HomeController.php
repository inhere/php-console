<?php

use inhere\console\Controller;
use inhere\console\utils\Download;
use inhere\console\utils\Interact;

/**
 * default command controller. there are some command usage examples
 */
class HomeController extends Controller
{
    const DESCRIPTION = 'default command controller. there are some command usage examples';

    /**
     * this is a command's description message
     * the second line text
     * @usage usage message
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

        $styles = $this->output->getStyle()->getStyleNames();
        $this->write('normal text output');

        $this->write('color text output');
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

        foreach (Interact::$defaultBlocks as $type) {
            $this->output->$type('message text');
        }

        return 0;
    }

    /**
     * output more format message text
     */
    public function fmtMsgCommand()
    {
        $this->output->title('title');
        $body = 'If screen size could not be detected, or the indentation is greater than the screen size, the text will not be wrapped.' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,';

        $this->output->section('section title', $body, [
            'pos' => 'l'
        ]);

        $commands = [
            'version' => 'Show application version information',
            'help' => 'Show application help information',
            'list' => 'List all group and independent commands',
        ];
        Interact::panel($commands, 'Internal Commands', '');
        Interact::aList($commands, 'Internal Commands');
    }

    /**
     * a example for use arguments on command
     * @usage home/useArg [arg1=val1 arg2=arg2] [options]
     * @example home/useArg status=2 name=john arg0 -s=test --page=23 -d -rf --debug --test=false
     *   home/useArg status=2 name=john name=tom name=jack arg0 -s=test --page=23 --id=23 --id=154 --id=456  -d -rf --debug --test=false
     */
    public function useArgCommand()
    {
        $this->write('input arguments:');
        var_dump($this->input->getArgs());

        $this->write('input options:');
        var_dump($this->input->getOpts());

        $this->write('the Input object:');
        var_dump($this->input);
    }

    /**
     * use Interact::confirm method
     *
     */
    public function confirmCommand()
    {
        $a = Interact::confirm('continue');

        $this->write('you answer is: ' . ($a ? 'yes' : 'no'));
    }

    /**
     * use <default>Interact::select</default> method
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
            'env' => 'test',
            'debug' => true,
        ];

        Interact::panel($info);
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
            \inhere\console\utils\Show::error('Please input you want to downloaded file url, use: url=[url]', 1);
        }

        $saveAs = $this->input->getArg('saveAs');
        $type = $this->input->getArg('type', 'text');

        if (!$saveAs) {
            $saveAs = __DIR__ . '/' . basename($url);
        }

        $goon = Interact::confirm("Now, will download $url to $saveAs, go on");

        if (!$goon) {
            \inhere\console\utils\Show::notice('Quit download, Bye!');

            return 0;
        }

        $d = Download::down($url, $saveAs, $type);

        var_dump($d);

        return 0;
    }
}
