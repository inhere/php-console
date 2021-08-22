<?php declare(strict_types=1);


namespace Inhere\ConsoleTest\Flag;

use Inhere\Console\Exception\FlagException;
use Inhere\Console\Flag\Flags;
use Inhere\Console\Flag\Option;
use Inhere\Console\Flag\SFlags;
use Inhere\ConsoleTest\BaseTestCase;
use function vdump;

/**
 * Class SFlagsTest
 *
 * @package Inhere\ConsoleTest\Flag
 */
class SFlagsTest extends BaseTestCase
{
    public function testParse(): void
    {
        $fs = SFlags::new();

        $flags = ['--name', 'inhere', 'arg0', 'arg1'];
        $rArgs = $fs->parseDefined($flags);
        vdump($rArgs);

        $fs->reset();

        $flags = ['--name', 'inhere', '-s', 'sv', '-f'];
        self::expectException(FlagException::class);
        $fs->parse($flags);
    }
}
