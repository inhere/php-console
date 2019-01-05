<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-31
 * Time: 13:14
 */

namespace Inhere\Console\Component;

use Inhere\Console\AbstractApplication;
use Inhere\Console\Face\ErrorHandlerInterface;
use Toolkit\Cli\Highlighter;

/**
 * Class ErrorHandler
 * @package Inhere\Console\Component
 */
class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * @inheritdoc
     */
    public function handle(\Throwable $e, AbstractApplication $app)
    {
        $class = \get_class($e);

        // open debug, throw exception
        if ($app->isDebug()) {
            $tpl = <<<ERR
\n<error> Error </error> <mga>%s</mga>

At File <cyan>%s</cyan> line <bold>%d</bold>
Exception class is <magenta>$class</magenta>
<comment>Code View:</comment>\n\n%s
<comment>Code Trace:</comment>\n\n%s\n
ERR;
            $line = $e->getLine();
            $file = $e->getFile();
            $snippet = Highlighter::create()->highlightSnippet(\file_get_contents($file), $line, 3, 3);
            $message = \sprintf(
                $tpl,
                // $e->getCode(),
                $e->getMessage(),
                $file,
                $line,
                // __METHOD__,
                $snippet,
                $e->getTraceAsString()
            // \str_replace('):', '): -', $e->getTraceAsString())
            );

            if ($app->getConfig('hideRootPath') && ($rootPath = $app->getConfig('rootPath'))) {
                $message = \str_replace($rootPath, '{ROOT}', $message);
            }

            $app->write($message, false);
        } else {
            // simple output
            $app->getOutput()->error('An error occurred! MESSAGE: ' . $e->getMessage());
            $app->write("\nYou can use '--debug' to see error details.");
        }
    }
}
