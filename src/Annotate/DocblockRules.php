<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Annotate;

use Toolkit\Stdlib\Str;
use Toolkit\Stdlib\Util\PhpDoc;
use function array_keys;
use function array_merge;
use function explode;
use function preg_match;
use function strlen;
use function substr;
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
    protected static array $allowedTags = [
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
    private array $docTags;

    /**
     * @var array
     */
    private array $argRules = [];

    /**
     * @var array
     */
    private array $optRules = [];

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
        $index = $keyWidth = 0;
        $rules = $kvRules = [];

        $sepChar = '  ';
        $sepLen = strlen($sepChar);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!$trimmed) {
                continue;
            }

            $nodes = Str::explode($trimmed, $sepChar, 2);
            if (!isset($nodes[1])) {
                if ($index === 0) { // invalid first line
                    continue;
                }

                // multi desc message.
                $rules[$index - 1][1] .= "\n" . $trimmed;
                continue;
            }

            $name = trim($nodes[0], '.');
            if (!preg_match('/^[\w ,-]{0,48}$/', $name)) {
                if ($index === 0) { // invalid first line
                    continue;
                }

                // multi desc message.
                $rules[$index - 1][1] .= "\n" . $trimmed;
                continue;
            }

            $nameLen  = strlen($name) + $sepLen;
            $keyWidth = $nameLen > $keyWidth ? $nameLen : $keyWidth;

            // TIP: special - if line indent space len gt keyWidth, is desc message of multi line.
            if (!trim(substr($line, 0, $keyWidth))) {
                $rules[$index - 1][1] .= "\n" . $trimmed; // multi desc message.
                continue;
            }

            // append
            $rules[$index] = [$name, $nodes[1]];
            $index++;
        }

        // convert to k-v data.
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
     * @param bool $onlyName
     *
     * @return array
     */
    public static function getAllowedTags(bool $onlyName = true): array
    {
        return $onlyName ? array_keys(self::$allowedTags) : self::$allowedTags;
    }

    /**
     * @param string $name
     */
    public static function addAllowedTag(string $name): void
    {
        if (!isset(self::$allowedTags[$name])) {
            self::$allowedTags[$name] = true;
        }
    }

    /**
     * @param array $allowedTags
     * @param bool $replace
     */
    public static function setAllowedTags(array $allowedTags, bool $replace = false): void
    {
        self::$allowedTags = $replace ? $allowedTags : array_merge(self::$allowedTags, $allowedTags);
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
