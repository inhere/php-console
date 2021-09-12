<?php declare(strict_types=1);

namespace Deprecated;

use Inhere\Console\Console;
use Inhere\Console\GlobalOption;
use Inhere\Console\IO\InputDefinition;
use InvalidArgumentException;
use LogicException;
use function array_diff_key;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function count;
use function explode;
use function implode;
use function is_int;
use function sprintf;
use const ARRAY_FILTER_USE_BOTH;

class AbstractHandlerOld
{
    /**
     * @var InputDefinition|null
     */
    protected $definition;

    /**
     * @return InputDefinition
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    protected function createDefinition(): InputDefinition
    {
        if (!$this->definition) {
            $this->definition = new InputDefinition();
            $this->definition->setDescription(self::getDescription());
        }

        return $this->definition;
    }

    /**
     * @return InputDefinition
     */
    protected function createDefinition2(): InputDefinition
    {
        if (!$this->definition) {
            $this->definition = new InputDefinition();

            // if have been set desc for the sub-command
            $cmdDesc = $this->commandMetas[$this->action]['desc'] ?? '';
            if ($cmdDesc) {
                $this->definition->setDescription($cmdDesc);
            }
        }

        return $this->definition;
    }

    /**
     * validate input arguments and options
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateInput(): bool
    {
        if (!$def = $this->definition) {
            return true;
        }

        $this->logf(Console::VERB_DEBUG, 'validate the input arguments and options by Definition');

        $in  = $this->input;
        $out = $this->output;

        $givenArgs = $errArgs = [];
        foreach ($in->getArgs() as $key => $value) {
            if (is_int($key)) {
                $givenArgs[$key] = $value;
            } else {
                $errArgs[] = $key;
            }
        }

        if (count($errArgs) > 0) {
            $out->liteError(sprintf('Unknown arguments (error: "%s").', implode(', ', $errArgs)));
            return false;
        }

        $defArgs     = $def->getArguments();
        $missingArgs = array_filter(array_keys($defArgs), static function ($name, $key) use ($def, $givenArgs) {
            return !array_key_exists($key, $givenArgs) && $def->argumentIsRequired($name);
        }, ARRAY_FILTER_USE_BOTH);

        if (count($missingArgs) > 0) {
            $out->liteError(sprintf('Not enough arguments (missing: "%s").', implode(', ', $missingArgs)));
            return false;
        }

        $index = 0;
        $args  = [];

        foreach ($defArgs as $name => $conf) {
            $args[$name] = $givenArgs[$index] ?? $conf['default'];
            $index++;
        }

        $in->setArgs($args);
        $this->checkNotExistsOptions($def);

        // check options
        $opts = $missingOpts = [];

        $defOpts = $def->getOptions();
        foreach ($defOpts as $name => $conf) {
            if (!$in->hasLOpt($name)) {
                // support multi short: 'a|b|c'
                $shortNames = $conf['shortcut'] ? explode('|', $conf['shortcut']) : [];
                if ($srt = $in->findOneShortOpts($shortNames)) {
                    $opts[$name] = $in->sOpt($srt);
                } elseif ($conf['default'] !== null) {
                    $opts[$name] = $conf['default'];
                } elseif ($conf['required']) {
                    $missingOpts[] = "--{$name}" . ($srt ? "|-{$srt}" : '');
                }
            }
        }

        if (count($missingOpts) > 0) {
            $out->liteError(sprintf('Not enough options parameters (missing: "%s").', implode(', ', $missingOpts)));
            return false;
        }

        if ($opts) {
            $in->setLOpts($opts);
        }

        return true;
    }

    private function checkNotExistsOptions(InputDefinition $def): void
    {
        $givenOpts  = $this->input->getOptions();
        $allDefOpts = $def->getAllOptionNames();

        // check unknown options
        if ($unknown = array_diff_key($givenOpts, $allDefOpts)) {
            $names = array_keys($unknown);

            // $first = array_shift($names);
            $first = '';
            foreach ($names as $name) {
                if (!GlobalOption::isExists($name)) {
                    $first = $name;
                    break;
                }
            }

            if (!$first) {
                return;
            }

            $errMsg = sprintf('Input option is not exists (unknown: "%s").', (isset($first[1]) ? '--' : '-') . $first);
            throw new InvalidArgumentException($errMsg);
        }
    }

}
