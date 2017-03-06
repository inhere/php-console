<?php

namespace inhere\console\examples;

use inhere\console\Controller;
use inhere\console\utils\Interact;

/**
 * default command controller
 */
class HomeController extends Controller
{
    /**
     * this is a command's description message
     * the second line text
     * @usage usage message
     * @example example text
     */
    public function indexCommand()
    {
        $this->write("hello, welcome!! this is " . __METHOD__);
    }

    /**
     * a example for use color text output on command
     * @usage ./bin/app home/outColor
     */
    public function outColorCommand()
    {
        if ( !$this->output->supportColor() ) {
            $this->write('Current terminal is not support output color text.');

            return 0;
        }

        $styles = $this->output->getColor()->getStyleNames();
        $this->write('normal text output');

        foreach ($styles as $style) {
            $this->output->write("<$style>$style style text</$style>");
        }

        $this->output->block('message text');

        return 0;
    }

    /**
     * a example for use arguments on command
     * @usage home/useArgs [arg1=val1 arg2=arg2]
     * @example ./bin/app home/useArgs name=test status=2
     */
    public function useArgsCommand()
    {
        var_dump($this->input->get());
    }

    public function testCommand()
    {
        $this->output->write('test <info>info</info> <success>info</success>');

        print_r($_SERVER);
    }

    /**
     * output current env info
     */
    public function envCommand()
    {
        $info = [
            'phpVersion' => PHP_VERSION,
            'env'        => 'test',
            'debug'      => true,
        ];

        Interact::panel($info);
    }
}
