<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/30
 * Time: 下午8:14
 */

namespace Inhere\Console\IO;

use Inhere\Console\Util\Helper;
use InvalidArgumentException;
use LogicException;
use function array_filter;
use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function implode;
use function is_array;
use function is_int;
use function preg_split;
use function sprintf;
use function strtoupper;
use function trim;

/**
 * Class InputDefinition
 *
 * @package Inhere\Console\IO
 * @refer \Symfony\Component\Console\Input\InputDefinition
 */
class InputDefinition
{
    /** @var array */
    private static $defaultArgOptConfig = [
        'mode'        => null,
        'default'     => null,
        'description' => '',
    ];

    /**
     * @var string|array
     */
    private $example;

    /**
     * @var string
     */
    private $description;

    /**
     * @var array[]
     */
    private $arguments = [];

    /**
     * @var int
     */
    private $requiredCount = 0;

    /**
     * @var bool
     */
    private $hasOptionalArgument = false;

    /**
     * @var bool
     */
    private $hasAnArrayArgument = false;

    /**
     * @var array[]
     */
    private $options;

    /**
     * @var array
     */
    private $shortcuts;

    /**
     * @param array $arguments
     * @param array $options
     *
     * @return InputDefinition
     */
    public static function make(array $arguments = [], array $options = []): InputDefinition
    {
        return new self($arguments, $options);
    }

    /**
     * Constructor.
     *
     * @param array $arguments
     * @param array $options
     *
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function __construct(array $arguments = [], array $options = [])
    {
        $this->setArguments($arguments);
        $this->setOptions($options);
    }

    /***************************************************************************
     * some methods for the arguments
     ***************************************************************************/

    /**
     * @param array $arguments
     *
     * @return InputDefinition
     * @throws LogicException
     */
    public function setArguments(array $arguments): InputDefinition
    {
        $this->arguments = [];

        return $this->addArguments($arguments);
    }

    /**
     * @param array $arguments
     *
     * @return $this
     * @throws LogicException
     */
    public function addArguments(array $arguments): self
    {
        foreach ($arguments as $name => $arg) {
            $arg = $this->mergeArgOptConfig($arg);
            $this->addArgument($name, $arg['mode'], $arg['description'], $arg['default']);
        }

        return $this;
    }

    /**
     * alias of the addArgument
     *
     * @param string   $name
     * @param int|null $mode
     * @param string   $description
     * @param null     $default
     *
     * @return InputDefinition
     */
    public function addArg(string $name, int $mode = null, string $description = '', $default = null): self
    {
        return $this->addArgument($name, $mode, $description, $default);
    }

    /**
     * Adds an argument.
     *
     * @param string $name        The argument name
     * @param int    $mode        The argument mode flags. eg: Input::ARG_REQUIRED, Input::ARG_OPTIONAL
     *                            allow more flags, eg: Input::ARG_REQUIRED|Input::ARG_IS_ARRAY
     * @param string $description A description text
     * @param mixed  $default     The default value (for Input::ARG_OPTIONAL mode only)
     *
     * @return $this
     * @throws LogicException
     */
    public function addArgument(string $name, int $mode = 0, string $description = '', $default = null): self
    {
        if (0 === $mode) {
            $mode = Input::ARG_OPTIONAL;
        } elseif (!is_int($mode) || $mode > 7 || $mode < 1) {
            throw new InvalidArgumentException(sprintf('Argument mode "%s" is not valid.', $mode));
        }

        if (isset($this->arguments[$name])) {
            throw new LogicException(sprintf('An argument with name "%s" already exists.', $name));
        }

        if ($this->hasAnArrayArgument) {
            throw new LogicException('Cannot add an argument after an array argument.');
        }

        $required = ($mode & Input::ARG_REQUIRED) > 0;
        if ($required && $this->hasOptionalArgument) {
            throw new LogicException('Cannot add a required argument after an optional one.');
        }

        $isArray = ($mode & Input::ARG_IS_ARRAY) > 0;
        if ($isArray) {
            // if (false === $this->argumentIsAcceptValue($mode)) {
            //     throw new InvalidArgumentException('Impossible to have an option mode ARG_IS_ARRAY if the option does not accept a value.');
            // }

            $this->hasAnArrayArgument = true;

            if (null === $default) {
                $default = [];
            } elseif (!is_array($default)) {
                throw new LogicException('A default value for an array argument must be an array.');
            }
        }

        if ($required) {
            if (null !== $default) {
                throw new LogicException('Cannot set a default value except for OPTIONAL-ARGUMENT mode.');
            }

            ++$this->requiredCount;
        } else {
            $this->hasOptionalArgument = true;
        }

        $index = count($this->arguments);

        $this->arguments[$name] = [
            'mode'        => $mode,
            'index'       => $index,
            'required'    => $required,
            'isArray'     => $isArray,
            'description' => $description,
            'default'     => $default,
        ];

        return $this;
    }

