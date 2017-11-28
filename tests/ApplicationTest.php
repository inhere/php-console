<?php

use Inhere\Console\IO\InputInterface;
use Inhere\Console\IO\OutputInterface;
use PHPUnit\Framework\TestCase;
use Inhere\Console\Application;

/**
 * @covers Application
 */
class ApplicationTest extends TestCase
{
    public function testApp()
    {
        $app = new Application([
            'name' => 'Tests',
            'debug' => 1,
            'version' => '1.0.0',
        ]);

        $this->assertArrayHasKey('name', $app->getMeta());
        $this->assertEquals('Tests', $app->getMeta('name'));

        $this->assertInstanceOf(InputInterface::class, $app->getInput());
        $this->assertInstanceOf(OutputInterface::class, $app->getOutput());
    }
}
