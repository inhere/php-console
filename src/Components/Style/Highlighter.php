<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-11
 * Time: 15:50
 */

namespace Inhere\Console\Components\Style;

/**
 * Class Highlighter
 * @package Inhere\Console\Components\Style
 * @see jakub-onderka/php-console-highlighter
 * @link https://github.com/JakubOnderka/PHP-Console-Highlighter/blob/master/src/Highlighter.php
 */
class Highlighter
{
    const TOKEN_DEFAULT = 'token_default';
    const TOKEN_COMMENT = 'token_comment';
    const TOKEN_STRING = 'token_string';
    const TOKEN_HTML = 'token_html';
    const TOKEN_KEYWORD = 'token_keyword';

    const ACTUAL_LINE_MARK = 'actual_line_mark';
    const LINE_NUMBER = 'line_number';

    /** @var Style */
    private $color;

    /** @var self */
    private static $instance;

    /** @var array */
    private $defaultTheme = [
        self::TOKEN_STRING => 'red',
        self::TOKEN_COMMENT => 'yellow',
        self::TOKEN_KEYWORD => 'info',
        self::TOKEN_DEFAULT => 'normal',
        self::TOKEN_HTML => 'cyan',
        self::ACTUAL_LINE_MARK => 'red',
        self::LINE_NUMBER => 'darkGray',
    ];

    /** @var bool */
    private $hasTokenFunc;

    /**
     * @return Highlighter
     */
    public static function create(): Highlighter
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param Style $color
     */
    public function __construct(Style $color = null)
    {
        $this->color = $color ?: Style::create();
        $this->hasTokenFunc = \function_exists('token_get_all');
    }

