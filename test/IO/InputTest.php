<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\ConsoleTest\IO;

use Inhere\Console\IO\Input;
use PHPUnit\Framework\TestCase;

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
        // $this->assertSame('cmd', $in->getCommand());
        $this->assertEquals('cmd val0 val1', $in->getFullScript());
        $this->assertEquals("'./bin/app' cmd val0 val1", $in->toString());
    }
}
