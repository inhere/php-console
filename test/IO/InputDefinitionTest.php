<?php declare(strict_types=1);

namespace Inhere\ConsoleTest\IO;

use Inhere\Console\IO\Input;
use Inhere\Console\IO\InputDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Class InputDefinitionTest
 *
 * @package Inhere\ConsoleTest\IO
 */
class InputDefinitionTest extends TestCase
{
    public function testBasic(): void
    {
        $def = new InputDefinition();

        $def->addArg('arg0', Input::OPT_OPTIONAL, 'this is arg0');

        $this->assertNotEmpty($def->getArgument('arg0'));
    }
}
