<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component\Formatter;

use JsonException;
use Toolkit\Cli\Color\ColorTag;
use Toolkit\Stdlib\Helper\JsonHelper;
use Toolkit\Stdlib\Obj\AbstractObj;
use Toolkit\Stdlib\Str\StrBuffer;
use function array_merge;
use function explode;
use function is_numeric;
use function json_decode;
use function preg_replace_callback;
use function rtrim;
use function str_contains;
use function str_ends_with;
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
    ];

    /**
     * @var array{keyName: string, strVal: string, intVal: string, boolVal: string}
     */
    protected array $theme = self::DEFAULT_THEME;

    /**
     * @var int
     */
    public int $maxDepth = 10;

    /**
     * @param string $json
     *
     * @return string
     * @throws JsonException
     */
    public function render(string $json): string
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $this->renderData($data);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function renderData(array $data): string
    {
        $buf  = StrBuffer::new();
        $json = JsonHelper::prettyJSON($data);

        foreach (explode("\n", $json) as $line) {
            $trimmed = trim($line);
            // start or end chars. eg: {} []
            if (!str_contains($trimmed, ': ')) {
                $buf->writeln($line);
                continue;
            }

            [$key, $val] = explode(': ', $line);

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

            // bool val
            if ($val === 'true' || $val === 'false') {
                $val = ColorTag::wrap($val, $this->theme['boolVal']);
            } elseif (is_numeric($val)) { // number
                $val = ColorTag::wrap($val, $this->theme['intVal']);
            } else { // string
                $val = ColorTag::wrap($val, $this->theme['strVal']);
            }

            $buf->writeln($key . ': ' . $val . ($hasEndComma ?  ',' : ''));
        }

        return $buf->getAndClear();
    }

    /**
     * @param array $theme
     */
    public function setTheme(array $theme): void
    {
        $this->theme = array_merge($this->theme, $theme);
    }
}