    /**
     * @param int|string $name
     * @param null       $default
     *
     * @return string|int|null
     */
    public function getArgument($name, $default = null)
    {
        $arguments = is_int($name) ? array_values($this->arguments) : $this->arguments;

        if (!isset($arguments[$name])) {
            return $default;
            // throw new \InvalidArgumentException(sprintf('The "%s" argument does not exist.', $name));
        }

        return $arguments[$name];
    }

    /**
     * @param string|int $name The argument name or position
     *
     * @return bool true if the InputArgument object exists, false otherwise
     */
    public function hasArgument($name): bool
    {
        $arguments = is_int($name) ? array_values($this->arguments) : $this->arguments;

        return isset($arguments[$name]);
    }

    /**
     * @return array[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Returns the number of arguments.
     *
     * @return int
     */
    public function getArgumentCount(): int
    {
        return count($this->arguments);
    }

    /**
     * get count of required arguments
     *
     * @return int
     */
    public function getArgumentRequiredCount(): int
    {
        return $this->requiredCount;
    }

    /***************************************************************************
     * some methods for the options
     ***************************************************************************/

    /**
     * Sets the options
     *
     * @param array[] $options An array of InputOption objects
     *
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function setOptions(array $options = []): void
    {
        $this->options = $this->shortcuts = [];
        $this->addOptions($options);
    }

    /**
     * Adds an array of option
     *
     * @param array
     *
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function addOptions(array $options = []): void
    {
        foreach ($options as $name => $opt) {
            $opt = $this->mergeArgOptConfig($opt);
            $this->addOption($name, $opt['mode'], $opt['description'], $opt['default']);
        }
    }

    /**
     * alias of the addOption
     *
     * @param string      $name
     * @param string|null $shortcut
     * @param int|null    $mode
     * @param string      $description
     * @param null|mixed  $default
     *
     * @return InputDefinition
     */
    public function addOpt(
        string $name,
        string $shortcut = null,
        int $mode = null,
        string $description = '',
        $default = null
    ): self {
        return $this->addOption($name, $shortcut, $mode, $description, $default);
    }

    /**
     * Adds an option.
     *
     * @param string|bool       $name        The option name, must is a string
     * @param string|array|null $shortcut    The shortcut (can be null)
     *                                       - array: [a, b]
     *                                       - string: 'a|b'
     * @param int               $mode        The option mode: One of the Input::OPT_* constants
     * @param string            $description A description text
     * @param mixed             $default     The default value (must be null for InputOption::OPT_BOOL)
     *
     * @return $this
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function addOption(
        string $name,
        $shortcut = '',
        int $mode = 0,
        string $description = '',
        $default = null
    ): self {
        $name = trim($name, '-');
        if (empty($name)) {
            throw new InvalidArgumentException('An option name cannot be empty.');
        }

        if (isset($this->options[$name])) {
            throw new LogicException(sprintf('An option named "%s" already exists.', $name));
        }

        if ($mode <= 0) {
            $mode = Input::OPT_BOOLEAN;
        } elseif (!is_int($mode) || $mode > 15 || $mode < 1) {
            throw new InvalidArgumentException(sprintf('Option mode "%s" is not valid.', $mode));
        }

        $isArray = ($mode & Input::OPT_IS_ARRAY) > 0;
        if ($isArray && !$this->optionIsAcceptValue($mode)) {
            throw new InvalidArgumentException('Impossible to have an option mode OPT_IS_ARRAY if the option does not accept a value.');
        }

        // set default value
        $isBoolean = Input::OPT_BOOLEAN === (Input::OPT_BOOLEAN & $mode);
        if ($isBoolean) {
            if (null !== $default) {
                throw new LogicException('Cannot set a default value when using OPT_BOOLEAN mode.');
            }

            $default = false;
        } elseif ($isArray) {
            if (null === $default) {
                $default = [];
            } elseif (!is_array($default)) {
                throw new LogicException('A default value for an array option must be an array.');
            }
        }

        $default = $this->optionIsAcceptValue($mode) ? $default : false;

        if ($shortcut) {
            if (is_array($shortcut)) {
                $shortcut = implode('|', $shortcut);
            }

            $shortcuts = preg_split('{(\|)-?}', ltrim($shortcut, '-'));
            $shortcuts = array_filter($shortcuts);
            $shortcut  = implode('|', $shortcuts);

            foreach ($shortcuts as $srt) {
                if (isset($this->shortcuts[$srt])) {
                    throw new LogicException(sprintf('An option with shortcut "%s" already exists.', $srt));
                }

                $this->shortcuts[$srt] = $name;
            }
        }

        $this->options[$name] = [
            'mode'        => $mode,
            'isArray'     => $isArray,
            'shortcut'    => $shortcut, // 允许数组
            'required'    => Helper::hasMode($mode, Input::OPT_REQUIRED),
            'optional'    => Helper::hasMode($mode, Input::OPT_OPTIONAL),
            'description' => $description,
            'default'     => $default,
        ];

        return $this;
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getOption(string $name): array
    {
        if (!$this->hasOption($name)) {
            throw new InvalidArgumentException(sprintf('The "--%s" option does not exist.', $name));
        }

        return $this->options[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * Gets the array of options
     *
     * @return array[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Gets the name of options, contains short-name
     *
     * @return array[] key is name
     */
    public function getAllOptionNames(): array
    {
        $allNames  = $this->shortcuts;
        $longNames = array_keys($this->options);

        foreach ($longNames as $name) {
            $allNames[$name] = 1;
        }

        return $allNames;
    }

