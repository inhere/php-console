<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/24 0024
 * Time: 15:32
 */

namespace Inhere\Console\Tests\Components;

use Inhere\Console\Components\TextTemplate;
use PHPUnit\Framework\TestCase;

/**
 * Class TextTemplateTest
 * @package Inhere\Console\Tests\Components
 * @covers TextTemplate
 */
class TextTemplateTest extends TestCase
{
    public function testRender()
    {
        $tpl = <<<EOF
test tpl on date {\$date}

use array {\$map.0} {\$map.key1}
EOF;
        $date = date('Ymd');

        $tt = new TextTemplate([
            'name' => 'test',
            'date' => $date,
            'map' => [
                'VAL0',
                'key1' => 'VAL1',
            ],
        ]);

        $ret = $tt->render($tpl);
        $this->assertNotEmpty($ret);
        $this->assertTrue((bool)strpos($ret, $date));
        $this->assertTrue((bool)strpos($ret, 'VAL0'));
        $this->assertStringEndsWith('VAL1', $ret);
    }
}
