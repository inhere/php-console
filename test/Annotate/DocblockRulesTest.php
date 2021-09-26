<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\ConsoleTest\Annotate;

use Inhere\Console\Annotate\DocblockRules;
use Inhere\ConsoleTest\BaseTestCase;
use ReflectionException;
use ReflectionMethod;
use function vdump;

/**
 * class DocblockRulesTest
 */
class DocblockRulesTest extends BaseTestCase
{
    /**
     * @throws ReflectionException
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
        $dr->setDocTagsByDocblock(
            <<<DOC
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

    public function testParse_byDocComment_mlOptions(): void
    {
        $dr = DocblockRules::new();
        $dr->setDocTagsByDocblock(
            <<<DOC
    /**
     * Match directory paths by given keywords
     *
     * @arguments
     *  keywords   The jump target directory keywords for match.
     *
     * @options
     *  --flag     The flag set for match paths.
     *              Allow:
     *              1   Only match name path list
     *              2   Only match history path list
     *              3   match all directory path list(default)
     *  --no-name   bool;Not output name for named paths, useful for bash env.
     *  --limit     bool;Limit the match result rows
     *
     */
DOC
        );

        $dr->parse();

        $this->assertNotEmpty($opts = $dr->getOptRules());
        $this->assertCount(3, $opts);
        vdump($opts);
        $this->assertStringContainsString('2   Only match history path list', $opts['--flag']);

        $this->assertNotEmpty($args = $dr->getArgRules());
        $this->assertCount(1, $args);
        $this->assertArrayHasKey('keywords', $args);
    }

    public function testParse_byDocComment_complex(): void
    {
        $dr = DocblockRules::new();
        $dr->setDocTagsByDocblock(
            <<<DOC
    /**
     * collect git change log information by `git log`
     *
     * @arguments
     *  oldVersion      string;The old version. eg: v1.0.2
     *                  - keywords `last/latest` will auto use latest tag.
     *                  - keywords `prev/previous` will auto use previous tag.;required
     *  newVersion      string;The new version. eg: v1.2.3
     *                  - keywords `head` will use `Head` commit.;required
     *
     * @options
     *  --exclude           Exclude contains given sub-string. multi by comma split.
     *  --fetch-tags        bool;Update repo tags list by `git fetch --tags`
     *  --file              Export changelog message to file
     *  --filters           Apply built in log filters. multi by `|` split
     *                      allow:
     *                       kw     keyword filter. eg: `kw:tom`
     *                       kws    keywords filter.
     *                       ml     msg length filter.
     *                       wl     word length filter.
     *  --format            The git log option `--pretty` value.
     *                      can be one of oneline, short, medium, full, fuller, reference, email, raw, format:<string> and tformat:<string>.
     *  --style             The style for generate for changelog.
     *                      allow: markdown(<cyan>default</cyan>), simple, gh-release
     *  --repo-url          The git repo URL address. eg: https://github.com/inhere/kite
     *                      default will auto use current git origin remote url
     *  --no-merges         bool;No contains merge request logs
     *  --unshallow         bool;Convert to a complete warehouse, useful on GitHub Action.
     *  --with-author       bool;Display commit author name
     *
     * @example
     *   {binWithCmd} last head
     *   {binWithCmd} last head --style gh-release --no-merges
     *   {binWithCmd} v2.0.9 v2.0.10 --no-merges --style gh-release --exclude "cs-fixer,format codes"
     */
DOC
        );

        $dr->parse();

        $this->assertNotEmpty($dr->getTagValue('desc'));
        $this->assertNotEmpty($dr->getTagValue('example'));

        $this->assertNotEmpty($opts = $dr->getOptRules());
        $this->assertArrayHasKey('--exclude', $opts);
        $this->assertArrayHasKey('--with-author', $opts);
        $this->assertArrayHasKey('--format', $opts);
        $this->assertArrayHasKey('--style', $opts);
        $this->assertArrayHasKey('--filters', $opts);

        $this->assertStringContainsString('can be one of oneline, short', $opts['--format']);
        $this->assertStringContainsString('kw     keyword filter', $opts['--filters']);
        $this->assertStringContainsString('wl     word length filter', $opts['--filters']);
        vdump($opts);

        $this->assertNotEmpty($args = $dr->getArgRules());
        $this->assertCount(2, $args);
        $this->assertArrayHasKey('oldVersion', $args);
        $this->assertArrayHasKey('newVersion', $args);
    }
}
