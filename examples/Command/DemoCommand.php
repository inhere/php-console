<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 18:58
 */

namespace Inhere\Console\Examples\Command;

use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use LogicException;

/**
 * Class DemoCommand
 * @package Inhere\Console\Examples\Command
 */
class DemoCommand extends Command
{
    protected static $name = 'demo';

    protected static $description = 'this is a demo alone command. but config use configure(), like symfony console: argument define by position';

    /**
     * {@inheritDoc}
     * @throws LogicException
     */
    protected function configure(): void
    {
        $this->createDefinition()
            ->setExample($this->parseCommentsVars('{script} {command} john male 43 --opt1 value1'))
            ->addArgument('name', Input::ARG_REQUIRED, 'description for the argument [name], is required')
            ->addArgument('sex', Input::ARG_OPTIONAL, 'description for the argument [sex], is optional')
            ->addArgument('age', Input::ARG_OPTIONAL, 'description for the argument [age], is optional')
            ->addOption('yes', 'y', Input::OPT_BOOLEAN, 'description for the option [yes], is boolean')
            ->addOption('opt1', null, Input::OPT_REQUIRED, 'description for the option [opt1], is required')
            ->addOption('opt2', null, Input::OPT_OPTIONAL, 'description for the option [opt2], is optional')
            ;
    }

    /**
     * description text by annotation. it is invalid when configure() is exists
     * @param  Input $input
     * @param  Output $output
     * @return int|void
     */
    public function execute($input, $output)
    {
        $output->write('hello, this in ' . __METHOD__);
        // $name = $input->getArg('name');

        $output->write(
            <<<EOF
this is argument and option example:
                                        the opt1's value
                                option: opt1 |
                                     |       |
php examples/app demo john male 43 --opt1 value1 -y
        |         |     |    |   |                |
     script    command  |    |   |______   option: yes, it use shortcat: y, and it is a Input::OPT_BOOLEAN, so no value.
                        |    |___       |
                 argument: name  |   argument: age
                            argument: sex
EOF
        );
    }
}
