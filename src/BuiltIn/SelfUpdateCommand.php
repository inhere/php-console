<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\BuiltIn;

use Exception;
use Humbug\SelfUpdate\Strategy\GithubStrategy;
use Humbug\SelfUpdate\Strategy\ShaStrategy;
use Humbug\SelfUpdate\Updater;
use Humbug\SelfUpdate\VersionParser;
use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Toolkit\PFlag\FlagType;
use function strlen;

/**
 * Class SelfUpdateCommand
 *
 * @package Inhere\Console\BuiltIn
 */
class SelfUpdateCommand extends Command
{
    public const VERSION_URL = 'https://inhere.github.io/humbug/downloads/humbug.version';

    public const PHAR_URL = 'https://inhere.github.io/humbug/downloads/humbug.phar';

    public const PACKAGE_NAME = 'inhere/console';

    public const FILE_NAME = 'console.phar';

    protected static string $name = 'self-update';

    protected static string $desc = 'Update phar package to most recent stable, pre-release or development build.';

    /**
     * @var string
     */
    protected string $version;

    /**
     * Execute the command.
     *
     * @options
     * --check          bool;check
     * --rollback       bool;Rollback to prev version
     *
     * @param Input  $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output): void
    {
        $this->version = $this->getApp()->getVersion();
        $parser        = new VersionParser;

        /**
         * Check for ancilliary options
         */
        if ($this->flags->getOpt('rollback')) {
            $this->rollback();

            return;
        }

        if ($this->flags->getOpt('check')) {
            $this->printAvailableUpdates();

            return;
        }

        /**
         * Update to any specified stability option
         */
        if ($this->flags->getOpt('dev')) {
            $this->updateToDevelopmentBuild();

            return;
        }

        if ($this->flags->getOpt('pre')) {
            $this->updateToPreReleaseBuild();

            return;
        }

        if ($this->flags->getOpt('stable')) {
            $this->updateToStableBuild();

            return;
        }

        if ($this->flags->getOpt('non-dev')) {
            $this->updateToMostRecentNonDevRemote();

            return;
        }

        /**
         * If current build is stable, only update to more recent stable
         * versions if available. User may specify otherwise using options.
         */
        if ($parser->isStable($this->version)) {
            $this->updateToStableBuild();

            return;
        }

