<?php declare(strict_types=1);

namespace Inhere\Console\Annotate;

use Toolkit\Stdlib\Str;
use Toolkit\Stdlib\Util\PhpDoc;
use function array_keys;
use function explode;
use function preg_match;
use function trim;

/**
 * class DocblockRules
 */
class DocblockRules
{
    /**
     * Allow display message tags in the command method docblock
     *
     * @var array
     */
    protected static $allowedTags = [
        // tag name => multi line align
        'desc'      => false,
        'usage'     => false,
        'arguments' => true,
        'options'   => true,
        'example'   => true,
        'help'      => true,
    ];

    /**
     * Parsed docblock tags
     *
     * @var array
     * @see $allowedTags for keys
     * @psalm-var array<string, mixed>
     */
    private $docTags;

    /**
     * @var array
     */
    private $argRules = [];

    /**
     * @var array
     */
    private $optRules = [];

    /**
     * @param string $doc
     *
     * @return static
     */
    public static function newByDocblock(string $doc): self
    {
        $dr = new self();
        $dr->setDocTagsByDocblock($doc);

        return $dr;
    }

    /**
     * @param array $docTags
     *
     * @return static
     */
    public static function new(array $docTags = []): self
    {
        return new self($docTags);
    }

    /**
     * Class constructor.
     *
     * @param array $docTags
     */
    public function __construct(array $docTags = [])
    {
        $this->docTags = $docTags;
    }

    /**
     * @param string $doc
     */
    public function setDocTagsByDocblock(string $doc): void
    {
        $docTags = PhpDoc::parseDocs($doc, [
            'default' => 'desc',
            'allow'   => self::getAllowedTags(),
        ]);

        $this->docTags = $docTags;
    }

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
     * @return $this
     * @example
     *  {fullCmd}  php-toolkit/cli-utils
     *  {fullCmd}  https://github.com/php-toolkit/cli-utils
     *
     */
    public function parse(): self
    {
        if ($argsText = $this->docTags['arguments'] ?? '') {
            $lines = explode("\n", $argsText);

            $this->argRules = $this->parseMultiLines($lines);
        }

        if ($optsText = $this->docTags['options'] ?? '') {
            $lines = explode("\n", $optsText);

            $this->optRules = $this->parseMultiLines($lines);
        }

        return $this;
    }

    /**
     * @param array $lines
     *
     * @return array
     */
    protected function parseMultiLines(array $lines): array
    {
        $index = 0;
        $rules = $kvRules = [];

        // $keyWidth = 16;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!$trimmed) {
                continue;
            }

            $nodes = Str::explode($trimmed, '   ', 2);
            if (!isset($nodes[1])) {
                if ($index === 0) { // invalid first line
                    continue;
                }

                // multi desc message.
                $rules[$index - 1][1] .= "\n" . $trimmed;
                continue;
            }

            $name = $nodes[0];
            if (!preg_match('/^[\w ,-]{0,48}$/', $name)) {
                // multi desc message.
                $rules[$index - 1][1] .= "\n" . $trimmed;
                continue;
            }

            $rules[$index] = $nodes;
            $index++;
        }

        if ($rules) {
            foreach ($rules as [$name, $rule]) {
                $kvRules[$name] = $rule;
            }
        }

        return $kvRules;
    }

    /**
     * @param string $tag
     *
     * @return string
     */
    public function getTagValue(string $tag): string
    {
        return $this->docTags[$tag] ?? '';
    }

    /**
     * @return array
     */
    public static function getAllowedTags(): array
    {
        return array_keys(self::$allowedTags);
    }

    /**
     * @return array
     */
    public function getArgRules(): array
    {
        return $this->argRules;
    }

    /**
     * @return array
     */
    public function getOptRules(): array
    {
        return $this->optRules;
    }

    /**
     * @return array
     */
    public function getDocTags(): array
    {
        return $this->docTags;
    }

    /**
     * @param array $docTags
     */
    public function setDocTags(array $docTags): void
    {
        $this->docTags = $docTags;
    }
}