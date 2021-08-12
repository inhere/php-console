<?php declare(strict_types=1);


namespace Inhere\ConsoleTest\Flag;

use Inhere\Console\Exception\FlagException;
use Inhere\Console\Flag\Flags;
use Inhere\Console\Flag\Option;
use Inhere\ConsoleTest\BaseTestCase;
use function vdump;

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
        self::assertTrue($fs->hasDefined('name'));
        self::assertFalse($fs->hasMatched('name'));

        $args = ['--name', 'inhere', 'arg0', 'arg1'];
        $fs->parse($args);
        self::assertTrue($fs->hasMatched('name'));

        $fs->reset();
        $args = ['--name', 'inhere', '-s', 'sv', '-f'];
        self::expectException(FlagException::class);
        $fs->parse($args);
    }
}