    /**
     * @param string $name The InputOption shortcut
     *
     * @return bool
     */
    public function hasShortcut(string $name): bool
    {
        return isset($this->shortcuts[$name]);
    }

    /**
     * Gets an option info array
     *
     * @param string $shortcut the Shortcut name
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getOptionByShortcut(string $shortcut): array
    {
        return $this->getOption($this->shortcutToName($shortcut));
    }

    /**
     * @param string $shortcut
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    private function shortcutToName(string $shortcut)
    {
        if (!isset($this->shortcuts[$shortcut])) {
            throw new InvalidArgumentException(sprintf('The "-%s" option does not exist.', $shortcut));
        }

        return $this->shortcuts[$shortcut];
    }

    /**
     * @param array $map
     *
     * @return array
     */
    private function mergeArgOptConfig(array $map): array
    {
        return $map ? array_merge(self::$defaultArgOptConfig, $map) : self::$defaultArgOptConfig;
    }

    /**
     * Gets the synopsis.
     *
     * @param bool $short 简化版显示
     *
     * @return array
     */
    public function getSynopsis(bool $short = false): array
    {
        $elements = $args = $opts = [];

        if ($short && $this->options) {
            $elements[] = '[options]';
        } elseif (!$short) {
            foreach ($this->options as $name => $option) {
                $value = '';

                if ($this->optionIsAcceptValue($option['mode'])) {
                    $value = sprintf(' %s%s%s', $option['optional'] ? '[' : '', strtoupper($name),
                        $option['optional'] ? ']' : '');
                }

                $shortcut   = $option['shortcut'] ? sprintf('-%s, ', $option['shortcut']) : '    ';
                $elements[] = sprintf('[%s--%s%s]', $shortcut, $name, $value);

                $key        = "{$shortcut}--{$name}";
                $opts[$key] = ($option['required'] ? '<red>*</red>' : '') . $option['description'];
            }
        }

        if ($this->arguments && count($elements)) {
            $elements[] = '[--]';
        }

        foreach ($this->arguments as $name => $argument) {
            $des = $argument['required'] ? '<red>*</red>' . $argument['description'] : $argument['description'];

            $element = '<' . $name . '>';
            if (!$argument['required']) {
                $element = '[' . $element . ']';
            } elseif ($argument['isArray']) {
                $element .= ' (' . $element . ')';
            }

            if ($argument['isArray']) {
                $element .= '...';
            }

            $elements[]  = $element;
            $args[$name] = $des;
        }

        $example = $this->example;
        if ($this->example) {

        }

        return [
            $this->description,
            'usage:'          => implode(' ', $elements),
            'options:'        => $opts,
            'arguments:'      => $args,
            'example:'        => $example,
            'global options:' => '',
        ];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function argumentIsRequired($name): bool
    {
        if (isset($this->arguments[$name])) {
            return $this->arguments[$name]['mode'] === Input::ARG_REQUIRED;
        }

        return false;
    }

    /**
     * @param int $mode
     *
     * @return bool
     */
    protected function argumentIsAcceptValue(int $mode): bool
    {
        return $mode & Input::ARG_REQUIRED || $mode & Input::ARG_OPTIONAL;
    }

    /**
     * @param int $mode
     *
     * @return bool
     */
    protected function optionIsAcceptValue(int $mode): bool
    {
        return $mode & Input::OPT_REQUIRED || $mode & Input::OPT_OPTIONAL;
    }

    /**
     * @return string|array
     */
    public function getExample()
    {
        return $this->example;
    }

    /**
     * @param string|array $example
     *
     * @return $this
     */
    public function setExample($example): self
    {
        $this->example = $example;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array
     */
    public function getShortcuts(): array
    {
        return $this->shortcuts;
    }
}
