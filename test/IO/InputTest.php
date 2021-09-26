<?php declare(strict_types=1);

namespace Inhere\ConsoleTest\IO;

use Inhere\Console\IO\Input;
use PHPUnit\Framework\TestCase;
use function vdump;

/**
 * Class InputTest
 *
 * @package Inhere\ConsoleTest\IO
 */
class InputTest extends TestCase
{
    public function testBasic(): void
    {
        $in = new Input(['./bin/app', 'cmd', 'val0', 'val1']);

        $this->assertSame('./bin/app', $in->getScriptFile());
        $this->assertSame('app', $in->getScriptName());
        $this->assertSame('cmd', $in->getCommand());
    }
}
