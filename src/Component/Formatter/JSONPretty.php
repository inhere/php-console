<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component\Formatter;

use Toolkit\Cli\Color\ColorTag;
use Toolkit\Stdlib\Helper\JsonHelper;
use Toolkit\Stdlib\Obj\AbstractObj;
use Toolkit\Stdlib\Str\StrBuffer;
use function array_merge;
use function explode;
use function is_numeric;
use function preg_replace_callback;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function trim;

/**
 * class JSONPretty
 */
class JSONPretty extends AbstractObj
{
    public const DEFAULT_THEME = [
        'keyName' => 'mga',
        'strVal'  => 'info',
        'intVal'  => 'cyan',
        'boolVal' => 'red',
        'matched' => 'red1',
    ];

    public const THEME_ONE = [
        'keyName' => 'blue',
        'strVal'  => 'cyan',
        'intVal'  => 'red',
        'boolVal' => 'green',
        'matched' => 'yellow',
    ];

    // json.cn
    public const THEME_TWO = [
        'keyName' => 'mga1',
        'strVal'  => 'info',
        'intVal'  => 'hiBlue',
        'boolVal' => 'red',
        'matched' => 'yellow',
    ];

    /**
     * @var array = DEFAULT_THEME
     */
    protected array $theme = self::DEFAULT_THEME;

    /**
     * @var int
     */
    public int $maxDepth = 10;

    public bool $noColor = false;

    public array $includes = [];

    public array $excludes = [];

    public array $matches = [];

    /**
     * @param string $json
     *
     * @return string
     */
    public static function prettyJSON(string $json): string
    {
        return (new self)->render($json);
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    public static function pretty(mixed $data): string
    {
        return (new self)->renderData($data);
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    public static function prettyData(mixed $data): string
    {
        return (new self)->renderData($data);
    }

    /**
     * @param string $json
     *
     * @return string
     */
    public function render(string $json): string
    {
        $data = JsonHelper::decode($json, true);

        return $this->renderData($data);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function renderData(mixed $data): string
    {
        $json = JsonHelper::prettyJSON($data);

        if ($this->noColor && !$this->includes && !$this->excludes) {
            return $json;
        }

        $buf = StrBuffer::new();

        foreach (explode("\n", $json) as $line) {
            $trimmed = trim($line);
            // start or end chars. eg: {} []
            if (!str_contains($trimmed, ': ')) {
                if ($this->noColor) {
                    $buf->writeln($line);
                } else {
                    $buf->writeln(ColorTag::wrap($line, $this->theme['strVal']));
                }
                continue;
            }

            if ($this->includes && !$this->includeFilter($trimmed)) {
                continue;
            }

            if ($this->excludes && !$this->excludeFilter($trimmed)) {
                continue;
            }

            if ($this->noColor) {
                $buf->writeln($line);
                continue;
            }

            if ($ms = $this->matchKeywords($line)) {
                foreach ($ms as $m) {
                    $line = str_replace($m, ColorTag::wrap($m, $this->theme['matched']), $line);
                }
                $buf->writeln($line);
                continue;
            }

            [$key, $val] = explode(': ', $line, 2);

            // format key name.
            if ($keyTag = $this->theme['keyName']) {
                $key = preg_replace_callback('/"[\w-]+"/', static function ($m) use ($keyTag) {
                    return ColorTag::wrap($m[0], $keyTag);
                }, $key);
            }

            // has end ',' clear it.
            if ($hasEndComma = str_ends_with($val, ',')) {
                $val = rtrim($val, ',');
            }

            // NULL or BOOL val
            if ($val === 'null' || $val === 'true' || $val === 'false') {
                $val = ColorTag::wrap($val, $this->theme['boolVal']);
            } elseif (is_numeric($val)) { // number
                $val = ColorTag::wrap($val, $this->theme['intVal']);
            } else { // string
                $val = ColorTag::wrap($val, $this->theme['strVal']);
            }

            $buf->writeln($key . ': ' . $val . ($hasEndComma ? ',' : ''));
        }

        return $buf->getAndClear();
    }

    /**
     * @param string $line
     *
     * @return bool return false to exclude
     */
    protected function includeFilter(string $line): bool
    {
        if (!$this->includes) {
            return true;
        }

        foreach ($this->includes as $kw) {
            if (str_contains($line, $kw)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $line
     *
     * @return bool return false to exclude
     */
    protected function excludeFilter(string $line): bool
    {
        if (!$this->excludes) {
            return true;
        }

        foreach ($this->excludes as $kw) {
            if (str_contains($line, $kw)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $line
     *
     * @return array
     */
    protected function matchKeywords(string $line): array
    {
        $matched = [];
        foreach ($this->matches as $kw) {
            if (str_contains($line, $kw)) {
                $matched[] = $kw;
            }
        }
        return $matched;
    }

    /**
     * @param array $theme
     */
    public function setTheme(array $theme): void
    {
        $this->theme = array_merge($this->theme, $theme);
    }

    /**
     * @param array|string $includes
     *
     * @return JSONPretty
     */
    public function setIncludes(array|string $includes): self
    {
        $this->includes = (array)$includes;
        return $this;
    }

    /**
     * @param array|string $excludes
     *
     * @return JSONPretty
     */
    public function setExcludes(array|string $excludes): self
    {
        $this->excludes = (array)$excludes;
        return $this;
    }

    /**
     * @param bool $noColor
     *
     * @return JSONPretty
     */
    public function setNoColor(bool $noColor): self
    {
        $this->noColor = $noColor;
        return $this;
    }

    /**
     * @param array $matches
     *
     * @return JSONPretty
     */
    public function setMatches(array $matches): self
    {
        $this->matches = $matches;
        return $this;
    }
}

