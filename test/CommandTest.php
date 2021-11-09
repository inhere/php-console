<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\ConsoleTest;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use PHPUnit\Framework\TestCase;

/**
 * Class CommandTest
 * @package Inhere\ConsoleTest
 */
class CommandTest extends TestCase
{
    public function testBasic(): void
    {
        $c = new TestCommand(new Input(), new Output());

        $this->assertSame('test1', $c::getName());
        $this->assertStringContainsString('desc', $c::getDesc());
    }
}
