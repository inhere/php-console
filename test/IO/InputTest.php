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

        $this->assertSame('./bin/app', $in->getScript());
        $this->assertSame('app', $in->getScriptName());
        $this->assertSame('cmd', $in->getCommand());
    }

    public function testArguments(): void
    {
        $in = new Input(['./bin/app', 'cmd', 'val0', 'val1']);

        $this->assertTrue($in->hasArg(0));
        $this->assertSame('val0', $in->getArgument(0));
        $this->assertSame('val1', $in->getArgument(1));

        $in = new Input(["bin/kite", "jump", "get", "-"]);
        $this->assertTrue($in->hasArg(0));
        $this->assertSame('get', $in->getArgument(0));
        $this->assertSame('-', $in->getArgument(1));
    }

    public function testBindArgument(): void
    {
        $in = new Input(['./bin/app', 'cmd', 'val0', 'val1']);

        $this->assertTrue($in->hasArg(0));
        $this->assertFalse($in->hasArg('arg0'));
        $this->assertFalse($in->hasArg('arg1'));

        $in->bindArgument('arg0', 0);
        $this->assertTrue($in->hasArg('arg0'));

        $in->bindArguments(['arg1' => 1]);
        $this->assertTrue($in->hasArg('arg1'));
    }
}