        /**
         * By default, update to most recent remote version regardless
         * of stability.
         */
        $this->updateToMostRecentNonDevRemote();
    }

    protected function getStableUpdater(): Updater
    {
        $updater = new Updater;
        $updater->setStrategy(Updater::STRATEGY_GITHUB);

        return $this->getGithubReleasesUpdater($updater);
    }

    protected function getPreReleaseUpdater(): Updater
    {
        $updater = new Updater;
        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setStability(GithubStrategy::UNSTABLE);

        return $this->getGithubReleasesUpdater($updater);
    }

    protected function getMostRecentNonDevUpdater(): Updater
    {
        $updater = new Updater;
        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setStability(GithubStrategy::ANY);

        return $this->getGithubReleasesUpdater($updater);
    }

    protected function getGithubReleasesUpdater(Updater $updater): Updater
    {
        $updater->getStrategy()->setPackageName(self::PACKAGE_NAME);
        $updater->getStrategy()->setPharName(self::FILE_NAME);
        $updater->getStrategy()->setCurrentLocalVersion($this->version);

        return $updater;
    }

    protected function getDevelopmentUpdater(): Updater
    {
        $updater = new Updater;
        $updater->getStrategy()->setPharUrl(self::PHAR_URL);
        $updater->getStrategy()->setVersionUrl(self::VERSION_URL);

        return $updater;
    }

    protected function updateToStableBuild(): void
    {
        $this->update($this->getStableUpdater());
    }

    protected function updateToPreReleaseBuild(): void
    {
        $this->update($this->getPreReleaseUpdater());
    }

    protected function updateToMostRecentNonDevRemote(): void
    {
        $this->update($this->getMostRecentNonDevUpdater());
    }

    protected function updateToDevelopmentBuild(): void
    {
        $this->update($this->getDevelopmentUpdater());
    }

    protected function update(Updater $updater): void
    {
        $this->output->writeln('Updating...' . PHP_EOL);
        try {
            $result = $updater->update();

            $newVersion = $updater->getNewVersion();
            $oldVersion = $updater->getOldVersion();

            if (strlen($newVersion) === 40) {
                $newVersion = 'dev-' . $newVersion;
            }

            if (strlen($oldVersion) === 40) {
                $oldVersion = 'dev-' . $oldVersion;
            }

            if ($result) {
                $this->output->writeln('<fg=green>Humbug has been updated.</fg=green>');
                $this->output->writeln(sprintf(
                    '<fg=green>Current version is:</fg=green> <options=bold>%s</options=bold>.',
                    $newVersion
                ));
                $this->output->writeln(sprintf(
                    '<fg=green>Previous version was:</fg=green> <options=bold>%s</options=bold>.',
                    $oldVersion
                ));
            } else {
                $this->output->writeln('<fg=green>Humbug is currently up to date.</fg=green>');
                $this->output->writeln(sprintf(
                    '<fg=green>Current version is:</fg=green> <options=bold>%s</options=bold>.',
                    $oldVersion
                ));
            }
        } catch (Exception $e) {
            $this->output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $e->getMessage()));
        }
        $this->output->write(PHP_EOL);
        $this->output->writeln('You can also select update stability using --dev, --pre (alpha/beta/rc) or --stable.');
    }

    protected function rollback(): void
    {
        $updater = new Updater;
        try {
            $result = $updater->rollback();
            if ($result) {
                $this->output->writeln('<fg=green>Humbug has been rolled back to prior version.</fg=green>');
            } else {
                $this->output->writeln('<fg=red>Rollback failed for reasons unknown.</fg=red>');
            }
        } catch (Exception $e) {
            $this->output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $e->getMessage()));
        }
    }

    protected function printAvailableUpdates(): void
    {
        $this->printCurrentLocalVersion();
        $this->printCurrentStableVersion();
        $this->printCurrentPreReleaseVersion();
        $this->printCurrentDevVersion();
        $this->output->writeln('You can select update stability using --dev, --pre or --stable when self-updating.');
    }

    protected function printCurrentLocalVersion(): void
    {
        $this->output->writeln(sprintf(
            'Your current local build version is: <options=bold>%s</options=bold>',
            $this->version
        ));
    }

    protected function printCurrentStableVersion(): void
    {
        $this->printVersion($this->getStableUpdater());
    }

    protected function printCurrentPreReleaseVersion(): void
    {
        $this->printVersion($this->getPreReleaseUpdater());
    }

    protected function printCurrentDevVersion(): void
    {
        $this->printVersion($this->getDevelopmentUpdater());
    }

    protected function printVersion(Updater $updater): void
    {
        $stability = 'stable';
        if ($updater->getStrategy() instanceof ShaStrategy) {
            $stability = 'development';
        } elseif ($updater->getStrategy() instanceof GithubStrategy && $updater->getStrategy()
                ->getStability() === GithubStrategy::UNSTABLE
        ) {
            $stability = 'pre-release';
        }

        try {
            if ($updater->hasUpdate()) {
                $this->output->writeln(sprintf(
                    'The current %s build available remotely is: <options=bold>%s</options=bold>',
                    $stability,
                    $updater->getNewVersion()
                ));
            } elseif (false === $updater->getNewVersion()) {
                $this->output->writeln(sprintf('There are no %s builds available.', $stability));
            } else {
                $this->output->writeln(sprintf('You have the current %s build installed.', $stability));
            }
        } catch (Exception $e) {
            $this->output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $e->getMessage()));
        }
    }

    protected function configure(): void
    {
        $this->flags
            ->addOpt(
                'dev',
                'd',
                'Update to most recent <cyan>development build</cyan> of package.',
                FlagType::BOOL
            )
            ->addOpt(
                'non-dev',
                'N',
                'Update to most recent non-development (alpha/beta/stable) build of package tagged on Github.',
                FlagType::BOOL
            )
            ->addOpt(
                'pre',
                'p',
                'Update to most recent pre-release version of package (alpha/beta/rc) tagged on Github.',
                FlagType::BOOL
            )
            ->addOpt(
                'stable',
                's',
                'Update to most recent stable version tagged on Github.',
                FlagType::BOOL
            )
            ->addOpt(
                'rollback',
                'r',
                'Rollback to previous version of package if available on filesystem.',
                FlagType::BOOL
            )
            ->addOpt(
                'check',
                'c',
                'Checks what updates are available across all possible stability tracks.',
                FlagType::BOOL
            );
    }
}
