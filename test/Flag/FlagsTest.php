<?php declare(strict_types=1);

namespace Inhere\ConsoleTest\Flag;

use Inhere\Console\Flag\Flags;
use Inhere\Console\Flag\Option;
use Inhere\ConsoleTest\BaseTestCase;

/**
 * Class FlagsTest
 *
 * @package Inhere\ConsoleTest\Flag
 */
class FlagsTest extends BaseTestCase
{
    public function testParse(): void
    {
        $fs = Flags::new();

        $fs->addOption(Option::new('name'));

        $args = ['--name', 'inhere', '-s', 'sv', '-f'];
    }
}
