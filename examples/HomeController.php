<?php

use inhere\console\Controller;
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
     * @usage ./bin/app home/outColor
     */
    public function outColorCommand()
    {
        if ( !$this->output->supportColor() ) {
            $this->write('Sorry, Current terminal is not support output color text.');

            return 0;
        }

        $styles = $this->output->getColor()->getStyleNames();
        $this->write('normal text output');

        foreach ($styles as $style) {
            $this->output->write("<$style>$style style text</$style>");
        }

        $this->output->block('block message text');
        $this->output->warning('block message text');
        $this->output->primary('block message text');

        return 0;
    }

    /**
     * a example for use arguments on command
     * @usage home/useArgs [arg1=val1 arg2=arg2] [options]
     * @example ./bin/app home/useArgs status=2 name=john city -s=test --page=23 -d -rf --debug --test=false
     */
    public function useArgCommand()
    {
        $this->write('input arguments:');
        var_dump($this->input->get());

        $this->write('input options:');
        var_dump($this->input->getOpts());

        $this->write('input object:');
        var_dump($this->input);
    }

    /**
     * use Interact::confirm method
     *
     */
    public function confirmCommand()
    {
        $a = Interact::confirm('continue');

        $this->write('you answer is: ' . ($a ? 'yes' : 'no') );
    }

    /**
     * use <default>Interact::select</default> method
     *
     */
    public function selectCommand()
    {
        $opts = ['john','simon','rose'];
        $a = Interact::select('you name is', $opts);

        $this->write('you answer is: ' . $opts[$a] );
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
