<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-31
 * Time: 13:14
 */

namespace Inhere\Console\Component;

use Inhere\Console\AbstractApplication;
use Inhere\Console\Contract\ErrorHandlerInterface;
use Inhere\Console\Exception\PromptException;
use Throwable;
use Toolkit\Cli\Util\Highlighter;
use function file_get_contents;
use function get_class;
use function sprintf;
use function str_replace;

/**
 * Class ErrorHandler
 *
 * @package Inhere\Console\Component
 */
class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * @inheritdoc
     */
    public function handle(Throwable $e, AbstractApplication $app): void
    {
        if ($e instanceof PromptException) {
            $app->getOutput()->error($e->getMessage());
            return;
        }

        $class = get_class($e);

        // open debug, throw exception
        if ($app->isDebug()) {
            $tpl  = <<<ERR
\n<error> Error </error> <mga>%s</mga>

At File <cyan>%s</cyan> line <bold>%d</bold>
Exception class is <magenta>$class</magenta>
<comment>Code View:</comment>\n\n%s
<comment>Code Trace:</comment>\n\n%s\n
ERR;
            $line = $e->getLine();
            $file = $e->getFile();

            $snippet = Highlighter::create()->snippet(file_get_contents($file), $line, 3, 3);
            $message = sprintf(
                $tpl, // $e->getCode(),
                $e->getMessage(),
                $file,
                $line, // __METHOD__,
                $snippet,
                $e->getTraceAsString()// \str_replace('):', '): -', $e->getTraceAsString())
            );

            if ($app->getParam('hideRootPath') && ($rootPath = $app->getParam('rootPath'))) {
                $message = str_replace($rootPath, '{ROOT}', $message);
            }

            $app->write($message, false);
            return;
        }

        // simple output
        $app->getOutput()->error('An error occurred! - ' . $e->getMessage());
        $app->write("\nYou can use '--debug 4' to see error details.");
    }
}