    /**
     * highlight a full php file content
     * @param string $source
     * @param bool $withLineNumber with line number
     * @return string
     */
    public function highlight(string $source, bool $withLineNumber = false): string
    {
        $tokenLines = $this->getHighlightedLines($source);
        $lines = $this->colorLines($tokenLines);

        if ($withLineNumber) {
            return $this->lineNumbers($lines);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param string $source
     * @param int $lineNumber
     * @param int $linesBefore
     * @param int $linesAfter
     * @return string
     * @throws \InvalidArgumentException
     */
    public function snippet(string $source, int $lineNumber, int $linesBefore = 2, int $linesAfter = 2): string
    {
        return $this->highlightSnippet($source, $lineNumber, $linesBefore, $linesAfter);
    }

    /**
     * @param string $source
     * @param int $lineNumber
     * @param int $linesBefore
     * @param int $linesAfter
     * @return string
     * @throws \InvalidArgumentException
     */
    public function highlightSnippet($source, $lineNumber, $linesBefore = 2, $linesAfter = 2): string
    {
        $tokenLines = $this->getHighlightedLines($source);

        $offset = $lineNumber - $linesBefore - 1;
        $offset = max($offset, 0);
        $length = $linesAfter + $linesBefore + 1;
        $tokenLines = \array_slice($tokenLines, $offset, $length, $preserveKeys = true);

        $lines = $this->colorLines($tokenLines);

        return $this->lineNumbers($lines, $lineNumber);
    }

    /**
     * @param string $source
     * @return array
     */
    private function getHighlightedLines(string $source): array
    {
        $source = \str_replace(["\r\n", "\r"], "\n", $source);

        if ($this->hasTokenFunc) {
            $tokens = $this->tokenize($source);
            return $this->splitToLines($tokens);
        }

        // if no func: token_get_all
        return \explode("\n", $source);
    }

    /**
     * @param string $source
     * @return array
     */
    private function tokenize(string $source): array
    {
        $buffer = '';
        $output = [];
        $tokens = \token_get_all($source);
        $newType = $currentType = null;

        foreach ($tokens as $token) {
            if (\is_array($token)) {
                switch ($token[0]) {
                    case T_INLINE_HTML:
                        $newType = self::TOKEN_HTML;
                        break;
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        $newType = self::TOKEN_COMMENT;
                        break;
                    case T_ENCAPSED_AND_WHITESPACE:
                    case T_CONSTANT_ENCAPSED_STRING:
                        $newType = self::TOKEN_STRING;
                        break;
                    case T_WHITESPACE:
                        break;
                    case T_OPEN_TAG:
                    case T_OPEN_TAG_WITH_ECHO:
                    case T_CLOSE_TAG:
                    case T_STRING:
                    case T_VARIABLE:
                        // Constants
                    case T_DIR:
                    case T_FILE:
                    case T_METHOD_C:
                    case T_DNUMBER:
                    case T_LNUMBER:
                    case T_NS_C:
                    case T_LINE:
                    case T_CLASS_C:
                    case T_FUNC_C:
                        //case T_TRAIT_C:
                        $newType = self::TOKEN_DEFAULT;
                        break;
                    default:
                        // Compatibility with PHP 5.3
                        if (\defined('T_TRAIT_C') && $token[0] === T_TRAIT_C) {
                            $newType = self::TOKEN_DEFAULT;
                        } else {
                            $newType = self::TOKEN_KEYWORD;
                        }
                }
            } else {
                $newType = $token === '"' ? self::TOKEN_STRING : self::TOKEN_KEYWORD;
            }

            if ($currentType === null) {
                $currentType = $newType;
            }

            if ($currentType != $newType) {
                $output[] = [$currentType, $buffer];
                $buffer = '';
                $currentType = $newType;
            }

            $buffer .= \is_array($token) ? $token[1] : $token;
        }

        if (null !== $newType) {
            $output[] = [$newType, $buffer];
        }

        return $output;
    }

    /**
     * @param array $tokens
     * @return array
     */
    private function splitToLines(array $tokens): array
    {
        $lines = $line = [];

        foreach ($tokens as $token) {
            foreach (explode("\n", $token[1]) as $count => $tokenLine) {
                if ($count > 0) {
                    $lines[] = $line;
                    $line = [];
                }
                if ($tokenLine === '') {
                    continue;
                }

                $line[] = [$token[0], $tokenLine];
            }
        }
        $lines[] = $line;

        return $lines;
    }

    /**
     * @param array[] $tokenLines
     * @return array
     * @throws \InvalidArgumentException
     */
    private function colorLines(array $tokenLines): array
    {
        if (!$this->hasTokenFunc) {
            return $tokenLines;
        }

        $lines = [];

        foreach ($tokenLines as $lineCount => $tokenLine) {
            $line = '';
            foreach ($tokenLine as list($tokenType, $tokenValue)) {
                $style = $this->defaultTheme[$tokenType];

                if ($this->color->hasStyle($style)) {
                    $line .= $this->color->apply($style, $tokenValue);
                } else {
                    $line .= $tokenValue;
                }
            }

            $lines[$lineCount] = $line;
        }

        return $lines;
    }

    /**
     * @param array $lines
     * @param null|int $markLine
     * @return string
     */
    private function lineNumbers(array $lines, $markLine = null): string
    {
        end($lines);

        $snippet = '';
        $lineLen = \strlen(key($lines) + 1);
        $lmStyle = $this->defaultTheme[self::ACTUAL_LINE_MARK];
        $lnStyle = $this->defaultTheme[self::LINE_NUMBER];

        foreach ($lines as $i => $line) {
            if ($markLine !== null) {
                $snippet .= ($markLine === $i + 1 ? $this->color->apply($lmStyle, '  > ') : '    ');
                $snippet .= $this->color->apply(
                    $markLine === $i + 1 ? $lmStyle : $lnStyle,
                    str_pad($i + 1, $lineLen, ' ', STR_PAD_LEFT) . '| '
                );
            } else {
                $snippet .= $this->color->apply($lnStyle, str_pad($i + 1, $lineLen, ' ', STR_PAD_LEFT) . '| ');
            }

            $snippet .= $line . PHP_EOL;
        }

        return $snippet;
    }

    /**
     * @return array
     */
    public function getDefaultTheme(): array
    {
        return $this->defaultTheme;
    }

    /**
     * @param array $defaultTheme
     */
    public function setDefaultTheme(array $defaultTheme)
    {
        $this->defaultTheme = array_merge($this->defaultTheme, $defaultTheme);
    }
}
