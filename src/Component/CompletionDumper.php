<?php declare(strict_types=1);

namespace Inhere\Console\Component;

use Inhere\Console\Application;
use Toolkit\Stdlib\Obj;

/**
 * Class CompletionDumper
 * - Generate auto completion script for zsh/bash/sh shell
 *
 * @package Inhere\Console\Component
 */
class CompletionDumper extends Obj\AbstractObj
{
    /**
     * @var Application
     */
    public $app;

}
