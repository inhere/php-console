<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-11
 * Time: 17:56
 */

namespace Inhere\Console\Components\AutoComplete;

/**
 * Class AutoCompletion - a simple command auto-completion tool
 *
 * @todo not available
 * @package Inhere\Console\Components\AutoComplete
 */
class AutoCompletion
{
    /**
     * @var callable
     */
    private $matcher;

    /**
     * @var array
     */
    private $data;

    /**
     * AutoCompletion constructor.
     * @param array $data
     * @param bool $enable
     */
    public function __construct(array $data = [], $enable = true)
    {
        $this->data = $data;

        if ($enable) {
            $this->register();
        }
    }

    /**
     * Activate readline tab completion.
     */
    public function register()
    {
        readline_completion_function([&$this, 'completionHandler']);
    }

    /**
     * The readline_completion_function callback handler.
     * @param string $input the user input
     * @param $index
     *
     * @return array
     */
    public function completionHandler($input, $index)
    {
        // $info = readline_info();
        // $line = substr($info['line_buffer'], 0, $info['end']);
        // $tokens = token_get_all('<?php ' . $line);
        $input = trim($input);

        if (!$input) {
            return $this->data;
        }

        $matches = [];
        $matcher = $this->matcher;

        foreach ($this->data as $item) {
            if ($matcher && $matcher($input, $item)) {
                $matches[] = $item;
            } elseif ($this->internalMatcher($input, $item)) {
                $matches[] = $item;
            }
        }

        if (!$matches) {
            return [];
        }

        $matches = array_unique($matches);
        return $matches ?: [''];
    }

    /**
     * @param $input
     * @param $target
     * @return bool
     */
    protected function internalMatcher($input, $target)
    {
        return stripos($target, $input) !== false;
    }

    /**
     * @return callable
     */
    public function getMatcher()
    {
        return $this->matcher;
    }

    /**
     * @param callable $matcher
     */
    public function setMatcher(callable $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->data = [];

        return $this;
    }
}
