<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Examples\Command;

use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use LogicException;
use Toolkit\PFlag\FlagType;

/**
 * Class DemoCommand
 * @package Inhere\Console\Examples\Command
 */
class DemoCommand extends Command
{
    protected static string $name = 'demo';

    protected static string $desc = 'this is a demo alone command. but use Definition instead of annotations';

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->getFlags()
            ->addArg('name', 'description for the argument [name], is required', FlagType::STRING, true)
            ->addArg('sex', 'description for the argument [sex], is optional')
            ->addArg('age', 'description for the argument [age], is optional', FlagType::INT)
            ->addOpt('yes', 'y', 'description for the option [yes], is boolean', FlagType::BOOL)
            ->addOpt('opt1', '', 'description for the option [opt1], is required', FlagType::STRING, true)
            ->addOpt('opt2', '', 'description for the option [opt2], is optional')
            ->setExample($this->parseCommentsVars('{script} {command} john male 43 --opt1 value1'))
            ;
    }

    /**
     * description text by annotation. it is invalid when configure() is exists
     * @param  Input $input
     * @param  Output $output
     * @return int|void
     */
    public function execute(Input $input, Output $output)
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
