<?php declare(strict_types=1);

namespace Inhere\Console\Examples\Controller\Attach;

use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use function vdump;

/**
 * class DemoSubCommand
 *
 * @author inhere
 */
class DemoSubCommand extends Command
{
    protected static string $name = 'sub1';
    protected static string $desc = 'alone sub command on an group';

    public function getOptions(): array
    {
        return [
            'str1'    => 'string option1',
            's2,str2' => 'string option2',
        ];
    }

    /**
     * Do execute command
     *
     * @param Input $input
     * @param Output $output
     *
     * @return void|mixed
     */
    protected function execute(Input $input, Output $output)
    {
        vdump(__METHOD__);
    }
}
