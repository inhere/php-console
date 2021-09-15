<?php declare(strict_types=1);

namespace Inhere\ConsoleTest\Annotate;

use Inhere\Console\Annotate\DocblockRules;
use Inhere\ConsoleTest\BaseTestCase;
use ReflectionMethod;

/**
 * class DocblockRulesTest
 */
class DocblockRulesTest extends BaseTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testParse_byReflect(): void
    {
        $dr = DocblockRules::new();

        $rftMth = new ReflectionMethod($dr, 'parse');
        $dr->setDocTagsByDocblock($rftMth->getDocComment());
        $dr->parse();

        $this->assertNotEmpty($dr->getTagValue('desc'));
        $this->assertNotEmpty($dr->getTagValue('example'));

        $this->assertNotEmpty($dr->getArgRules());
        $this->assertNotEmpty($dr->getOptRules());
    }

    /**
     */
    public function testParse_byDocComment(): void
    {
        $dr = DocblockRules::new();
        $dr->setDocTagsByDocblock(<<<DOC
    /**
     * parse multi line text to flag rules
     *
     * @options
     *  -r, --remote         The git remote name. default is `origin`
     *      --main           bool;Use the config `mainRemote` name
     *
     * @arguments
     *  repoPath    The remote git repo URL or repository group/name.
     *              If not input, will auto parse from current work directory
     *
     * @return \$this
     * @example
     *  {fullCmd}  php-toolkit/cli-utils
     *  {fullCmd}  https://github.com/php-toolkit/cli-utils
     *
     */
DOC
);
        $dr->parse();

        $this->assertNotEmpty($dr->getTagValue('desc'));
        $this->assertNotEmpty($dr->getTagValue('example'));

        $this->assertNotEmpty($opts = $dr->getOptRules());
        $this->assertNotEmpty($args = $dr->getArgRules());

        $this->assertArrayHasKey('-r, --remote', $opts);
        $this->assertArrayHasKey('--main', $opts);
        $this->assertArrayHasKey('repoPath', $args);
    }
}
