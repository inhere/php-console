<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-31
 * Time: 14:27
 */

namespace Inhere\Console\Examples\Controller;

use Inhere\Console\Controller;
use Inhere\Console\Util\Interact;
use Inhere\Console\Util\Show;
use Toolkit\Cli\Terminal;
use function preg_match;

/**
 * Class InteractController
 * @package Inhere\Console\Examples\Controller
 */
class InteractController extends Controller
{
    protected static $name = 'interact';

    protected static $description = 'there are some demo commands for use interactive method';

    public static function aliases(): array
    {
        return ['iact'];
    }

    /**
     * @return array
     */
    protected static function commandAliases(): array
    {
        return [
            'cfm' => 'confirm',
            'ms' => 'multiSelect',
            'pwd' => 'password',
            'lask' => 'limitedAsk',
        ];
    }

    /**
     * This is a demo for use <magenta>Interact::confirm</magenta> method
     */
    public function confirmCommand(): void
    {
        // can also: $this->confirm();
        $a = Interact::confirm('ensure continue');

        $this->write('Your answer is: ' . ($a ? 'yes' : 'no'));
    }

    /**
     * This is a demo for use <magenta>Interact::select()</magenta> method
     */
    public function selectCommand(): void
    {
        $opts = ['john', 'simon', 'rose'];
        // can also: $this->select();
        $a = Interact::select('you name is', $opts);

        $this->write('Your answer is: ' . $opts[$a]);
    }

    /**
     * This is a demo for use <magenta>Interact::multiSelect()</magenta> method
     */
    public function multiSelectCommand(): void
    {
        $opts = ['john', 'simon', 'rose', 'tom'];

        // can also: $a = Interact::multiSelect('Your friends are', $opts);
        $a = $this->multiSelect('Your friends are', $opts);

        $this->write('Your answer is: ' . json_encode($a));
    }

    /**
     * This is a demo for use <magenta>Interact::ask()</magenta> method
     */
    public function askCommand(): void
    {
        $a = Interact::ask('you name is: ', '', static function ($val, &$err) {
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
    public function limitedAskCommand(): void
    {
        $times = (int)$this->input->getOpt('limit', 3);

        if ($this->input->getBoolOpt('nv')) {
            $a = Interact::limitedAsk('you name is: ', '', null, $times);
        } else {
            $a = Interact::limitedAsk('you name is: ', '', static function ($val) {
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
    public function passwordCommand(): void
    {
        $pwd = $this->askPassword();

        $this->write('Your input is: ' . $pwd);
    }

    /**
     * This is a demo for show cursor move on the Terminal screen
     */
    public function cursorCommand(): void
    {
        $this->write('hello, this in ' . __METHOD__);
        $this->write('this is a message text.', false);

        sleep(1);
        Terminal::make()->cursor(Terminal::CUR_BACKWARD, 6);

        sleep(1);
        Terminal::make()->cursor(Terminal::CUR_FORWARD, 3);

        sleep(1);
        Terminal::make()->cursor(Terminal::CUR_BACKWARD, 2);

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
